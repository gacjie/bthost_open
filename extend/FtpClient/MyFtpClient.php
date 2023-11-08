<?php

namespace FtpClient;

class MyFtpClient extends \FtpClient\FtpClient
{
    public $off; // 返回操作状态(成功/失败)
    // 隐藏目录
    protected $_dirs = ['.', '..'];
    // 隐藏文件
    protected $_files = ['.user.ini'];

    protected $_error = '';

    /**
     * 方法：上传文件
     * @path  -- 本地文件
     * @newpath -- 远程文件，带文件名
     * @type  -- 若目标目录不存在则新建
     * @mode  传送模式，只能为 FTP_ASCII（文本模式）或 FTP_BINARY（二进制模式）。
     */
    public function up_file($newpath, $path, $type = true, $mode = FTP_BINARY)
    {
        if ($type) {
            $this->dir_mkdirs($newpath);
        }

        $this->off = @ftp_put($this->conn, $newpath, $path, $mode);
        if (!$this->off) {
            $this->setError('文件上传失败,请检查权限及路径是否正确！');
            return false;
        }
        return true;
    }

    /**
     * 下载文件
     * @Author   Youngxj
     * @DateTime 2019-12-09
     * @param    [type]     $local     本地文件路径
     * @param    [type]     $remote    远程文件路径
     * @param    string     $mode      传送模式。只能为 (文本模式) FTP_ASCII 或 (二进制模式) FTP_BINARY 中的其中一个。
     * @param    boolean    $resumepos 断点续传
     * @return   [type]                [description]
     */
    public function file_down($local, $remote, $mode = FTP_BINARY, $resumepos = true)
    {
        $down = @ftp_get($this->conn, $local, $remote, $mode, $resumepos);
        if ($down) {
            return $down;
        } else {
            return false;
        }
    }

    /**
     * 方法：删除文件
     * @path -- 路径
     */
    public function del_file($path)
    {
        $this->off = @ftp_delete($this->conn, $path);
        if (!$this->off) {
            $this->setError('文件删除失败,请检查权限及路径是否正确！');
            return false;
        }
        return true;
    }

    // 删除所有文件
    public function del_all($path)
    {
        // 读取所有文件/目录
        // var_dump($path);
        $files = $this->get_rawlist($path);
        // $fileArr = $this->mlsd($path);
        // $fileArr = $this->nlist($path);

        $fileArr = array_merge($files['dirs'], $files['files']);
        // var_dump($fileArr);
        if ($fileArr !== false && !empty($fileArr)) {
            foreach ($fileArr as $key => $value) {
                // var_dump($value);
                if ($value['name'] == '.' || $value['name'] == '..') { //如果为.或..
                    continue;
                }

                if (strpos($value['name'], '/') !== false) {
                    $tmp = $value['name'];
                } else {
                    $tmp = $path . '/' . $value['name'];
                }
                if (!$this->isDir($tmp)) { //如果为文件
                    $this->del_file($tmp);
                } else { //如果为目录
                    $this->del_all($tmp);
                }
            }
        }
        // var_dump('执行' . $path);
        $this->del_dir($path);
        return true;
    }

    /**
     * 删除目录
     * @Author   Youngxj
     * @DateTime 2019-12-09
     * @param    [type]     $dir 目录全路径
     * @return   [type]          [description]
     */
    public function del_dir($dir)
    {
        $del = @ftp_rmdir($this->conn, $dir);
        if ($del) {
            return true;
        } else {
            $this->setError('删除目录失败');
            return false;
        }
    }

    /**
     * 方法：生成目录
     * @path -- 路径
     */
    public function dir_mkdirs($path)
    {
        $path_arr  = explode('/', $path); // 取目录数组
        $file_name = array_pop($path_arr); // 弹出文件名
        $path_div  = count($path_arr); // 取层数
        // var_dump($path_arr);exit;
        foreach ($path_arr as $val) // 创建目录
        {
            // var_dump($val);
            if (@ftp_chdir($this->conn, $val) == false) {
                // var_dump($val);
                $tmp = @ftp_mkdir($this->conn, $val);
                // var_dump($tmp);
                if ($tmp == false) {
                    $this->setError('目录创建失败,请检查权限及路径是否正确！');
                    return false;
                }
                @ftp_chdir($this->conn, $val);
            }
        }

        for ($i = 1; $i <= $path_div; $i++) // 回退到根
        {
            @ftp_cdup($this->conn);
        }
    }

    /**
     * 文件列表
     * @Author   Youngxj
     * @DateTime 2019-12-09
     * @param    string     $filedir 目录
     * @param    string     $type 文件/目录
     * @return   [type]              [description]
     */
    public function get_rawlist($filedir = '/')
    {
        $ftp_rawlist = ftp_rawlist($this->conn, $filedir);
        foreach ($ftp_rawlist as $v) {
            $info  = array();
            $vinfo = preg_split("/[\s]+/", $v, 9);
            if ($vinfo[0] !== "total") {
                $info['chmod']          = $vinfo[0];
                $info['num']            = $vinfo[1];
                $info['owner']          = $vinfo[2];
                $info['group']          = $vinfo[3];
                $info['size']           = $vinfo[4];
                $info['month']          = $vinfo[5];
                $info['day']            = $vinfo[6];
                $info['time']           = $vinfo[7];
                $info['name']           = $vinfo[8];
                $rawlist[$info['name']] = $info;
            }
        }
        $dir  = array();
        $file = array();
        foreach ($rawlist as $k => $v) {
            if ($v['chmod'][0] == "d") {
                $dir[$k] = $v;
            } elseif ($v['chmod'][0] == "-") {
                $file[$k] = $v;
            }
        }
        // 删除指定目录
        foreach ($this->_dirs as $key => $value) {
            if (isset($dir[$value])) {
                unset($dir[$value]);
            }
        }
        // 删除指定文件
        foreach ($this->_files as $key => $value) {
            if (isset($file[$value])) {
                unset($file[$value]);
            }
        }
        return ['dirs' => $dir, 'files' => $file];

        foreach ($dir as $dirname => $dirinfo) {
            echo "[ $dirname ] " . $dirinfo['chmod'] . " | " . $dirinfo['owner'] . " | " . $dirinfo['group'] . " | " . $dirinfo['month'] . " " . $dirinfo['day'] . " " . $dirinfo['time'] . "<br>";
        }
        foreach ($file as $filename => $fileinfo) {
            echo "$filename " . $fileinfo['chmod'] . " | " . $fileinfo['owner'] . " | " . $fileinfo['group'] . " | " . $fileinfo['size'] . " Byte | " . $fileinfo['month'] . " " . $fileinfo['day'] . " " . $fileinfo['time'] . "<br>";
        }
    }

    /**
     * 修改文件/目录名
     * @Author   Youngxj
     * @DateTime 2019-12-09
     * @param    [type]     全路径 老文件/目录名
     * @param    [type]     全路径 新文件/目录名 [description]
     */
    public function set_name($old, $new)
    {
        $new = ftp_rename($this->conn, $old, $new);
        if ($new) {
            return true;
        } else {
            $this->setError('命名失败');
            return false;
        }
    }

    /**
     * 方法：复制文件
     * 说明：由于FTP无复制命令,本方法变通操作为：下载后再上传到新的路径
     * @path  -- 原路径
     * @newpath -- 新路径
     * @type  -- 若目标目录不存在则新建
     */
    public function copy_file($path, $newpath, $downpath, $type = true)
    {
        if ($type) {
            $this->dir_mkdirs($newpath);
        }
        // $downpath  = "c:/tmp.dat";
        $this->off = @ftp_get($this->conn, $downpath, $path, FTP_BINARY); // 下载
        if (!$this->off) {
            $this->setError('文件复制失败,请检查权限及原路径是否正确！');
            return false;
        }

        return $this->up_file($newpath, $downpath, $type, FTP_BINARY);
    }

    /**
     * 方法：移动文件
     * @path  -- 原路径
     * @newpath -- 新路径
     * @type  -- 若目标目录不存在则新建
     */
    public function move_file($path, $newpath, $type = true)
    {
        if ($type) {
            $this->dir_mkdirs($newpath);
        }

        $this->off = @ftp_rename($this->conn, $path, $newpath);
        if (!$this->off) {
            $this->setError('文件移动失败,请检查权限及原路径是否正确！');
            return false;
        }
        return true;
    }

    /**
     * 设置错误信息
     *
     * @param $error 错误信息
     * @return Auth
     */
    public function setError($error)
    {
        $this->_error = $error;
        return $this;
    }

    /**
     * 获取错误信息
     * @return string
     */
    public function getError()
    {
        return $this->_error ? __($this->_error) : '';
    }
}