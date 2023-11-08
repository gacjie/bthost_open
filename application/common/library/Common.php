<?php

namespace app\common\library;

use think\Config;
use think\Db;

class Common
{
    public static function sql_back($name = 'backsql.sql', $path = ROOT_PATH . 'Data/')
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
        $to_file_name = $path . $name;
        //数据库中有哪些表
        $tables = Db::query('SHOW TABLES ');
        $tablelist = array();
        foreach ($tables as $v) {
            foreach ($v as $vv) {
                $tablelist[] = $vv;
            }
        }

        // 数据库名
        $db_name = Config::get('database.database');

        $info = '-- Online Database Management SQL Dump' . PHP_EOL;
        $info .= '-- 数据库名: ' . $db_name . PHP_EOL;
        $info .= '-- 生成日期: ' . date('Y-m-d H:i:s') . PHP_EOL;
        $info .= '-- PHP 版本: ' . phpversion() . PHP_EOL . PHP_EOL;

        $info .= 'SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";' . PHP_EOL;
        $info .= 'SET time_zone = "+08:00";' . PHP_EOL;
        $info .= 'SET NAMES utf8;' . PHP_EOL . PHP_EOL;

        $info .= '-- --------------------------------------------------------' . PHP_EOL . PHP_EOL;

        file_put_contents($to_file_name, $info, FILE_APPEND);
        //将每个表的表结构导出到文件
        foreach ($tablelist as $val) {
            $res = Db::query('show create table ' . $val);

            foreach ($res as $v) {
                $newres = $v['Create Table'];
            }

            $info = "-- ----------------------------\r\n";
            $info .= "-- Table structure for `" . $val . "`\r\n";
            $info .= "-- ----------------------------\r\n";
            $info .= "DROP TABLE IF EXISTS `" . $val . "`;\r\n";
            $sqlStr = $info . $newres . ";\r\n\r\n";
            //追加到文件
            file_put_contents($to_file_name, $sqlStr, FILE_APPEND);
        }

        //将每个表的数据导出到文件
        foreach ($tablelist as $val) {
            $sql = "select * from " . $val;
            $res = Db::query('select * from ' . $val);

            //如果表中没有数据，则继续下一张表
            if (count($res) < 1) continue;
            //
            $info = "-- ----------------------------\r\n";
            $info .= "-- Records for `" . $val . "`\r\n";
            $info .= "-- ----------------------------\r\n";
            file_put_contents($to_file_name, $info, FILE_APPEND);
            //读取数据

            foreach ($res as $v) {
                $sqlstr = "INSERT INTO `" . $val . "` VALUES (";
                foreach ($v as $vv) {
                    //将数据中的单引号转义，否则还原时会出错
                    $newvv = str_replace("'", "\'", $vv);
                    $sqlstr .= "'" . $newvv . "', ";
                }
                //去掉最后一个逗号和空格
                $sqlstr = substr($sqlstr, 0, strlen($sqlstr) - 2);
                $sqlstr .= ");\r\n";
                file_put_contents($to_file_name, $sqlstr, FILE_APPEND);
            }
            file_put_contents($to_file_name, "\r\n", FILE_APPEND);
        }

        return true;
    }

    // 清除缓存
    public static function clear_cache()
    {
        // 清除opcache缓存
        if (extension_loaded('Zend OPcache')) {
            opcache_reset();
        }
    }

    // 备案检查
    public static function beian_check($domain)
    {
        try {
            return Config::get('site.icp_check_api')?self::chinazIcpCheck($domain):self::qqsuuIcpCheck($domain);
        } catch (\Exception $th) {
            return false;
        }
        return false;
    }

    // 大米免费接口
    public static function qqsuuIcpCheck($domain){
        $api = 'https://api.qqsuu.cn/api/icp?url='.$domain;
        $check = \fast\Http::get($api);
        if ($check && $check_data = json_decode($check, 1)) {
            if(isset($check_data['name'])){
                return true;
            }elseif(isset($check_data['code'])&&$check_data['code']=='-1'){
                return false;
            }
        }
        return false;
    }

    /**
     * 站长之家备案查询接口
     *
     * @param [type] $domain    域名
     * @return void
     */
    public static function chinazIcpCheck($domain)
    {
        if(!Config::get('site.chinaz_key')){
            return false;
        }
        $api = 'http://apidata.chinaz.com/CallAPI/Domain';
        $data = [
            'key' => Config::get('site.chinaz_key'),
            'domainName' => $domain,
        ];
        $url = $api.'?'.http_build_query($data);
        $check = \fast\Http::get($url);
        if ($check && $check_data = json_decode($check, 1)) {
            if($check_data['StateCode']==200){
                return true;
            }
        }
        return false;
    }
}
