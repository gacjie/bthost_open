<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use app\common\exception\UploadException;
use app\common\library\Btaction;
use app\common\library\Upload;
use think\addons\Service;
use think\Cache;
use think\Config;
use think\Db;
use think\Debug;
use think\Exception;
use think\Lang;
use think\Validate;
use fast\Http;

/**
 * Ajax异步请求接口
 * @internal
 */
class Ajax extends Backend
{

    protected $noNeedLogin = ['lang'];
    protected $noNeedRight = ['*'];
    protected $layout = '';

    public static $filePath = ROOT_PATH . 'Data/';

    public function _initialize()
    {
        parent::_initialize();

        //设置过滤方法
        $this->request->filter(['strip_tags', 'htmlspecialchars']);
        // $this->auth_check_local();
    }

    /**
     * 加载语言包
     */
    public function lang()
    {
        header('Content-Type: application/javascript');
        header("Cache-Control: public");
        header("Pragma: cache");

        $offset = 30 * 60 * 60 * 24; // 缓存一个月
        header("Expires: " . gmdate("D, d M Y H:i:s", time() + $offset) . " GMT");

        $controllername = input("controllername");
        //默认只加载了控制器对应的语言名，你还根据控制器名来加载额外的语言包
        $this->loadlang($controllername);
        return jsonp(Lang::get(), 200, [], ['json_encode_param' => JSON_FORCE_OBJECT | JSON_UNESCAPED_UNICODE]);
    }

    /**
     * 上传文件
     */
    public function upload()
    {
        Config::set('default_return_type', 'json');
        //必须设定cdnurl为空,否则cdnurl函数计算错误
        Config::set('upload.cdnurl', '');
        $chunkid = $this->request->post("chunkid");
        if ($chunkid) {
            if (!Config::get('upload.chunking')) {
                $this->error(__('Chunk file disabled'));
            }
            $action = $this->request->post("action");
            $chunkindex = $this->request->post("chunkindex/d");
            $chunkcount = $this->request->post("chunkcount/d");
            $filename = $this->request->post("filename");
            $method = $this->request->method(true);
            if ($action == 'merge') {
                $attachment = null;
                //合并分片文件
                try {
                    $upload = new Upload();
                    $attachment = $upload->merge($chunkid, $chunkcount, $filename);
                } catch (UploadException $e) {
                    $this->error($e->getMessage());
                }
                $this->success(__('Uploaded successful'), '', ['url' => $attachment->url, 'fullurl' => cdnurl($attachment->url, true)]);
            } elseif ($method == 'clean') {
                //删除冗余的分片文件
                try {
                    $upload = new Upload();
                    $upload->clean($chunkid);
                } catch (UploadException $e) {
                    $this->error($e->getMessage());
                }
                $this->success();
            } else {
                //上传分片文件
                //默认普通上传文件
                $file = $this->request->file('file');
                try {
                    $upload = new Upload($file);
                    $upload->chunk($chunkid, $chunkindex, $chunkcount);
                } catch (UploadException $e) {
                    $this->error($e->getMessage());
                }
                $this->success();
            }
        } else {
            $attachment = null;
            //默认普通上传文件
            $file = $this->request->file('file');
            try {
                $upload = new Upload($file);
                $attachment = $upload->upload();
            } catch (UploadException $e) {
                $this->error($e->getMessage());
            }

            $this->success(__('Uploaded successful'), '', ['url' => $attachment->url, 'fullurl' => cdnurl($attachment->url, true)]);
        }
    }

    /**
     * 通用排序
     */
    public function weigh()
    {
        //排序的数组
        $ids = $this->request->post("ids");
        //拖动的记录ID
        $changeid = $this->request->post("changeid");
        //操作字段
        $field = $this->request->post("field");
        //操作的数据表
        $table = $this->request->post("table");
        if (!Validate::is($table, "alphaDash")) {
            $this->error();
        }
        //主键
        $pk = $this->request->post("pk");
        //排序的方式
        $orderway = strtolower($this->request->post("orderway", ""));
        $orderway = $orderway == 'asc' ? 'ASC' : 'DESC';
        $sour = $weighdata = [];
        $ids = explode(',', $ids);
        $prikey = $pk ? $pk : (Db::name($table)->getPk() ?: 'id');
        $pid = $this->request->post("pid");
        //限制更新的字段
        $field = in_array($field, ['weigh']) ? $field : 'weigh';

        // 如果设定了pid的值,此时只匹配满足条件的ID,其它忽略
        if ($pid !== '') {
            $hasids = [];
            $list = Db::name($table)->where($prikey, 'in', $ids)->where('pid', 'in', $pid)->field("{$prikey},pid")->select();
            foreach ($list as $k => $v) {
                $hasids[] = $v[$prikey];
            }
            $ids = array_values(array_intersect($ids, $hasids));
        }

        $list = Db::name($table)->field("$prikey,$field")->where($prikey, 'in', $ids)->order($field, $orderway)->select();
        foreach ($list as $k => $v) {
            $sour[] = $v[$prikey];
            $weighdata[$v[$prikey]] = $v[$field];
        }
        $position = array_search($changeid, $ids);
        $desc_id = $sour[$position]; //移动到目标的ID值,取出所处改变前位置的值
        $sour_id = $changeid;
        $weighids = array();
        $temp = array_values(array_diff_assoc($ids, $sour));
        foreach ($temp as $m => $n) {
            if ($n == $sour_id) {
                $offset = $desc_id;
            } else {
                if ($sour_id == $temp[0]) {
                    $offset = isset($temp[$m + 1]) ? $temp[$m + 1] : $sour_id;
                } else {
                    $offset = isset($temp[$m - 1]) ? $temp[$m - 1] : $sour_id;
                }
            }
            if (!isset($weighdata[$offset])) {
                continue;
            }
            $weighids[$n] = $weighdata[$offset];
            Db::name($table)->where($prikey, $n)->update([$field => $weighdata[$offset]]);
        }
        $this->success();
    }

    /**
     * 清空系统缓存
     */
    public function wipecache()
    {
        $type = $this->request->request("type");
        switch ($type) {
            case 'all':
            case 'content':
                rmdirs(CACHE_PATH, false);
                Cache::clear();
                \app\common\library\Common::clear_cache();

                if ($type == 'content') {
                    break;
                }
            case 'template':
                rmdirs(TEMP_PATH, false);
                if ($type == 'template') {
                    break;
                }
            case 'addons':
                Service::refresh();
                if ($type == 'addons') {
                    break;
                }
            case 'logs':
                rmdirs(ROOT_PATH . 'logs/', false);
                if ($type == 'logs') {
                    break;
                }
        }

        \think\Hook::listen("wipecache_after");
        $this->success();
    }

    /**
     * 读取分类数据,联动列表
     */
    public function category()
    {
        $type = $this->request->get('type');
        $pid = $this->request->get('pid');
        $where = ['status' => 'normal'];
        $categorylist = null;
        if ($pid !== '') {
            if ($type) {
                $where['type'] = $type;
            }
            if ($pid) {
                $where['pid'] = $pid;
            }

            $categorylist = Db::name('category')->where($where)->field('id as value,name')->order('weigh desc,id desc')->select();
        }
        $this->success('', null, $categorylist);
    }

    /**
     * 读取省市区数据,联动列表
     */
    public function area()
    {
        $params = $this->request->get("row/a");
        if (!empty($params)) {
            $province = isset($params['province']) ? $params['province'] : '';
            $city = isset($params['city']) ? $params['city'] : null;
        } else {
            $province = $this->request->get('province');
            $city = $this->request->get('city');
        }
        $where = ['pid' => 0, 'level' => 1];
        $provincelist = null;
        if ($province !== '') {
            if ($province) {
                $where['pid'] = $province;
                $where['level'] = 2;
            }
            if ($city !== '') {
                if ($city) {
                    $where['pid'] = $city;
                    $where['level'] = 3;
                }
                $provincelist = Db::name('area')->where($where)->field('id as value,name')->select();
            }
        }
        $this->success('', null, $provincelist);
    }

    /**
     * 生成后缀图标
     */
    public function icon()
    {
        $suffix = $this->request->request("suffix");
        header('Content-type: image/svg+xml');
        $suffix = $suffix ? $suffix : "FILE";
        echo build_suffix_image($suffix);
        exit;
    }

    public function check_username_available()
    {
        $params = $this->request->post('row/a');
        $event = $this->request->post('event');
        $id = $this->request->post('id/d');
        if (isset($params['username']) && $params['username']) {

            $where = ['username' => $params['username']];
            if ($id) {
                $where['id'] = ['<>', $id];
            }
            // var_dump($where);exit;
            $find = model('User')->where($where)->find();
            if ($find) {
                $this->error('用户名已存在');
            }
        }
        $this->success();
    }

    // 宝塔新版一键部署列表
    public function deployment()
    {
        // 获取服务器一键部署内容
        $bt = new Btaction();
        $name = $this->request->post('name');
        $keyValue = $this->request->post('keyValue');
        $search = $name ? $name : $keyValue;
        $new_data['list'] = $bt->getdeploymentlist($search);
        $new_data['total'] = count($new_data['list']);
        return json($new_data);
    }

    // 宝塔已安装php列表
    public function phplist()
    {
        $keyValue = $this->request->post('keyValue');
        // 获取服务器安装的php版本列表(由于官方的存在很大的数据变动)
        $bt = new Btaction();
        $list = $bt->getphplist();
        $new_data = [];
        $new_data['list'] = [];
        if ($list) {
            // 处理一下数据
            if ($keyValue) {
                foreach ($list as $key => $value) {
                    if ($keyValue == $value['id']) {
                        $new_data['list'] = [$value];
                        break;
                    }
                }
            } else {
                $new_data['list'] = $list;
            }
        } else {
            $new_data['list'] = [];
        }

        // 写死数据
        // $new_data['list'] = [
        //     ['id'=>'00','name'=>'纯静态',],
        //     ['id'=>'52','name'=>'52',],
        //     ['id'=>'53','name'=>'53',],
        //     ['id'=>'54','name'=>'54',],
        //     ['id'=>'55','name'=>'55',],
        //     ['id'=>'56','name'=>'56',],
        //     ['id'=>'70','name'=>'70',],
        //     ['id'=>'71','name'=>'71',],
        //     ['id'=>'72','name'=>'72',],
        //     ['id'=>'73','name'=>'73',],
        //     ['id'=>'74','name'=>'74',],
        // ];

        $new_data['total'] = count($new_data['list']);
        return json($new_data);
    }

    // 获取数据库管理地址
    public function getphpmyadmin_url()
    {
        if (!Config('site.api_token')) {
            $this->error('请先配置宝塔面板接口密钥');
        }
        $bt = new Btaction();
        if ($bt->os != 'linux') {
            $this->error('当前操作系统不支持自动获取phpMyAdmin地址，请尝试到宝塔面板中手动复制phpMyAdmin地址');
        }
        $url = $bt->getphpmyadminUrl();
        if (!$url) {
            $this->error('获取失败，请在服务器中提前安装phpMyAdmin插件');
        }
        $this->success('请求成功', '', ['url' => $url]);
    }

    // 宝塔通讯密钥连接
    public function bt_test()
    {
        $row = $this->request->post('row/a');
        $api_token = isset($row['api_token']) ? $row['api_token'] : '';
        $api_port = isset($row['api_port']) ? $row['api_port'] : '';
        $http = isset($row['http']) ? $row['http'] : '';
        if ($api_token) {
            $bt = new Btaction($api_token, $api_port, $http);
            if (!$bt->test()) {
                $this->error($bt->_error);
            }
            $this->success('请求成功');
        } else {
            $this->error('密钥不能为空');
        }
    }

    // 宝塔分类列表
    public function sortlist()
    {
        $keyValue = $this->request->post('keyValue');
        // 获取服务器中的分类列表
        $list = Cache::remember('site_type_list', function () {
            $bt = new Btaction();
            return $list = $bt->getsitetype();
        }, 0);
        if ($list) {
            if ($keyValue) {
                foreach ($list as $key => $value) {
                    if ($keyValue == $value['id']) {
                        $new_data['list'] = $list[$key];
                        break;
                    }
                }
            } else {
                $new_data['list'] = $list;
            }
        } else {
            $new_data['list'] = [];
        }

        $new_data['total'] = count($new_data['list']);
        return json($new_data);
    }

    // 获取服务器网络信息
    public function getNet()
    {
        $bt = new Btaction();
        return $bt->btPanel->GetNetWork();
    }

    // 测试
    public function testing()
    {
        // 检测是否支持curl
        // 检测对外网络访问
        // 检测延迟
        $auths = $auths_back = $ms = 0;
        if (!extension_loaded('curl')) {
            $curl = 0;
        } else {
            $curl = 1;
        }
        $ms1 = $ms2 = $baidu = '';
        try {
            $ms1 = getRequestTimes(Config::get('bty.api_url'));
            $ms2 = getRequestTimes(Config::get('bty.api_url2'));
            $ms3 = getRequestTimes('http://127.0.0.1');
            $baidu = getRequestTimes('https://www.baidu.com');
        } catch (\Exception $m) {
        }
        $this->success('请求成功', '', ['url' => Config::get('bty.api_url'), 'curl' => $curl, 'api_url1' => $ms1, 'api_url2' => $ms2, 'lan_url' => $ms3, 'baidu' => $baidu]);
    }

    /**
     * 检查更新（阿珏版）
     * @Author   Youngxj
     * @DateTime 2019-03-25
     * @return   [type]     [description]
     */
    public function updates()
    {
        Debug::remark('begin');
        if ($this->request->param('token') == '123456789') {
            \app\common\library\Common::clear_cache();
            if (!function_exists('zip_open')) {
                $this->error('不支持zip_open，请尝试切换php版本');
            }
            // TODO 在线升级优化方向
            // 逻辑判断前置
            // 版号放置数据库(待考虑)
            $config_file = APP_PATH . DS . 'extra' . DS . 'bty.php';
            // 判断文件写入权限
            if (!is_writable($config_file)) {
                $this->error('文件不可写，请检查网站目录及文件、权限、网站防篡改、系统加固等问题');
            }

            $update = new \autoupdate\Autoupdate(ROOT_PATH, true);
            $update->currentVersion = Config::get('bty.version');
            $update->updateUrl = Config::get('bty.api_url');
            $data = http_build_query(['version' => Config::get('bty.version'), 'domain' => $this->getIP(), 'obj' => Config::get('bty.APP_NAME'), 'rsa' => 1], '', '&');
            $update->updateIni = '/bthost_update_check.html?' . $data;

            try {
                $latest = $update->checkUpdate();
                if ($latest !== false) {
                    if (!$update->latestVersion) {
                        $this->error('版本号错误');
                    }
                    // 判断是否存在更新包地址
                    if (empty($update->latestUpdate) || $update->latestUpdate == '') {
                        throw new Exception('更新包地址为空');
                    }
                    // 对比版本号
                    if ($latest > $update->currentVersion) {
                        // 执行数据库更新
                        if ($update->sql_file) {
                            // 备份数据库
                            $sql_name = config('bty.version') . '_' . date("His", time()) . '.sql';
                            \app\common\library\Common::sql_back($sql_name);

                            $stream_opts = [
                                "ssl" => [
                                    "verify_peer"      => false,
                                    "verify_peer_name" => false,
                                ]
                            ];
                            $sql = file($update->sql_file, false, stream_context_create($stream_opts));
                            $query = '';
                            $prefix = Config::get("database.prefix");
                            if ($sql) {
                                foreach ($sql as $value) {
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
                        }

                        // 执行文件更新
                        if ($update->update()) {
                            // 修改版本号
                            $set = setconfig($config_file, ['version'], [$update->latestVersion]);
                            if (!$set) {
                                throw new Exception('版本号更新错误');
                            }
                            Debug::remark('end');
                            $desc = $update->currentVersion . "->" . $update->latestVersion . "，用时：" . Debug::getRangeTime('begin', 'end') . 's';
                            // 数据库更新版本号
                            Db::name('version')->insert([
                                'version'      => $update->latestVersion,
                                'last_version' => $update->currentVersion,
                                'desc'         => $desc,
                                'updatetime'   => time(),
                            ]);
                        } else {
                            throw new Exception('在线更新失败，请尝试手动更新！信息：' . $update->getLastError());
                        }
                    } else {
                        throw new Exception('没有发现可用的新版本！');
                    }
                } else {
                    throw new Exception($update->getLastError());
                }
            } catch (\Exception $e) {
                $update->log($e->getMessage());

                // 升级错误记录
                $desc = $update->currentVersion . "->" . $update->latestVersion . "，用时：" . Debug::getRangeTime('begin', 'end') . 's';
                Db::name('version')->insert([
                    'version'      => $update->latestVersion,
                    'last_version' => $update->currentVersion,
                    'desc'         => $desc,
                    'updatetime'   => time(),
                    'error_msg'    => $e->getMessage(),
                ]);
                $this->error($e->getMessage());
            }

            // 清除缓存
            rmdirs(CACHE_PATH, false);
            rmdirs(TEMP_PATH, false);
            Cache::clear();
            Service::refresh();
            \app\common\library\Common::clear_cache();

            $this->success('更新成功，欢迎体验最新的系统^_^');
        } else {
            $this->error('非法请求');
        }
    }

    // 版本检测
    public function update_check()
    {
        return ["code"=>0,"msg"=>"暂无版本更新","data"=>"","url"=>"javascript:history.back(-1);","wait"=>3];
        // $total = [
        //     'user'         => model('User')->count(),
        //     'host'         => model('Host')->count(),
        //     'sql'          => model('Sql')->count(),
        //     'ftp'          => model('Ftp')->count(),
        //     'domain'       => model('Domain')->count(),
        //     'hostlog'      => model('HostLog')->count(),
        //     'apilog'       => model('ApiLog')->count(),
        //     'domainlist'   => model('Domainlist')->count(),
        //     'hostresetlog' => model('HostresetLog')->count(),
        // ];
        // $url = Config::get('bty.api_url') . '/bthost_update_check.html';
        // $data = [
        //     'obj'     => Config::get('bty.APP_NAME'),
        //     'version' => Config::get('bty.version'),
        //     'domain'  => $this->getIp(),
        //     'rsa'     => 1,
        //     'total'   => base64_encode(json_encode($total)),
        // ];
        // $curl = http::post($url, $data);
        // return json_decode($curl, 1);
    }


    // TODO 获取任务队列开发中
    public function getTask()
    {
        return '[{"id": 46, "name": "下载文件", "type": "1", "shell": "", "other": "", "status": -1, "exectime": 1599709200, "endtime": null, "addtime": 1599709200, "log": {"name": "下载文件", "total": 503087100, "used": "324.90 MB", "pre": "67", "speed": "10.3M", "time": "12秒"}}]';
    }

    // 获取公告内容
    public function getNotice()
    {
        // 缓存器
        // $url = Config::get('bty.api_url') . '/bthost_get_notice.html';
        // $data = [
        //     'obj'     => Config::get('bty.APP_NAME'),
        //     'version' => Config::get('bty.version'),
        //     'domain'  => $this->getIp(),
        //     'rsa'     => 1,
        // ];
        // $curl = http::post($url, $data);

        // return json_decode($curl, 1);
        return ["code"=>0,"msg"=>"暂无公告","data"=>"","url"=>"javascript:history.back(-1);","wait"=>3];
    }

    /**
     * 获取服务器公网IP
     *
     * @return void
     */
    public function getIp()
    {
        $bt = new Btaction();
        $ip = $bt->getIp();
        if ($ip) {
            return $ip;
        } else {
            return false;
        }
    }

    // 设置面板自动更新状态
    public function setAutoUpdate()
    {
        $bt = new Btaction();

        if ($this->request->post('is') == 'on') {
            $set = $bt->btPanel->AutoUpdatePanel();
        } else {
            if ($bt->os == 'windows') {
                // TODO windows文件路径待验证
                $file = 'C:/BtSoft/panel/data/autoUpdate.pl';
            } else {
                $file = '/www/server/panel/data/autoUpdate.pl';
            }
            $set = $bt->btPanel->AutoUpdatePanelOff($file);
        }
        $this->success(__('Success'));
    }
}
