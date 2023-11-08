<?php

/**
 * 在线更新
 * 2020年10月15日
 * @author 原作者XingMeng，由Youngxj适配修改优化
 */

namespace app\admin\controller\general;

use app\common\controller\Backend;
use app\common\library\Btaction;
use think\Model;
use think\Config;
use fast\Http;
use think\addons\Service;
use think\Cache;
use think\Db;

class Upgrade extends Backend
{
    protected $noNeedRight = ['*'];

    // 服务器地址
    private $server = 'https://auths.yum6.cn';

    // 更新分支
    private $branch;

    // 强制同步文件
    private $force;

    // 修改版本
    private $revise;

    // 文件列表
    public $files = array();

    // 保存日志
    private $_log = true;

    // 日志保存路径
    public $logFile = 'update.log';

    // 当前版本
    public $currentVersion = 0;

    // 最新版本
    public $latestVersion = null;

    // 临时下载目录
    public $tempDir = '/upgrade';

    // 备份目录
    public $backDir = ROOT_PATH . 'Data';

    // 最后的错误
    private $_lastError = null;

    public function _initialize()
    {
        parent::_initialize();
        error_reporting(0);
        $this->server = Config::get('bty.api_url');
        $this->currentVersion = Config::get('bty.version');
        $this->branch = Config::get('bty.app_version') == '1.0.0.dev' ? '1.0.0.dev' : '1.0.0';
        $this->force = Config::get('upgrade_force') ?: 0;
        $this->revise = Config::get('revise_version') ?: 0;
    }

    public function index()
    {
        switch ($this->request->get('action', 'local')) {
            case 'local':
                $upfile = $this->local();
                break;
            case 'server':
                $files = $this->getServerList();
                $upfile = isset($files->files) ? $files->files : '';
            default:
                $upfile = array();
        }

        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            $result = array("total" => count($upfile), "rows" => $upfile);

            return json($result);
        }

        $this->assign('upfile', $upfile);
        $this->assign('branch', $this->branch);
        $this->assign('force', $this->force);
        $this->assign('revise', $this->revise);
        return $this->view->fetch();
    }

    // 检查更新
    public function check()
    {
        // 清理目录，检查下载目录及备份目录
        $this->path_delete(RUNTIME_PATH . $this->tempDir, true);
        if (!$this->check_dir(RUNTIME_PATH . $this->tempDir, true)) {
            $this->error('目录写入权限不足，无法正常升级！' . RUNTIME_PATH . $this->tempDir);
        }
        $this->check_dir($this->backDir . '/backup/upgrade', true);

        $files = $this->getServerList();
        $filesss  = $files->files;
        if (!$files || !isset($files->files)) {
            $this->success('您的系统无任何文件需要更新！');
        }

        $db = Config::get('database.type');
        foreach ($filesss as $key => $value) {
            // 过滤掉相对路径
            $value->path = preg_replace('/\.\.(\/|\\\)/', '', $value->path);
            $file = ROOT_PATH . $value->path;
            if (@md5_file($file) != $value->md5) {
                // 筛选数据库更新脚本
                if (preg_match('/([\w]+)-([\w\.\+]+)-update\.sql/i', $file, $matches)) {
                    if ($matches[1] != $db || !$this->compareVersion($matches[2], Config::get('bty.app_version'))) {
                        continue;
                    }
                }
                $filesss[$key]->id = $key;
                if (file_exists($file)) {
                    $filesss[$key]->type = '覆盖';
                    $filesss[$key]->ltime = date('Y-m-d H:i:s', filemtime($file));
                } else {
                    $filesss[$key]->type = '新增';
                    $filesss[$key]->ltime = '无';
                }
                $filesss[$key]->ctime = date('Y-m-d H:i:s', $filesss[$key]->ctime);
                $upfile[] = $filesss[$key];
            }
        }

        if ($this->request->isAjax()) {
            $result = array("total" => count($upfile), "rows" => $upfile);
            return json($result);
        }

        if (!$upfile) {
            $this->success('您的系统无任何文件需要更新！');
        } else {
            $this->success('系统有更新', '', ['files' => $upfile, 'rowtotal' => count($upfile)]);
        }
    }

    // 执行下载
    public function down()
    {
        if (!!$list = $this->request->post('list')) {
            if (!is_array($list)) { // 单个文件转换为数组
                $list = array(
                    $list
                );
            }
            $len = count($list) ?: 0;
            foreach ($list as $value) {
                // 过滤掉相对路径
                $value = preg_replace('/\.\.(\/|\\\)/', '', $value);
                // 本地存储路径
                $path = RUNTIME_PATH . $this->tempDir . $value;
                // 自动创建目录
                if (!$this->check_dir(dirname($path), true)) {
                    $this->error('目录写入权限不足，无法下载升级文件！' . dirname($path));
                }

                // 定义执行下载的类型
                $types = '.zip|.rar|.doc|.docx|.ppt|.pptx|.xls|.xlsx|.chm|.ttf|.otf|';
                $pathinfo = explode(".", basename($path));
                $ext = end($pathinfo); // 获取扩展
                if (preg_match('/\.' . $ext . '\|/i', $types)) {
                    $result = $this->getServerDown('/release/' . $this->branch . $value, $path);
                } else {
                    $result = $this->getServerFile($value, $path);
                }
            }
            if ($len == 1) {
                $this->success("更新文件 " . basename($value) . " 下载成功!");
            } else {
                $this->success("更新文件" . basename($value) . "等文件全部下载成功!");
            }
        } else {
            $this->error('请选择要下载的文件！');
        }
    }

    // 执行更新
    public function update()
    {
        if ($this->request->isPost()) {
            if (!!$list = $this->request->post('list')) {
                $list = explode(',', $list);
                $backdir = date('YmdHis');

                // 分离文件
                foreach ($list as $value) {
                    // 过滤掉相对路径
                    $value = preg_replace('/\.\.(\/|\\\)/', '', $value);

                    if (stripos($value, '/script/') !== false) {
                        $sqls[] = $value;
                    } else {
                        $path = RUNTIME_PATH . $this->tempDir . $value;
                        $des_path = ROOT_PATH . $value;
                        $back_path = $this->backDir . '/backup/upgrade/' . $backdir . $value;
                        if (!$this->check_dir(dirname($des_path), true)) {
                            $this->error('目录写入权限不足，无法正常升级！' . dirname($des_path));
                        }
                        if (file_exists($des_path)) { // 文件存在时执行备份
                            $this->check_dir(dirname($back_path), true);
                            copy($des_path, $back_path);
                        }

                        // 如果后台入口文件修改过名字，则自动适配
                        if (stripos($path, 'admin.php') !== false && stripos($_SERVER['SCRIPT_FILENAME'], 'admin.php') === false) {
                            if (file_exists($_SERVER['SCRIPT_FILENAME'])) {
                                $des_path = $_SERVER['SCRIPT_FILENAME'];
                            }
                        }

                        $files[] = array(
                            'sfile' => $path,
                            'dfile' => $des_path
                        );
                    }
                }
                // 更新数据库
                if (isset($sqls)) {
                    // 备份数据库
                    switch (Config::get('database.type')) {
                        case 'mysql':
                            $sql_name = config('bty.version') . '_' . date("His", time()) . '.sql';
                            \app\common\library\Common::sql_back($sql_name);
                            break;
                    }
                    sort($sqls); // 排序
                    foreach ($sqls as $value) {

                        $path = RUNTIME_PATH . $this->tempDir . $value;

                        if (file_exists($path)) {
                            $sql = file($path);
                            if (!$this->upsql($sql)) {
                                $this->log("数据库 $value 更新失败!");
                                $this->error("数据库" . basename($value) . " 更新失败！");
                            }
                        } else {
                            $this->error("数据库文件" . basename($value) . "不存在！");
                        }
                    }
                }

                // 替换文件
                if (isset($files)) {
                    foreach ($files as $value) {
                        if (!copy($value['sfile'], $value['dfile'])) {
                            $this->log("文件 " . $value['dfile'] . " 更新失败!");
                            $this->error("文件 " . basename($value['dfile']) . " 更新失败，请重试!");
                        }
                    }
                }

                // 清理缓存
                $this->path_delete(RUNTIME_PATH . $this->tempDir, true);
                $this->path_delete(RUNTIME_PATH . '/cache');

                rmdirs(CACHE_PATH, false);
                rmdirs(TEMP_PATH, false);
                Cache::clear();
                Service::refresh();
                \app\common\library\Common::clear_cache();

                $this->log("系统更新成功-->" . $this->latestVersion);
                $this->success('系统更新成功-->' . $this->latestVersion);
            } else {
                $this->error('请选择要更新的文件！');
            }
        }
    }

    /**
     * 删除目录及目录下所有文件或删除指定文件
     *
     * @param str $path
     *            待删除目录路径
     * @param int $delDir
     *            是否删除目录，true删除目录，false则只删除文件保留目录
     * @return bool 返回删除状态
     */
    private function path_delete($path, $delDir = false)
    {
        $result = true; // 对于空目录直接返回true状态
        if (!file_exists($path)) {
            return $result;
        }
        if (is_dir($path)) {
            if (!!$dirs = scandir($path)) {
                foreach ($dirs as $value) {
                    if ($value != "." && $value != "..") {
                        $dir = $path . '/' . $value;
                        $result = is_dir($dir) ? $this->path_delete($dir, $delDir) : unlink($dir);
                    }
                }
                if ($result && $delDir) {
                    return rmdir($path);
                } else {
                    return $result;
                }
            } else {
                return false;
            }
        } else {
            return unlink($path);
        }
    }

    // 缓存文件
    private function local()
    {
        $files = $this->getLoaclList(RUNTIME_PATH . $this->tempDir);
        $files = json_decode(json_encode($files));
        foreach ($files as $key => $value) {
            $file = ROOT_PATH . $value->path;
            $files[$key]->id = $key;
            if (file_exists($file)) {
                $files[$key]->type = '覆盖';
                $files[$key]->ltime = date('Y-m-d H:i:s', filemtime($file));
            } else {
                $files[$key]->type = '新增';
                $files[$key]->ltime = '无';
            }
            $files[$key]->ctime = date('Y-m-d H:i:s', $files[$key]->ctime);
            $upfile[] = $files[$key];
        }
        return $upfile;
    }

    // 执行更新数据库
    private function upsql($sql)
    {
        $query  = '';
        $prefix = Config::get("database.prefix");
        if ($sql) {
            foreach ($sql as $value) {
                $value = trim($value);
                if (!$value || $value[0] == '#') {
                    continue;
                }
                $value = str_replace("__db_prefix__", $prefix, trim($value));
                if (preg_match("/\;$/i", $value)) {
                    $query .= $value;
                    Db::execute($query);
                    $query = '';
                } else {
                    $query .= $value;
                }
            }
        }
        return true;
    }

    // 获取列表
    private function getServerList()
    {
        $bt = new Btaction();
        $ip = $bt->getIp();
        $param = [
            'obj' => Config::get('bty.APP_NAME'),
            'version' => Config::get('bty.version'),
            'domain' => $ip,
            'rsa' => 1,
        ];
        $url = $this->server . '/bthost_getlist.html?' . http_build_query($param);
        if (!!$rs = json_decode(http::get($url))) {
            if ($rs->code) {
                if (is_array($rs->data->files)) {
                    return $rs->data;
                } else {
                    $this->success($rs->data->files);
                }
            } else {
                $this->error($rs->data->files);
            }
        } else {
            $this->log('连接更新服务器发生错误，请稍后再试！');
            $this->error('连接更新服务器发生错误，请稍后再试！');
        }
    }

    // 获取文件
    private function getServerFile($source, $des)
    {
        $bt = new Btaction();
        $ip = $bt->getIp();
        $url = $this->server . '/bthost_getfile.html';
        $data = [
            'obj' => Config::get('bty.APP_NAME'),
            'version' => Config::get('bty.version'),
            'domain' => $ip,
            'rsa' => 1,
        ];
        $data['path'] = $source;
        $file = basename($source);
        if (!!$rs = json_decode(http::post($url, $data))) {
            if ($rs->code) {
                if (!file_put_contents($des, base64_decode($rs->data))) {
                    $this->log("更新文件  " . $file . " 下载失败!");
                    $this->error("更新文件 " . $file . " 下载失败!");
                } else {
                    return true;
                }
            } else {
                $this->error($rs->data);
            }
        } else {
            $this->log("更新文件 " . $file . " 获取失败!");
            $this->error("更新文件 " . $file . " 获取失败!");
        }
    }

    // 获取非文本文件
    private function getServerDown($source, $des)
    {
        $url = $this->server . $source;
        $file = basename($source);
        if (($sfile = fopen($url, "rb")) && ($dfile = fopen($des, "wb"))) {
            while (!feof($sfile)) {
                $fwrite = fwrite($dfile, fread($sfile, 1024 * 8), 1024 * 8);
                if ($fwrite === false) {
                    $this->log("更新文件 " . $file . " 下载失败!");
                    $this->error("更新文件 " . $file . " 下载失败!");
                }
            }
            if ($sfile) {
                fclose($sfile);
            }
            if ($dfile) {
                fclose($dfile);
            }
            return true;
        } else {
            $this->log("更新文件 " . $file . " 获取失败!");
            $this->error("更新文件 " . $file . " 获取失败!");
        }
    }

    // 获取文件列表
    private function getLoaclList($path)
    {
        $files = scandir($path);
        foreach ($files as $value) {
            if ($value != '.' && $value != '..') {
                if (is_dir($path . '/' . $value)) {
                    $this->getLoaclList($path . '/' . $value);
                } else {
                    $file = $path . '/' . $value;

                    // 避免中文乱码
                    if (!mb_check_encoding($file, 'utf-8')) {
                        $out_path = mb_convert_encoding($file, 'UTF-8', 'GBK');
                    } else {
                        $out_path = $file;
                    }

                    $out_path = str_replace(RUNTIME_PATH . $this->tempDir, '', $out_path);

                    $this->files[] = array(
                        'path' => $out_path,
                        'md5' => md5_file($file),
                        'ctime' => filemtime($file)
                    );
                }
            }
        }
        return $this->files;
    }

    // 比较程序本号
    private function compareVersion($sv, $cv)
    {
        if (empty($sv) || $sv == $cv) {
            return 0;
        }
        $sv = explode('.', $sv);
        $cv = explode('.', $cv);
        $len = count($sv) > count($cv) ? count($sv) : count($cv);
        for ($i = 0; $i < $len; $i++) {
            $n1 = $sv[$i] or 0;
            $n2 = $cv[$i] or 0;
            if ($n1 > $n2) {
                return 1;
            } elseif ($n1 < $n2) {
                return 0;
            }
        }
        return 0;
    }

    // 检测目录是否存在
    private function check_dir($path, $create = false)
    {
        if (is_dir($path)) {
            return true;
        } elseif ($create) {
            return $this->create_dir($path);
        }
    }

    // 创建目录
    private function create_dir($path)
    {
        if (!file_exists($path)) {
            if (mkdir($path, 0777, true)) {
                return true;
            }
        }
        return false;
    }

    /* 
	 * 日志记录
	 *
	 * @param string $message 信息
	 */
    public function log($message)
    {
        $this->_lastError = $message;
        if ($this->_log) {
            $log = fopen(ROOT_PATH .  DS . $this->logFile, 'a');
            if ($log) {
                $message = date('[Y-m-d H:i:s] ') . $message . "\r\n";
                fputs($log, $message);
                fclose($log);
            } else {
                $this->_lastError = '无法写入日志文件!';
            }
        }
    }
}
