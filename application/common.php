<?php

// 公共助手函数

if (!function_exists('__')) {

    /**
     * 获取语言变量值
     * @param string $name 语言变量名
     * @param array  $vars 动态变量值
     * @param string $lang 语言
     * @return mixed
     */
    function __($name, $vars = [], $lang = '')
    {
        if (is_numeric($name) || !$name) {
            return $name;
        }
        if (!is_array($vars)) {
            $vars = func_get_args();
            array_shift($vars);
            $lang = '';
        }
        return \think\Lang::get($name, $vars, $lang);
    }
}

if (!function_exists('format_bytes')) {

    /**
     * 将字节转换为可读文本
     * @param int    $size      大小
     * @param string $delimiter 分隔符
     * @return string
     */
    function format_bytes($size, $delimiter = '')
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
        for ($i = 0; $size >= 1024 && $i < 6; $i++) {
            $size /= 1024;
        }
        return round($size, 2) . $delimiter . $units[$i];
    }
}

if (!function_exists('format_megabyte')) {

    /**
     * 将兆字节转换为可读文本
     * @param int    $size      大小
     * @param string $delimiter 分隔符
     * @return string
     */
    function format_megabyte($size, $delimiter = '')
    {
        $units = array('MB', 'GB', 'TB', 'PB');
        for ($i = 0; $size >= 1024 && $i < 6; $i++) {
            $size /= 1024;
        }
        return round($size, 2) . $delimiter . $units[$i];
    }
}

if (!function_exists('datetime')) {

    /**
     * 将时间戳转换为日期时间
     * @param int    $time   时间戳
     * @param string $format 日期时间格式
     * @return string
     */
    function datetime($time, $format = 'Y-m-d H:i:s')
    {
        $time = is_numeric($time) ? $time : strtotime($time);
        return date($format, $time);
    }
}

if (!function_exists('human_date')) {

    /**
     * 获取语义化时间
     * @param int $time  时间
     * @param int $local 本地时间
     * @return string
     */
    function human_date($time, $local = null)
    {
        return \fast\Date::human($time, $local);
    }
}

if (!function_exists('cdnurl')) {

    /**
     * 获取上传资源的CDN的地址
     * @param string  $url    资源相对地址
     * @param boolean $domain 是否显示域名 或者直接传入域名
     * @return string
     */
    function cdnurl($url, $domain = false)
    {
        $regex = "/^((?:[a-z]+:)?\/\/|data:image\/)(.*)/i";
        $url = preg_match($regex, $url) ? $url : \think\Config::get('upload.cdnurl') . $url;
        if ($domain && !preg_match($regex, $url)) {
            $domain = is_bool($domain) ? request()->domain() : $domain;
            $url = $domain . $url;
        }
        return $url;
    }
}


if (!function_exists('is_really_writable')) {

    /**
     * 判断文件或文件夹是否可写
     * @param string $file 文件或目录
     * @return    bool
     */
    function is_really_writable($file)
    {
        if (DIRECTORY_SEPARATOR === '/') {
            return is_writable($file);
        }
        if (is_dir($file)) {
            $file = rtrim($file, '/') . '/' . md5(mt_rand());
            if (($fp = @fopen($file, 'ab')) === false) {
                return false;
            }
            fclose($fp);
            @chmod($file, 0777);
            @unlink($file);
            return true;
        } elseif (!is_file($file) or ($fp = @fopen($file, 'ab')) === false) {
            return false;
        }
        fclose($fp);
        return true;
    }
}

if (!function_exists('rmdirs')) {

    /**
     * 删除文件夹
     * @param string $dirname  目录
     * @param bool   $withself 是否删除自身
     * @return boolean
     */
    function rmdirs($dirname, $withself = true)
    {
        if (!is_dir($dirname)) {
            return false;
        }
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dirname, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            $todo($fileinfo->getRealPath());
        }
        if ($withself) {
            @rmdir($dirname);
        }
        return true;
    }
}

if (!function_exists('copydirs')) {

    /**
     * 复制文件夹
     * @param string $source 源文件夹
     * @param string $dest   目标文件夹
     */
    function copydirs($source, $dest)
    {
        if (!is_dir($dest)) {
            mkdir($dest, 0755, true);
        }
        foreach ($iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        ) as $item) {
            if ($item->isDir()) {
                $sontDir = $dest . DS . $iterator->getSubPathName();
                if (!is_dir($sontDir)) {
                    mkdir($sontDir, 0755, true);
                }
            } else {
                copy($item, $dest . DS . $iterator->getSubPathName());
            }
        }
    }
}

if (!function_exists('mb_ucfirst')) {
    function mb_ucfirst($string)
    {
        return mb_strtoupper(mb_substr($string, 0, 1)) . mb_strtolower(mb_substr($string, 1));
    }
}

if (!function_exists('addtion')) {

    /**
     * 附加关联字段数据
     * @param array $items  数据列表
     * @param mixed $fields 渲染的来源字段
     * @return array
     */
    function addtion($items, $fields)
    {
        if (!$items || !$fields) {
            return $items;
        }
        $fieldsArr = [];
        if (!is_array($fields)) {
            $arr = explode(',', $fields);
            foreach ($arr as $k => $v) {
                $fieldsArr[$v] = ['field' => $v];
            }
        } else {
            foreach ($fields as $k => $v) {
                if (is_array($v)) {
                    $v['field'] = isset($v['field']) ? $v['field'] : $k;
                } else {
                    $v = ['field' => $v];
                }
                $fieldsArr[$v['field']] = $v;
            }
        }
        foreach ($fieldsArr as $k => &$v) {
            $v = is_array($v) ? $v : ['field' => $v];
            $v['display'] = isset($v['display']) ? $v['display'] : str_replace(['_ids', '_id'], ['_names', '_name'], $v['field']);
            $v['primary'] = isset($v['primary']) ? $v['primary'] : '';
            $v['column'] = isset($v['column']) ? $v['column'] : 'name';
            $v['model'] = isset($v['model']) ? $v['model'] : '';
            $v['table'] = isset($v['table']) ? $v['table'] : '';
            $v['name'] = isset($v['name']) ? $v['name'] : str_replace(['_ids', '_id'], '', $v['field']);
        }
        unset($v);
        $ids = [];
        $fields = array_keys($fieldsArr);
        foreach ($items as $k => $v) {
            foreach ($fields as $m => $n) {
                if (isset($v[$n])) {
                    $ids[$n] = array_merge(isset($ids[$n]) && is_array($ids[$n]) ? $ids[$n] : [], explode(',', $v[$n]));
                }
            }
        }
        $result = [];
        foreach ($fieldsArr as $k => $v) {
            if ($v['model']) {
                $model = new $v['model'];
            } else {
                $model = $v['name'] ? \think\Db::name($v['name']) : \think\Db::table($v['table']);
            }
            $primary = $v['primary'] ? $v['primary'] : $model->getPk();
            $result[$v['field']] = $model->where($primary, 'in', $ids[$v['field']])->column("{$primary},{$v['column']}");
        }

        foreach ($items as $k => &$v) {
            foreach ($fields as $m => $n) {
                if (isset($v[$n])) {
                    $curr = array_flip(explode(',', $v[$n]));

                    $v[$fieldsArr[$n]['display']] = implode(',', array_intersect_key($result[$n], $curr));
                }
            }
        }
        return $items;
    }
}

if (!function_exists('var_export_short')) {

    /**
     * 返回打印数组结构
     * @param string $var    数组
     * @param string $indent 缩进字符
     * @return string
     */
    function var_export_short($var, $indent = "")
    {
        switch (gettype($var)) {
            case "string":
                return '"' . addcslashes($var, "\\\$\"\r\n\t\v\f") . '"';
            case "array":
                $indexed = array_keys($var) === range(0, count($var) - 1);
                $r = [];
                foreach ($var as $key => $value) {
                    $r[] = "$indent    "
                        . ($indexed ? "" : var_export_short($key) . " => ")
                        . var_export_short($value, "$indent    ");
                }
                return "[\n" . implode(",\n", $r) . "\n" . $indent . "]";
            case "boolean":
                return $var ? "TRUE" : "FALSE";
            default:
                return var_export($var, true);
        }
    }
}

if (!function_exists('letter_avatar')) {
    /**
     * 首字母头像
     * @param $text
     * @return string
     */
    function letter_avatar($text)
    {
        $total = unpack('L', hash('adler32', $text, true))[1];
        $hue = $total % 360;
        list($r, $g, $b) = hsv2rgb($hue / 360, 0.3, 0.9);

        $bg = "rgb({$r},{$g},{$b})";
        $color = "#ffffff";
        $first = mb_strtoupper(mb_substr($text, 0, 1));
        $src = base64_encode('<svg xmlns="http://www.w3.org/2000/svg" version="1.1" height="100" width="100"><rect fill="' . $bg . '" x="0" y="0" width="100" height="100"></rect><text x="50" y="50" font-size="50" text-copy="fast" fill="' . $color . '" text-anchor="middle" text-rights="admin" alignment-baseline="central">' . $first . '</text></svg>');
        $value = 'data:image/svg+xml;base64,' . $src;
        return $value;
    }
}

if (!function_exists('hsv2rgb')) {
    function hsv2rgb($h, $s, $v)
    {
        $r = $g = $b = 0;

        $i = floor($h * 6);
        $f = $h * 6 - $i;
        $p = $v * (1 - $s);
        $q = $v * (1 - $f * $s);
        $t = $v * (1 - (1 - $f) * $s);

        switch ($i % 6) {
            case 0:
                $r = $v;
                $g = $t;
                $b = $p;
                break;
            case 1:
                $r = $q;
                $g = $v;
                $b = $p;
                break;
            case 2:
                $r = $p;
                $g = $v;
                $b = $t;
                break;
            case 3:
                $r = $p;
                $g = $q;
                $b = $v;
                break;
            case 4:
                $r = $t;
                $g = $p;
                $b = $v;
                break;
            case 5:
                $r = $v;
                $g = $p;
                $b = $q;
                break;
        }

        return [
            floor($r * 255),
            floor($g * 255),
            floor($b * 255)
        ];
    }
}


/**
 * 字符加密，一次一密,可定时解密有效
 * @param string $string 原文
 * @param string $key    密钥
 * @param int    $expiry 密文有效期,单位s,0 为永久有效
 * @return string 加密后的内容
 */
function encode($string, $key = '', $expiry = 0)
{
    $ckeyLength = 4;
    $key = md5($key ? $key : '2pIL1XlNXnOPgZTA');
    $keya = md5(substr($key, 0, 16));
    $keyb = md5(substr($key, 16, 16));
    $keyc = substr(md5(microtime()), -$ckeyLength);
    $cryptkey = $keya . md5($keya . $keyc);
    $keyLength = strlen($cryptkey);
    $string = sprintf('%010d', $expiry ? $expiry + time() : 0) . substr(md5($string . $keyb), 0, 16) . $string;
    $stringLength = strlen($string);

    $rndkey = array();
    for ($i = 0; $i <= 255; $i++) {
        $rndkey[$i] = ord($cryptkey[$i % $keyLength]);
    }

    $box = range(0, 255);
    // 打乱密匙簿，增加随机性
    for ($j = $i = 0; $i < 256; $i++) {
        $j = ($j + $box[$i] + $rndkey[$i]) % 256;
        $tmp = $box[$i];
        $box[$i] = $box[$j];
        $box[$j] = $tmp;
    }
    // 加解密，从密匙簿得出密匙进行异或，再转成字符
    $result = '';
    for ($a = $j = $i = 0; $i < $stringLength; $i++) {
        $a = ($a + 1) % 256;
        $j = ($j + $box[$a]) % 256;
        $tmp = $box[$a];
        $box[$a] = $box[$j];
        $box[$j] = $tmp;
        $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
    }
    $result = $keyc . str_replace('=', '', base64_encode($result));
    $result = str_replace(array('+', '/', '='), array('-', '_', '.'), $result);
    return $result;
}

/**
 * 字符解密，一次一密,可定时解密有效
 * @param string $string 密文
 * @param string $key    解密密钥
 * @return string 解密后的内容
 */
function decode($string, $key = '')
{
    if (strpos($string, '$') !== false) {
        return '';
    }
    $string = str_replace(array('-', '_', '.'), array('+', '/', '='), $string);
    $ckeyLength = 4;
    $key = md5($key ? $key : '2pIL1XlNXnOPgZTA');
    $keya = md5(substr($key, 0, 16));
    $keyb = md5(substr($key, 16, 16));
    $keyc = substr($string, 0, $ckeyLength);
    $cryptkey = $keya . md5($keya . $keyc);
    $keyLength = strlen($cryptkey);
    $string = base64_decode(substr($string, $ckeyLength));
    $stringLength = strlen($string);

    $rndkey = array();
    for ($i = 0; $i <= 255; $i++) {
        $rndkey[$i] = ord($cryptkey[$i % $keyLength]);
    }

    $box = range(0, 255);
    // 打乱密匙簿，增加随机性
    for ($j = $i = 0; $i < 256; $i++) {
        $j = ($j + $box[$i] + $rndkey[$i]) % 256;
        $tmp = $box[$i];
        $box[$i] = $box[$j];
        $box[$j] = $tmp;
    }
    // 加解密，从密匙簿得出密匙进行异或，再转成字符
    $result = '';
    for ($a = $j = $i = 0; $i < $stringLength; $i++) {
        $a = ($a + 1) % 256;
        $j = ($j + $box[$a]) % 256;
        $tmp = $box[$a];
        $box[$a] = $box[$j];
        $box[$j] = $tmp;
        $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
    }
    if (strpos($result, '0000000000') !== false) {
    } else {
        return '';
    }
    if ((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0)
        && substr($result, 10, 16) == substr(md5(substr($result, 26) . $keyb), 0, 16)
    ) {
        return substr($result, 26);
    } else {
        return '';
    }
}

/**
 * 获取password_hash加密后的字符串
 * @Author   Youngxj
 * @DateTime 2019-10-28
 * @param    [type]     $password 密码
 * @return   [type]               [description]
 */
function getPasswordHash($password)
{
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * 验证密码
 * @Author   Youngxj
 * @DateTime 2019-11-01
 * @param    [type]     $password    用户输入的密码
 * @param    [type]     $oldpassword 数据库中的hash字符串
 * @return   [type]                  [description]
 */
function verifyPassword($password, $oldpassword)
{
    return password_verify($password, $oldpassword);
}

/**
 * 修改config的函数
 * @param $arr1 配置前缀
 * @param $arr2 数据变量
 * @return bool 返回状态
 */
function setconfig($file, $pat, $rep)
{
    /**
     * 原理就是 打开config配置文件 然后使用正则查找替换 然后在保存文件.
     * 传递的参数为2个数组 前面的为配置 后面的为数值.  正则的匹配为单引号  如果你的是分号 请自行修改为分号
     * $pat[0] = 参数前缀;  例:   default_return_type
     * $rep[0] = 要替换的内容;    例:  json
     */
    if (is_array($pat) and is_array($rep)) {
        for ($i = 0; $i < count($pat); $i++) {
            $pats[$i] = '/\'' . $pat[$i] . '\'(.*?),/';
            $reps[$i] = "'" . $pat[$i] . "'" . "=>" . "'" . $rep[$i] . "',";
        }
        $fileurl = $file;
        $string = file_get_contents($fileurl); //加载配置文件
        $string = preg_replace($pats, $reps, $string); // 正则查找然后替换
        file_put_contents($fileurl, $string); // 写入配置文件
        return true;
    } else {
        return false;
    }
}

/**
 * 生成IP地址|ipv4
 *
 * @param [type] $start     起始IP
 * @param [type] $end       结束IP
 * @return void
 */
function ip_range($start, $end)
{
    $start = ip2long($start);
    $end = ip2long($end);
    return array_map('long2ip', range($start, $end));
}

/**
 * 提示模版
 * @Author   Youngxj
 * @DateTime 2019-05-26
 * @param string  $msg 自定义消息
 * @param boolean $die 是否终止
 * @return   [type]          [description]
 */
function sysmsg($msg = '未知的异常', $die = true)
{
    echo '
    <!DOCTYPE html>
    <html>
    <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>站点提示信息</title>
    <style type="text/css">
    html{background:#eee}body{background:#fff;color:#333;font-family:"微软雅黑","Microsoft YaHei",sans-serif;margin:2em auto;padding:1em 2em;max-width:700px;-webkit-box-shadow:10px 10px 10px rgba(0,0,0,.13);box-shadow:10px 10px 10px rgba(0,0,0,.13);opacity:.8}h1{border-bottom:1px solid #dadada;clear:both;color:#666;font:24px "微软雅黑","Microsoft YaHei",,sans-serif;margin:30px 0 0 0;padding:0;padding-bottom:7px}#error-page{margin-top:50px}h3{text-align:center}#error-page p{font-size:9px;line-height:1.5;margin:25px 0 20px}#error-page code{font-family:Consolas,Monaco,monospace}ul li{margin-bottom:10px;font-size:9px}a{color:#21759B;text-decoration:none;margin-top:-10px}a:hover{color:#D54E21}.button{background:#f7f7f7;border:1px solid #ccc;color:#555;display:inline-block;text-decoration:none;font-size:9px;line-height:26px;height:28px;margin:0;padding:0 10px 1px;cursor:pointer;-webkit-border-radius:3px;-webkit-appearance:none;border-radius:3px;white-space:nowrap;-webkit-box-sizing:border-box;-moz-box-sizing:border-box;box-sizing:border-box;-webkit-box-shadow:inset 0 1px 0 #fff,0 1px 0 rgba(0,0,0,.08);box-shadow:inset 0 1px 0 #fff,0 1px 0 rgba(0,0,0,.08);vertical-align:top}.button.button-large{height:29px;line-height:28px;padding:0 12px}.button:focus,.button:hover{background:#fafafa;border-color:#999;color:#222}.button:focus{-webkit-box-shadow:1px 1px 1px rgba(0,0,0,.2);box-shadow:1px 1px 1px rgba(0,0,0,.2)}.button:active{background:#eee;border-color:#999;color:#333;-webkit-box-shadow:inset 0 2px 5px -3px rgba(0,0,0,.5);box-shadow:inset 0 2px 5px -3px rgba(0,0,0,.5)}table{table-layout:auto;border:1px solid #333;empty-cells:show;border-collapse:collapse}th{padding:4px;border:1px solid #333;overflow:hidden;color:#333;background:#eee}td{padding:4px;border:1px solid #333;overflow:hidden;color:#333}
    </style>
    </head>
    <body id="error-page">
    <div class="panel-heading">
    <h3>站点提示信息</h3>
    ' . $msg . '
    </div>
    <div class="panel-body">
    </div>
    </body>
    </html>
    ';
    if ($die == true) {
        exit;
    }
}

/**
 * byte(字节)根据长度转成mb(兆字节)
 */
function bytes2mb($bytes)
{
    return is_numeric($bytes) ? round($bytes / 1024 / 1024, 2) : 0;
}

/**
 * 单位转换
 * @param  [type] $size [description]
 * @return [type]       [description]
 */
function formatBytes($size)
{
    $units = array(' B', ' KB', ' MB', ' GB', ' TB');
    for ($i = 0; $size >= 1024 && $i < 4; $i++) {
        $size /= 1024;
    }

    return round($size, 2) . $units[$i];
}

/**
 * 转字节
 * @Author   Youngxj
 * @DateTime 2019-05-17
 * @param    [type]     $input [description]
 * @return   [type]            [description]
 */
function byteconvert($input)
{
    preg_match('/(\d+)(\w+)/', $input, $matches);
    $type = strtolower($matches[2]);
    switch ($type) {
        case "b":
            $output = $matches[1];
            break;
        case "kb":
            $output = $matches[1] * 1024;
            break;
        case "m":
        case "mb":
            $output = $matches[1] * 1024 * 1024;
            break;
        case "gb":
            $output = $matches[1] * 1024 * 1024 * 1024;
            break;
        case "tb":
            $output = $matches[1] * 1024 * 1024 * 1024;
            break;
        default:
            $output = 0;
    }
    return $output;
}

/**
 * 单位转换到字节
 * @param  [type] $size 值
 * @return [type]       字节
 */
function toBytes($size)
{
    $size = strtolower($size);
    if (strstr($size, 'tb')) {
        $str = str_replace('tb', '', $size);
        $s = $str * 1024 * 1024 * 1024 * 1024;
    } elseif (strstr($size, 'gb')) {
        $str = str_replace('gb', '', $size);
        $s = $str * 1024 * 1024 * 1024;
    } elseif (strstr($size, 'mb')) {
        $str = str_replace('mb', '', $size);
        $s = $str * 1024 * 1024;
    } elseif (strstr($size, 'kb')) {
        $str = str_replace('kb', '', $size);
        $s = $str * 1024;
    } elseif (strstr($size, 'b')) {
        $str = str_replace('b', '', $size);
        $s = $str;
    } else {
        $s = $size;
    }
    return (string)$s;
}

/**
 * 判断是否包含字符串
 * @param  [type]  $key 长内容
 * @param  [type]  $con 关键字
 * @return boolean      [description]
 */
function isHave($key, $con)
{
    if (strpos($key, $con) !== false) {
        return true;
    } else {
        return false;
    }
}

/**
 * 查找二维数组中是否包含
 * @Author   Youngxj
 * @DateTime 2019-05-10
 * @param    [type]     $value 值
 * @param    [type]     $array 数组
 * @return   [type]            bool
 */
function deep_in_array($value, $array)
{
    foreach ($array as $item) {
        if (!is_array($item)) {
            if ($item == $value) {
                return true;
            } else {
                continue;
            }
        }

        if (in_array($value, $item)) {
            return true;
        } else if (deep_in_array($value, $item)) {
            return true;
        }
    }
    return false;
}

/**
 * 下载本地文件
 *
 * @param [type] $file_sub_path
 * @param [type] $file_name
 */
function downloadTemplate($file_sub_path, $file_name)
{
    set_time_limit(0);
    header("Content-type:text/html;charset=utf-8");

    $file_name = iconv("utf-8", "gb2312", $file_name);
    $file_path = $file_sub_path . $file_name;
    if (!file_exists($file_path)) {
        echo "下载文件不存在！";
        exit;         //如果提示这个错误，很可能你的路径不对，可以打印$file_sub_path查看
    }

    $fp = fopen($file_path, "r");
    $file_size = filesize($file_path);

    //下载文件需要用到的头
    Header("Content-type: application/octet-stream");
    Header("Accept-Ranges: bytes");
    Header("Accept-Length:" . $file_size);
    Header("Content-Disposition: attachment; filename=" . $file_name);
    $buffer = 1024;
    $file_count = 0;
    while (!feof($fp) && $file_count < $file_size) {
        $file_con = fread($fp, $buffer);
        $file_count += $buffer;
        echo $file_con;
    }
    fclose($fp);
}

/**
 * 替换get参数
 * @Author   Youngxj
 * @DateTime 2019-08-05
 * @param    [type]     $url   地址
 * @param    [type]     $key   key
 * @param    [type]     $value val
 * @return   [type]            [description]
 */
function url_set_value($url, $key, $value)
{
    $a = explode('?', $url);
    $url_f = $a[0];
    $query = $a[1];
    parse_str($query, $arr);
    $arr[$key] = $value;
    return $url_f . '?' . http_build_query($arr);
}

/**
 * 获取扩展名
 * @Author   Youngxj
 * @DateTime 2019-04-25
 * @param    [type]     $name 文本
 * @return   [type]           [description]
 */
function fileExtension($name)
{
    $file = pathinfo($name);
    if ($file) {
        return @$file['extension'];
    } else {
        return false;
    }
}

/**
 * 取百分比
 *
 * @param [type] $sum
 * @param [type] $row
 * @return float
 */
function getround($sum, $row)
{
    return @round($row / $sum * 100);
}

/**
 * 获取操作系统类型
 *
 * @return string
 */
function getOs()
{
    $os_name = php_uname();
    if (strpos($os_name, "Linux") !== false) {
        $os_str = 'linux';
    } else if (strpos($os_name, "Windows") !== false) {
        $os_str = 'windows';
    }
    return $os_str;
}

/**
 * 英文月份转数字月份
 *
 * @param [type] $value
 * @return void
 */
function monthTosmonth($value)
{
    switch (mb_strtolower($value)) {
        case 'jan':
            $m = '01';
            break;
        case 'feb':
            $m = '02';
            break;
        case 'mar':
            $m = '03';
            break;
        case 'apr':
            $m = '04';
            break;
        case 'may':
            $m = '05';
            break;
        case 'jun':
            $m = '06';
            break;
        case 'jul':
            $m = '07';
            break;
        case 'aug':
            $m = '08';
            break;
        case 'sep':
            $m = '09';
            break;
        case 'oct':
            $m = '10';
            break;
        case 'nov':
            $m = '11';
            break;
        case 'dec':
            $m = '12';
            break;
        default:
            $m = $value;
            break;
    }
    return $m;
}

/**
 * 判断是否为手机
 *
 * @return boolean
 */
function isMobile()
{
    // 如果有HTTP_X_WAP_PROFILE则一定是移动设备
    if (isset($_SERVER['HTTP_X_WAP_PROFILE']))
        return true;
    // 如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
    if (isset($_SERVER['HTTP_VIA'])) {
        // 找不到为flase,否则为true
        return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;
    }
    // 脑残法，判断手机发送的客户端标志,兼容性有待提高
    if (isset($_SERVER['HTTP_USER_AGENT'])) {
        $clientkeywords = array('nokia', 'sony', 'ericsson', 'mot', 'samsung', 'htc', 'sgh', 'lg', 'sharp', 'sie-', 'philips', 'panasonic', 'alcatel', 'lenovo', 'iphone', 'ipod', 'blackberry', 'meizu', 'android', 'netfront', 'symbian', 'ucweb', 'windowsce', 'palm', 'operamini', 'operamobi', 'openwave', 'nexusone', 'cldc', 'midp', 'wap', 'mobile');
        // 从HTTP_USER_AGENT中查找手机浏览器的关键字
        if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT'])))
            return true;
    }
    // 协议法，因为有可能不准确，放到最后判断
    if (isset($_SERVER['HTTP_ACCEPT'])) {
        // 如果只支持wml并且不支持html那一定是移动设备
        // 如果支持wml和html但是wml在html之前则是移动设备
        if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html')))) {
            return true;
        }
    }
    return false;
}

/**
 * 目录删除
 *
 * @param [type] $dirname
 * @return void
 */
function delFiles($dirname)
{
    $hand = dir($dirname);
    while (($file = $hand->read()) != false) {
        $smallfile = $hand->path . '\\' . $file;
        if (is_dir($smallfile) && $file != '.' && $file != '..') {
            if (@rmdir($smallfile) == false) {
                delFiles($smallfile);
            }
        } elseif (@is_file($smallfile)) {
            @unlink($smallfile);
        }
    }
}

/**
 * 获取服务器连接时间
 *
 * @param [type] $url
 * @param string  $data
 * @param integer $timeout
 * @param integer $time
 * @return void
 */
function getRequestTimes($url, $timeout = 60)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_exec($ch);
    $request = curl_getinfo($ch);
    curl_close($ch);
    return isset($request['total_time']) ? $request['total_time'] : false;
}

if (!function_exists('check_nav_active')) {
    /**
     * 检测会员中心导航是否高亮
     */
    function check_nav_active($url, $classname = 'active')
    {
        $auth = \app\common\library\Auth::instance();
        $requestUrl = $auth->getRequestUri();
        $url = ltrim($url, '/');
        return $requestUrl === str_replace(".", "/", $url) ? $classname : '';
    }
}

if (!function_exists('check_cors_request')) {
    /**
     * 跨域检测
     */
    function check_cors_request()
    {
        if (isset($_SERVER['HTTP_ORIGIN']) && $_SERVER['HTTP_ORIGIN']) {
            $info = parse_url($_SERVER['HTTP_ORIGIN']);
            $domainArr = explode(',', config('fastadmin.cors_request_domain'));
            $domainArr[] = request()->host();
            if (in_array("*", $domainArr) || in_array($_SERVER['HTTP_ORIGIN'], $domainArr) || (isset($info['host']) && in_array($info['host'], $domainArr))) {
                header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
            } else {
                header('HTTP/1.1 403 Forbidden');
                exit;
            }

            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Max-Age: 86400');

            if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
                if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
                    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
                }
                if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
                    header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
                }
                exit;
            }
        }
    }
}

if (!function_exists('xss_clean')) {
    /**
     * 清理XSS
     */
    function xss_clean($content, $is_image = false)
    {
        return \app\common\library\Security::instance()->xss_clean($content, $is_image);
    }
}

if (!function_exists('arr_to_str')) {
    /**
     * 二维数组转字符串
     *
     * @param array $arr 二维数组
     * @return void
     *                   0 => ['xxxxxx.cim' => 'success'],
     *                   1 => ['xxxxxx.cim' => 'success'],
     */
    function arr_to_str($arr)
    {
        if (count($arr) <= 0) {
            return false;
        }
        $t = '';
        $temp = [];
        foreach ($arr as $v) {
            if (is_array($v)) {
                $k = array_keys($v);
                $key = join('', $k);
                $v1 = array_values($v);
                // 防止二维数组下还有数组类型，强转字符串处理
                $value = is_array($v1) ? arrayToString($v1) : join('', $v1);
                $v = $key . ':' . $value;
                $temp[] = $v;
            }
        }
        $t = implode(';', $temp);
        return $t;
    }
}

if (!function_exists('arrayToString')) {
    /**
     * 多维数组转字符串
     *
     * @param [type] $arr
     * @return void
     */
    function arrayToString($arr)
    {
        if (is_array($arr)) {
            return implode(',', array_map('arrayToString', $arr));
        }
        return $arr;
    }
}

if (!function_exists('dd')) {
    /**
     * dd
     * @param ...$vars
     * @date 2021/7/10
     */
    function dd(...$vars)
    {
        foreach ($vars as $v) {
            var_dump($v);
        }

        die(1);
    }
}