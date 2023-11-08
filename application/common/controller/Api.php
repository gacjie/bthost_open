<?php

namespace app\common\controller;

use app\common\library\Auth;
use app\common\library\Btaction;
use think\Config;
use think\Exception;
use think\exception\HttpResponseException;
use think\exception\ValidateException;
use think\Hook;
use think\Lang;
use think\Loader;
use think\Request;
use think\Response;
use think\Route;
use think\Validate;
use think\Cache;

/**
 * API控制器基类
 */
class Api
{

    /**
     * @var Request Request 实例
     */
    protected $request;

    /**
     * @var bool 验证失败是否抛出异常
     */
    protected $failException = false;

    /**
     * @var bool 是否批量验证
     */
    protected $batchValidate = false;

    /**
     * @var array 前置操作方法列表
     */
    protected $beforeActionList = [];

    /**
     * 无需登录的方法,同时也就不需要鉴权了
     * @var array
     */
    protected $noNeedLogin = [];

    /**
     * 无需鉴权的方法,但需要登录
     * @var array
     */
    protected $noNeedRight = [];

    /**
     * 跳过签名验证/跨域检测
     * @var array
     */
    protected $noTokenCheck = [];

    /**
     * 权限Auth
     * @var Auth
     */
    protected $auth = null;

    /**
     * 默认响应输出类型,支持json/xml
     * @var string
     */
    protected $responseType = 'json';

    /**
     * 构造方法
     * @access public
     * @param Request $request Request 对象
     */
    public function __construct(Request $request = null)
    {
        $this->request = is_null($request) ? Request::instance() : $request;

        // 控制器初始化
        $this->_initialize();

        // 前置操作方法
        if ($this->beforeActionList) {
            foreach ($this->beforeActionList as $method => $options) {
                is_numeric($method) ?
                    $this->beforeAction($options) :
                    $this->beforeAction($method, $options);
            }
        }
    }

    /**
     * 初始化操作
     * @access protected
     */
    protected function _initialize()
    {
        //移除HTML标签
        $this->request->filter('trim,strip_tags,htmlspecialchars');

        $this->auth = Auth::instance();

        // 免签跳过跨域检测
        if (!$this->auth->match($this->noTokenCheck)) {
            // 跨域请求检测
            check_cors_request();
        }

        $modulename = $this->request->module();
        $controllername = Loader::parseName($this->request->controller());
        $actionname = strtolower($this->request->action());

        // $this->auth_check_local();

        // token
        $token = $this->request->server('HTTP_TOKEN', $this->request->request('token', \think\Cookie::get('token')));

        $path = str_replace('.', '/', $controllername) . '/' . $actionname;
        // 设置当前请求的URI
        $this->auth->setRequestUri($path);
        // 检测是否需要验证登录
        if (!$this->auth->match($this->noNeedLogin)) {
            //初始化
            $this->auth->init($token);
            //检测是否登录
            if (!$this->auth->isLogin()) {
                $this->error(__('Please login first'), null, 401);
            }
            // 判断是否需要验证权限
            if (!$this->auth->match($this->noNeedRight)) {
                // 判断控制器和方法判断是否有对应权限
                if (!$this->auth->check($path)) {
                    $this->error(__('You have no permission'), null, 403);
                }
            }
        } else {
            // 如果有传递token才验证是否登录状态
            if ($token) {
                $this->auth->init($token);
            }
        }

        if (!Config('site.debug')) {
            error_reporting(E_ALL ^ E_NOTICE);
        }

        $upload = \app\common\model\Config::upload();

        // 上传信息配置后
        Hook::listen("upload_config_init", $upload);

        Config::set('upload', array_merge(Config::get('upload'), $upload));

        // 加载当前控制器语言包
        $this->loadlang($controllername);
    }

    /**
     * 加载语言文件
     * @param string $name
     */
    protected function loadlang($name)
    {
        $name = Loader::parseName($name);
        Lang::load(APP_PATH . $this->request->module() . '/lang/' . $this->request->langset() . '/' . str_replace('.', '/', $name) . '.php');
    }

    /**
     * 操作成功返回的数据
     * @param string $msg    提示信息
     * @param mixed  $data   要返回的数据
     * @param int    $code   错误码，默认为1
     * @param string $type   输出类型
     * @param array  $header 发送的 Header 信息
     */
    protected function success($msg = '', $data = null, $code = 1, $type = null, array $header = [])
    {
        $this->result($msg, $data, $code, $type, $header);
    }

    /**
     * 操作失败返回的数据
     * @param string $msg    提示信息
     * @param mixed  $data   要返回的数据
     * @param int    $code   错误码，默认为0
     * @param string $type   输出类型
     * @param array  $header 发送的 Header 信息
     */
    protected function error($msg = '', $data = null, $code = 0, $type = null, array $header = [])
    {
        $this->result($msg, $data, $code, $type, $header);
    }

    /**
     * 返回封装后的 API 数据到客户端
     * @access protected
     * @param mixed  $msg    提示信息
     * @param mixed  $data   要返回的数据
     * @param int    $code   错误码，默认为0
     * @param string $type   输出类型，支持json/xml/jsonp
     * @param array  $header 发送的 Header 信息
     * @return void
     * @throws HttpResponseException
     */
    protected function result($msg, $data = null, $code = 0, $type = null, array $header = [])
    {
        $result = [
            'code' => $code,
            'msg'  => $msg,
            'time' => Request::instance()->server('REQUEST_TIME'),
            'data' => $data,
        ];
        // 如果未设置类型则自动判断
        $type = $type ? $type : ($this->request->param(config('var_jsonp_handler')) ? 'jsonp' : $this->responseType);

        if (isset($header['statuscode'])) {
            $code = $header['statuscode'];
            unset($header['statuscode']);
        } else {
            //未设置状态码,根据code值判断
            $code = $code >= 1000 || $code < 200 ? 200 : $code;
        }
        $response = Response::create($result, $type, $code)->header($header);
        throw new HttpResponseException($response);
    }

    /**
     * 前置操作
     * @access protected
     * @param string $method  前置操作方法名
     * @param array  $options 调用参数 ['only'=>[...]] 或者 ['except'=>[...]]
     * @return void
     */
    protected function beforeAction($method, $options = [])
    {
        if (isset($options['only'])) {
            if (is_string($options['only'])) {
                $options['only'] = explode(',', $options['only']);
            }

            if (!in_array($this->request->action(), $options['only'])) {
                return;
            }
        } elseif (isset($options['except'])) {
            if (is_string($options['except'])) {
                $options['except'] = explode(',', $options['except']);
            }

            if (in_array($this->request->action(), $options['except'])) {
                return;
            }
        }

        call_user_func([$this, $method]);
    }

    /**
     * 设置验证失败后是否抛出异常
     * @access protected
     * @param bool $fail 是否抛出异常
     * @return $this
     */
    protected function validateFailException($fail = true)
    {
        $this->failException = $fail;

        return $this;
    }

    /**
     * 验证数据
     * @access protected
     * @param array        $data     数据
     * @param string|array $validate 验证器名或者验证规则数组
     * @param array        $message  提示信息
     * @param bool         $batch    是否批量验证
     * @param mixed        $callback 回调方法（闭包）
     * @return array|string|true
     * @throws ValidateException
     */
    protected function validate($data, $validate, $message = [], $batch = false, $callback = null)
    {
        if (is_array($validate)) {
            $v = Loader::validate();
            $v->rule($validate);
        } else {
            // 支持场景
            if (strpos($validate, '.')) {
                list($validate, $scene) = explode('.', $validate);
            }

            $v = Loader::validate($validate);

            !empty($scene) && $v->scene($scene);
        }

        // 批量验证
        if ($batch || $this->batchValidate) {
            $v->batch(true);
        }
        // 设置错误信息
        if (is_array($message)) {
            $v->message($message);
        }
        // 使用回调验证
        if ($callback && is_callable($callback)) {
            call_user_func_array($callback, [$v, &$data]);
        }

        if (!$v->check($data)) {
            if ($this->failException) {
                throw new ValidateException($v->getError());
            }

            return $v->getError();
        }

        return true;
    }

    /**
     * 刷新Token
     */
    protected function token()
    {
        $token = $this->request->param('__token__');

        //验证Token
        if (!Validate::make()->check(['__token__' => $token], ['__token__' => 'require|token'])) {
            $this->error(__('Token verification error'), ['__token__' => $this->request->token()]);
        }

        //刷新Token
        $this->request->token();
    }

    // 获取公钥证书
    private static function getPublicKey()
    {
        $pem_file = ROOT_PATH . 'public_key.pem';
        if (file_exists($pem_file) && is_writable($pem_file)) {
            return $public_key = file_get_contents($pem_file);
        } else {
            throw new Exception(__('Public key acquisition failed. Please check the directory file %s', $pem_file));
        }
    }

    // 远程授权方法
    private function auth_check($ip)
    {
        // 缓存器缓存远端获取的私钥
        $url = Config::get('bty.api_url') . '/bthost_auth_check.html';
        $data = [
            'obj'     => Config::get('bty.APP_NAME'),
            'version' => Config::get('bty.version'),
            'domain'  => $ip,
            'rsa'     => 1,
        ];
        $json = \fast\Http::post($url, $data);
        return json_decode($json, 1);
    }

    // 授权验证方法
    protected function auth_check_local()
    {
        $is_ajax = $this->request->isAjax() ? 1 : 0;
        $security_code = '';
        try {
            // 公钥
            $public_key = self::getPublicKey();

            $rsa = new \fast\Rsa($public_key);

            if (!Cache::get('auth_check_ip')) {
                $bt = new Btaction();
                $ipInfo = $bt->getIp();
                $ip = $ipInfo;
                if (!$ip) {
                    $msg = __('The current server public network IP acquisition failed, please make sure that your panel has public network capability, and check whether the server communication and key are correct');
                    throw new Exception($msg);
                }
                // ip需要密文加密
                $ip_encode = encode($ipInfo, 'ZD4wNqBVN0Gn');
                Cache::remember('auth_check_ip', $ip_encode, 0);
            } else {
                $ip_encode = Cache::get('auth_check_ip');
                $ip = decode($ip_encode, 'ZD4wNqBVN0Gn');
                if (!$ip) {
                    $msg = __('The current server public network IP acquisition failed, please make sure that your panel has public network capability, and check whether the server communication and key are correct') . '，' . __('Or try to delete the directory /runtime/cache and try again!');
                    throw new Exception($msg);
                }
            }

            // 离线授权
            if (Config::get('auth.code')) {
                $security_code = Config::get('auth.code');
            } else {
                // 线上授权
                $curl = $this->auth_check($ip);
                if ($curl && isset($curl['code']) && $curl['code'] == 1) {
                    $security_code = $curl['encode'];
                    $msg = '';
                } elseif (isset($curl['msg'])) {
                    $msg = $curl['msg'];
                } else {
                    $msg = __('Authorization check failed');
                }
                if ($msg) {
                    $msg = __('Local IP：%s【Caching】<hr>Authorized info：%s', $ip, $msg);
                    throw new Exception($msg);
                }
            }

            if (!$security_code) throw new Exception($ip . __('Authorization check failed'));

            // 解密信息获取IP及有效期
            // 公钥解密
            $decode = $rsa->pubDecrypt($security_code);
            if (!$decode) throw new Exception(__('security_code error'));
            $decode_arr = explode('|', $decode);

            list($domain, $auth_expiration_time) = $decode_arr;

            // 检查授权IP是否为当前IP
            if ($domain != '9527' && $domain !== $ip) throw new Exception($ip . __('Authorization information error, please request authorization again or obtain authorization code'));

            // 检查授权是否过期
            if ($auth_expiration_time != 0 && time() > $auth_expiration_time) {
                // 删除授权码文件
                if (file_exists(APP_PATH . 'extra' . DS . 'auth.php')) {
                    @unlink(APP_PATH . 'extra' . DS . 'auth.php');
                }
                throw new Exception($ip . __('Authorization expired'));
            }

            // 记录离线授权码
            if (!Config::get('auth.code')) {
                $auth_file = APP_PATH . 'extra' . DS . 'auth.php';
                // 判断文件是否存在
                if (!file_exists($auth_file)) {
                    // 不存在则创建
                    $createfile = @fopen($auth_file, "a+");
                    $content = "<?php return ['code' => '',];";
                    @fputs($createfile, $content);
                    @fclose($createfile);
                    if (!$createfile) throw new Exception(__('The current permissions are insufficient to write the file %s', $auth_file));
                }
                // 写入code
                $config = include $auth_file;
                $config['code'] = $security_code;
                file_put_contents($auth_file, '<?php' . "\n\nreturn " . var_export($config, true) . ";");
            }

        } catch (Exception $e) {
            return $is_ajax ? $this->error($e->getMessage()) : sysmsg($e->getMessage());
        }
        return true;
    }
}
