<?php

namespace app\common\model;

use app\common\library\Auth;
use think\Model;
use think\Loader;

class HostLog extends Model
{

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = '';
    //自定义日志标题
    protected static $title = '';
    //自定义日志内容
    protected static $content = '';
    //忽略的链接正则列表
    protected static $ignoreRegex = [
        '/^(.*)\/(selectpage|index)$/i',
    ];

    public static function setTitle($title)
    {
        self::$title = $title;
    }

    public static function setContent($content)
    {
        self::$content = $content;
    }

    public static function setIgnoreRegex($regex = [])
    {
        $regex = is_array($regex) ? $regex : [$regex];
        self::$ignoreRegex = array_merge(self::$ignoreRegex, $regex);
    }

    /**
     * 记录日志
     * @param string $title
     * @param string $content
     */
    public static function record($title = '', $content = '')
    {
        $auth = Auth::instance();
        $user_id = $auth->isLogin() ? $auth->id : 0;
        $username = $auth->isLogin() ? $auth->username : __('Unknown');

        $controllername = Loader::parseName(request()->controller());
        $actionname = strtolower(request()->action());
        // 适应控制台路由规则优化
        $path = 'index/'.str_replace('.', '/', $controllername) . '/' . $actionname;
        if (self::$ignoreRegex) {
            foreach (self::$ignoreRegex as $index => $item) {
                if (preg_match($item, $path)) {
                    return;
                }
            }
        }
        $content = $content ? $content : self::$content;
        if (!$content) {
            $content = request()->param('', null, 'trim,strip_tags,htmlspecialchars');
            $content = self::getPureContent($content);
        }
        $title = $title ? $title : self::$title;
        if (!$title) {
            $title = [];
            $breadcrumb = Auth::instance()->getBreadcrumb($path);
            foreach ($breadcrumb as $k => $v) {
                $title[] = $v['title'];
            }
            $title = implode(' / ', $title);
        }
        self::create([
            'title'     => $title,
            'content'   => !is_scalar($content) ? json_encode($content, JSON_UNESCAPED_UNICODE) : $content,
            'url'       => substr(request()->url(), 0, 1500),
            'user_id'   => $user_id,
            'username'  => $username,
            'useragent' => substr(request()->server('HTTP_USER_AGENT'), 0, 255),
            'ip'        => request()->ip()
        ]);
    }

    /**
     * 获取已屏蔽关键信息的数据
     * @param $content
     * @return false|string
     */
    protected static function getPureContent($content)
    {
        if (!is_array($content)) {
            return $content;
        }
        foreach ($content as $index => &$item) {
            if (preg_match("/(password|salt|token)/i", $index)) {
                $item = "***";
            } else {
                if (is_array($item)) {
                    $item = self::getPureContent($item);
                }
            }
        }
        return $content;
    }

    public function user()
    {
        return $this->belongsTo('User', 'user_id')->setEagerlyType(0);
    }
}