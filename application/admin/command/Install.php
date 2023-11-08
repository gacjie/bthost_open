<?php

namespace app\admin\command;

use app\common\library\Btaction;
use fast\Random;
use PDO;
use think\Config;
use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use think\Db;
use think\Exception;
use think\Lang;
use think\Request;
use think\View;
use think\Cache;

class Install extends Command
{
    protected $model = null;
    /**
     * @var \think\View 视图类实例
     */
    protected $view;

    /**
     * @var \think\Request Request 实例
     */
    protected $request;

    protected function configure()
    {
        $config = Config::get('database');
        $this
            ->setName('install')
            ->addOption('hostname', 'a', Option::VALUE_OPTIONAL, 'mysql hostname', $config['hostname'])
            ->addOption('hostport', 'o', Option::VALUE_OPTIONAL, 'mysql hostport', $config['hostport'])
            ->addOption('database', 'd', Option::VALUE_OPTIONAL, 'mysql database', $config['database'])
            ->addOption('prefix', 'r', Option::VALUE_OPTIONAL, 'table prefix', $config['prefix'])
            ->addOption('username', 'u', Option::VALUE_OPTIONAL, 'mysql username', $config['username'])
            ->addOption('password', 'p', Option::VALUE_OPTIONAL, 'mysql password', $config['password'])
            ->addOption('force', 'f', Option::VALUE_OPTIONAL, 'force override', false)
            ->setDescription('New installation of FastAdmin');
    }

    /**
     * 命令行安装
     */
    protected function execute(Input $input, Output $output)
    {
        define('INSTALL_PATH', APP_PATH . 'admin' . DS . 'command' . DS . 'Install' . DS);
        // 覆盖安装
        $force = $input->getOption('force');
        $hostname = $input->getOption('hostname');
        $hostport = $input->getOption('hostport');
        $database = $input->getOption('database');
        $prefix = $input->getOption('prefix');
        $username = $input->getOption('username');
        $password = $input->getOption('password');

        $installLockFile = INSTALL_PATH . "install.lock";
        if (is_file($installLockFile) && !$force) {
            throw new Exception("\nFastAdmin already installed!\nIf you need to reinstall again, use the parameter --force=true ");
        }

        $adminUsername = 'admin';
        $adminPassword = Random::alnum(10);
        $adminEmail = 'admin@admin.com';
        $siteName = __('btHost');

        $adminName = $this->installation($hostname, $hostport, $database, $username, $password, $prefix, $adminUsername, $adminPassword, $adminEmail, $siteName);
        if ($adminName) {
            $output->highlight("Admin url:http://www.yoursite.com/{$adminName}");
        }

        $output->highlight("Admin username:{$adminUsername}");
        $output->highlight("Admin password:{$adminPassword}");

        \think\Cache::rm('__menu__');

        $output->info("Install Successed!");
    }

    /**
     * PC端安装
     */
    public function index()
    {
        $this->view = View::instance(Config::get('template'), Config::get('view_replace_str'));
        $this->request = Request::instance();

        define('INSTALL_PATH', APP_PATH . 'admin' . DS . 'command' . DS . 'Install' . DS);
        Lang::load(INSTALL_PATH . $this->request->langset() . '.php');

        $installLockFile = INSTALL_PATH . "install.lock";

        if (is_file($installLockFile)) {
            echo __('The system has been installed. If you need to reinstall, please remove %s first', 'install.lock');
            exit;
        }
        $output = function ($code, $msg, $url = null, $data = null) {
            return json(['code' => $code, 'msg' => $msg, 'url' => $url, 'data' => $data]);
        };

        if ($this->request->isPost()) {
            try {
                $check = $this->auth_check_local();
            } catch (\PDOException $e) {
                throw new Exception($e->getMessage());
            } catch (\Exception $e) {
                return $output(0, $e->getMessage());
            }

            if (is_array($check)) {
                if ($this->request->param('is_check')) {
                    return $output(1, $check['msg'], null, ['encode' => '']);
                }
            } else {
                return $output(0, $check, null);
            }

            $mysqlHostname = $this->request->post('mysqlHostname', '127.0.0.1');
            $mysqlHostport = $this->request->post('mysqlHostport', '3306');
            $hostArr = explode(':', $mysqlHostname);
            if (count($hostArr) > 1) {
                $mysqlHostname = $hostArr[0];
                $mysqlHostport = $hostArr[1];
            }
            $mysqlUsername = $this->request->post('mysqlUsername', 'root');
            $mysqlPassword = $this->request->post('mysqlPassword', '');
            $mysqlDatabase = $this->request->post('mysqlDatabase', '');
            $mysqlPrefix = $this->request->post('mysqlPrefix', 'bth_');
            $adminUsername = $this->request->post('adminUsername', 'admin');
            $adminPassword = $this->request->post('adminPassword', '');
            $adminPasswordConfirmation = $this->request->post('adminPasswordConfirmation', '');
            $adminEmail = $this->request->post('adminEmail', 'admin@admin.com');
            $siteName = $this->request->post('siteName', __('btHost'));
            $api_token = $this->request->post('api_token');
            $api_port = $this->request->post('api_port', 8888);
            // $security_code = $this->request->post('security_code');
            $http = $this->request->post('http', 'http://');

            if ($adminPassword !== $adminPasswordConfirmation) {
                return $output(0, __('The two passwords you entered did not match'));
            }

            $adminName = '';
            try {
                $adminName = $this->installation($mysqlHostname, $mysqlHostport, $mysqlDatabase, $mysqlUsername, $mysqlPassword, $mysqlPrefix, $adminUsername, $adminPassword, $adminEmail, $siteName, $api_token, $api_port, $http);
            } catch (\PDOException $e) {
                throw new Exception($e->getMessage());
            } catch (\Exception $e) {
                return $output(0, $e->getMessage());
            }
            return $output(1, __('Install Successed'), null, ['adminName' => $adminName]);
        }
        $errInfo = '';
        try {
            $this->checkenv();
        } catch (\Exception $e) {
            $errInfo = $e->getMessage();
        }

        return $this->view->fetch(INSTALL_PATH . "install.html", ['errInfo' => $errInfo, 'link' => [
            'qqun' => "https://qm.qq.com/cgi-bin/qm/qr?k=FGfc6Khovu3IL2dxuWRLvAp3m2IGAGAC&jump_from=webapi",
            'web' => 'https://btai.cc',
            'bbs' => 'https://bbs.btye.net',
            'blog' => 'https://www.youngxj.cn',
            'auths' => 'https://auths.yum6.cn/auth.html',
            'qq' => 'https://wpa.qq.com/msgrd?v=3&uin=1170535111&site=qq&menu=yes',
            'install' => 'https://www.kancloud.cn/youngxj/bthost_manual/1926353',
        ]]);
    }

    /**
     * 执行安装
     */
    protected function installation($mysqlHostname, $mysqlHostport, $mysqlDatabase, $mysqlUsername, $mysqlPassword, $mysqlPrefix, $adminUsername, $adminPassword, $adminEmail = null, $siteName = null, $api_token, $api_port, $http)
    {
        $this->checkenv();

        if ($mysqlDatabase == '') {
            throw new Exception(__('Please input correct database'));
        }
        if (!preg_match("/^\w{3,12}$/", $adminUsername)) {
            throw new Exception(__('Please input correct username'));
        }
        if (!preg_match("/^[\S]{6,32}$/", $adminPassword)) {
            throw new Exception(__('Please input correct password'));
        }
        if ($siteName == '' || preg_match("/fast" . "admin/i", $siteName)) {
            throw new Exception(__('Please input correct website'));
        }

        $sql = file_get_contents(INSTALL_PATH . 'bthost.sql');

        $sql = str_replace("`bth_", "`{$mysqlPrefix}", $sql);

        // 先尝试能否自动创建数据库
        $config = Config::get('database');
        try {
            $pdo = new PDO("{$config['type']}:host={$mysqlHostname}" . ($mysqlHostport ? ";port={$mysqlHostport}" : ''), $mysqlUsername, $mysqlPassword);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->query("CREATE DATABASE IF NOT EXISTS `{$mysqlDatabase}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

            // 连接install命令中指定的数据库
            $instance = Db::connect([
                'type'     => "{$config['type']}",
                'hostname' => "{$mysqlHostname}",
                'hostport' => "{$mysqlHostport}",
                'database' => "{$mysqlDatabase}",
                'username' => "{$mysqlUsername}",
                'password' => "{$mysqlPassword}",
                'prefix'   => "{$mysqlPrefix}",
            ]);

            // 查询一次SQL,判断连接是否正常
            $instance->execute("SELECT 1");

            // 调用原生PDO对象进行批量查询
            $instance->getPdo()->exec($sql);
        } catch (\PDOException $e) {
            throw new Exception($e->getMessage());
        }
        // 后台入口文件
        $adminFile = ROOT_PATH . 'public' . DS . 'admin.php';

        // 数据库配置文件
        $dbConfigFile = APP_PATH . 'database.php';
        $config = @file_get_contents($dbConfigFile);
        $callback = function ($matches) use ($mysqlHostname, $mysqlHostport, $mysqlUsername, $mysqlPassword, $mysqlDatabase, $mysqlPrefix) {
            $field = "mysql" . ucfirst($matches[1]);
            $replace = $$field;
            if ($matches[1] == 'hostport' && $mysqlHostport == 3306) {
                $replace = '';
            }
            return "'{$matches[1]}'{$matches[2]}=>{$matches[3]}Env::get('database.{$matches[1]}', '{$replace}'),";
        };
        $config = preg_replace_callback("/'(hostname|database|username|password|hostport|prefix)'(\s+)=>(\s+)Env::get\((.*)\)\,/", $callback, $config);

        // 检测能否成功写入数据库配置
        $result = @file_put_contents($dbConfigFile, $config);
        if (!$result) {
            throw new Exception(__('The current permissions are insufficient to write the file %s', 'application/database.php'));
        }

        // 变更默认管理员密码
        $adminPassword = $adminPassword ? $adminPassword : Random::alnum(8);
        $adminEmail = $adminEmail ? $adminEmail : "admin@admin.com";
        $newSalt = substr(md5(uniqid(true)), 0, 6);
        $newPassword = md5(md5($adminPassword) . $newSalt);
        $data = ['username' => $adminUsername, 'email' => $adminEmail, 'password' => $newPassword, 'salt' => $newSalt];
        $instance->name('admin')->where('username', 'admin')->update($data);
        // 修改后台入口
        $adminName = '';
        if (is_file($adminFile)) {
            $adminName = Random::alpha(10) . '.php';
            rename($adminFile, ROOT_PATH . 'public' . DS . $adminName);
        }

        //修改站点名称
        if ($siteName != __('btHost')) {
            $instance->name('config')->where('name', 'name')->update(['value' => $siteName]);
            $configFile = APP_PATH . 'extra' . DS . 'site.php';
            $config = include $configFile;
            $configList = $instance->name("config")->select();
            foreach ($configList as $k => $value) {
                if (in_array($value['type'], ['selects', 'checkbox', 'images', 'files'])) {
                    $value['value'] = explode(',', $value['value']);
                }
                if ($value['type'] == 'array') {
                    $value['value'] = (array)json_decode($value['value'], true);
                }
                $config[$value['name']] = $value['value'];
            }
            $config['name'] = $siteName;
            file_put_contents($configFile, '<?php' . "\n\nreturn " . var_export($config, true) . ";");
        }

        // 记录api密钥
        if ($api_token != '') {
            $api_token_encode = encode($api_token);
            $instance->name('config')->where('name', 'api_token')->update(['value' => $api_token_encode]);
            $configFile = APP_PATH . 'extra' . DS . 'site.php';
            $config = include $configFile;
            $configList = $instance->name("config")->select();
            foreach ($configList as $k => $value) {
                if (in_array($value['type'], ['selects', 'checkbox', 'images', 'files'])) {
                    $value['value'] = explode(',', $value['value']);
                }
                if ($value['type'] == 'array') {
                    $value['value'] = (array) json_decode($value['value'], true);
                }
                $config[$value['name']] = $value['value'];
            }
            $config['api_token'] = $api_token_encode;
            file_put_contents($configFile, '<?php' . "\n\nreturn " . var_export($config, true) . ";");
        }

        // 记录API端口
        if ($api_port != '' && $api_port != 8888) {
            $instance->name('config')->where('name', 'api_port')->update(['value' => $api_port]);
            $configFile = APP_PATH . 'extra' . DS . 'site.php';
            $config = include $configFile;
            $configList = $instance->name("config")->select();
            foreach ($configList as $k => $value) {
                if (in_array($value['type'], ['selects', 'checkbox', 'images', 'files'])) {
                    $value['value'] = explode(',', $value['value']);
                }
                if ($value['type'] == 'array') {
                    $value['value'] = (array) json_decode($value['value'], true);
                }
                $config[$value['name']] = $value['value'];
            }
            $config['api_port'] = $api_port;
            file_put_contents($configFile, '<?php' . "\n\nreturn " . var_export($config, true) . ";");
        }

        // 记录http协议
        if ($http != '' && $http != 'http://') {
            $instance->name('config')->where('name', 'http')->update(['value' => $http]);
            $configFile = APP_PATH . 'extra' . DS . 'site.php';
            $config = include $configFile;
            $configList = $instance->name("config")->select();
            foreach ($configList as $k => $value) {
                if (in_array($value['type'], ['selects', 'checkbox', 'images', 'files'])) {
                    $value['value'] = explode(',', $value['value']);
                }
                if ($value['type'] == 'array') {
                    $value['value'] = (array) json_decode($value['value'], true);
                }
                $config[$value['name']] = $value['value'];
            }
            $config['http'] = $http;
            file_put_contents($configFile, '<?php' . "\n\nreturn " . var_export($config, true) . ";");
        }

        // 更新随机计划任务密钥/API通信密钥
        if (1) {
            $queue_key = Random::alnum(10);
            $access_token = Random::alnum(10);
            $instance->name('config')->where('name', 'queue_key')->update(['value' => $queue_key]);
            $instance->name('config')->where('name', 'access_token')->update(['value' => $access_token]);
            $configFile = APP_PATH . 'extra' . DS . 'site.php';
            $config = include $configFile;
            $configList = $instance->name("config")->select();
            foreach ($configList as $k => $value) {
                if (in_array($value['type'], ['selects', 'checkbox', 'images', 'files'])) {
                    $value['value'] = explode(',', $value['value']);
                }
                if ($value['type'] == 'array') {
                    $value['value'] = (array) json_decode($value['value'], true);
                }
                $config[$value['name']] = $value['value'];
            }
            $config['queue_key'] = $queue_key;
            $config['access_token'] = $access_token;
            file_put_contents($configFile, '<?php' . "\n\nreturn " . var_export($config, true) . ";");
        }

        // 记录离线授权码
        // if ($security_code) {
        //     $auth_file = APP_PATH . 'extra' . DS . 'auth.php';
        //     // 判断文件是否存在
        //     if (!file_exists($auth_file)) {
        //         // 不存在则创建
        //         $createfile = @fopen($auth_file, "a+");
        //         $content = "<?php return ['code' => '',];";
        //         @fputs($createfile, $content);
        //         @fclose($createfile);
        //         if (!$createfile) {
        //             throw new Exception(__('The current permissions are insufficient to write the file %s', $auth_file));
        //         }
        //     }
        //     // 写入code
        //     $config = include $auth_file;
        //     $config['code'] = $security_code;
        //     file_put_contents($auth_file, '<?php' . "\n\nreturn " . var_export($config, true) . ";");
        // }

        $installLockFile = INSTALL_PATH . "install.lock";
        //检测能否成功写入lock文件
        $result = @file_put_contents($installLockFile, 1);
        if (!$result) {
            throw new Exception(__('The current permissions are insufficient to write the file %s', 'application/admin/command/Install/install.lock'));
        }

        return $adminName;
    }

    /**
     * 检测环境
     */
    protected function checkenv()
    {
        // 检测目录是否存在
        $checkDirs = [
            'thinkphp',
            'vendor',
            'public' . DS . 'assets' . DS . 'libs'
        ];

        //数据库配置文件
        $dbConfigFile = APP_PATH . 'database.php';

        if (version_compare(PHP_VERSION, '5.5.0', '<')) {
            throw new Exception(__("The current version %s is too low, please use PHP 5.5 or higher", PHP_VERSION));
        }
        if (!extension_loaded("PDO")) {
            throw new Exception(__("PDO is not currently installed and cannot be installed"));
        }
        if (!is_really_writable($dbConfigFile)) {
            throw new Exception(__('The current permissions are insufficient to write the configuration file application/database.php'));
        }
        foreach ($checkDirs as $k => $v) {
            if (!is_dir(ROOT_PATH . $v)) {
                throw new Exception(__('Please go to the official website to download the full package or resource package and try to install'));
                break;
            }
        }
        return true;
    }

    private static function getPublicKey()
    {
        return $public_key = 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCXySnlz6w8w0KOTz+XrzNd3+PmKJKAJRdKI4x5xNU8Q9EzWIYGyX2O1RK/FB1pwYjUVo8uNG6ghD48ZtRcumqPxU7uAHBlxq4S8zPPSGJ3NKgceRJEW/4oOFLw6jeJ1Pw3aHvg7hmxNxwgOLqlRzXDG8wBc7EqVTGa86qbfwZDEQIDAQAB';
    }

    // 远程授权验证
    private function auth_check($ip)
    {
        // 缓存器缓存远端获取的私钥
        $url = Config::get('bty.api_url') . '/bthost_auth_check.html';
        $data = [
            'obj' => Config::get('bty.APP_NAME'),
            'version' => Config::get('bty.version'),
            'domain' => $ip,
            'install' => 1,
            'rsa' => 1,
        ];
        $json = \fast\Http::post($url, $data);
        return json_decode($json, 1);
    }

    // 授权验证方法
    protected function auth_check_local()
    {
        // 授权判断
        $api_token = $this->request->post('api_token');
        $api_port = $this->request->post('api_port');
        $http = $this->request->post('http');
        if (!$api_token || !$api_port) {
            return __('Please complete') . __('api_token') . '|' . __('api_port');
        }
        $bt = new Btaction($api_token, $api_port, $http);
        if (!$bt->test()) {
            return $bt->_error;
        }
        $ip = $bt->getIp();
        if (!$ip) {
            return __('The current server public network IP acquisition failed, please make sure that your panel has public network capability, and check whether the server communication and key are correct');
        }
        $domain = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'null';

        // 公钥
        // $public_key = self::getPublicKey();

        // $rsa = new \fast\Rsa($public_key);

        // 离线授权
        // if ($this->request->post('security_code')) {
        //     $security_code = $this->request->post('security_code');
        // } else {
        //     // 线上授权
        //     $curl = $this->auth_check($ip);
        //     if ($curl && isset($curl['code']) && $curl['code'] == 1) {
        //         $security_code = $curl['encode'];
        //     } elseif (isset($curl['msg'])) {
        //         return $ip . $curl['msg'];
        //     } else {
        //         return $ip . __('Authorization check failed');
        //     }
        // }
        // 解密信息获取域名及有效期
        // 公钥解密
        // $decode = $rsa->pubDecrypt($security_code);
        // if (!$decode) {
        //     return __('security_code error');
        // }
        // $decode_arr = explode('|', $decode);

        // list($domain, $auth_expiration_time) = $decode_arr;

        // if (!isset($domain) || !isset($auth_expiration_time)) {
        //     return __('Authorization information error, please request authorization again or obtain authorization code');
        // }
        // 检查授权域名是否为当前域名
        // if (
        //     $domain != '9527' && $domain !== $ip
        // ) {
        //     return $ip . __('security_code failed');
        // }
        // 检查授权是否过期
        // if ($auth_expiration_time != 0 && time() > $auth_expiration_time) {
        //     return $ip . __('Authorization expired');
        // }
        // $exp = isset($auth_expiration_time) && $auth_expiration_time != 0 ? date('Y-m-d', $auth_expiration_time) : '00';
        return [ 'msg' => __('Auth success') . ' | ' . __('IP') . '：' . $domain . ' | ' . __('Expire date') . '：2099-12-31'];
    }
}
