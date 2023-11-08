<?php

namespace autoupdate;

/**
 * 自动更新
 * @author: 阿珏
 * @link: http://img.52ecy.cn
 */

class Autoupdate
{
	/*
	 * 保存日志
	 */
	private $_log = true;

	/* 
	 * 日志保存路径
	 */
	public $logFile = 'update.log';

	/*
	 * 最后的错误
	 */
	private $_lastError = null;

	/*
	 * 当前版本
	 */
	public $currentVersion = 0;

	/*
	 * 最新版本
	 */
	public $latestVersion = null;

	/*
	 * 最新版本地址	
	 */
	public $latestUpdate = null;

	/*
	 * 更新服务器地址（带/）
	 */
	public $updateUrl = 'https://auths.yum6.cn/';

	/*
	 * 服务器上的版本文件名称
	 */
	public $updateIni = 'update_check.html';

	/*
	 * 临时下载目录
	 */
	public $tempDir = 'temp/';

	/*
	 * 安装完成后删除临时目录
	 */
	public $removeTempDir = true;

	/*
	 * 安装目录
	 */
	public $installDir = '';

	public $sql_file = '';

	/**
	 * 创建新实例
	 * @param [type]  $installDir 安装路径
	 * @param boolean $log        是否启用日志
	 */
	public function __construct($installDir, $log = false)
	{
		ini_set('max_execution_time', 600);
		$this->_log = $log;
		$this->installDir = $installDir;
		$this->arrContextOptions = [
			'ssl' => [
				'verify_peer' => false,
				'verify_peer_name' => false,
			]
		];
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

	/*
	 * 获取错误信息
	 *
	 * @return string 最后的错误
	 */
	public function getLastError()
	{
		if (!is_null($this->_lastError))
			return $this->_lastError;
		else
			return '日志尚未开启！';
	}

	/**
	 * 删除指定路径下所有文件
	 * @param  [type] $dir 路径
	 */
	private function _removeDir($dir)
	{
		if (is_dir($dir)) {
			$objects = scandir($dir);
			foreach ($objects as $object) {
				if ($object != "." && $object != "..") {
					if (filetype($dir . "/" . $object) == "dir")
						// 是路径
						$this->_removeDir($dir . "/" . $object);
					else
						// 删除文件
						unlink($dir . "/" . $object);
				}
			}
			reset($objects);
			rmdir($dir);
		}
	}

	/*
	 * 检查新版本
	 *
	 * @return string 最新版本号
	 */
	public function checkUpdate()
	{
		$this->log('检查更新. . .');

		$updateFile = $this->updateUrl . $this->updateIni;
		$update = file_get_contents($updateFile, false, stream_context_create($this->arrContextOptions));

		if ($update === false) {
			$this->log('无法获取更新文件 -->' . $updateFile);
			return false;
		} else {
			$updates = json_decode($update, 1);
			// var_dump($updateFile, $updates);
			// exit;
			$versions = isset($updates['data']['newversion']) ? $updates['data']['newversion'] : '';

			//$versions = parse_ini_string($update, true);
			if ($versions && isset($updates['data']['newversion'])) {
				$this->sql_file = isset($updates['data']['sqldownloadurl']) ? $updates['data']['sqldownloadurl'] : '';
				$this->log('最新版本号 -->' . $versions);
				$this->latestVersion = $versions;
				$this->latestUpdate = isset($updates['data']['downloadurl']) ? $updates['data']['downloadurl'] : '';
				return $versions;
			} else {
				$this->log('没有发现可用的新版本！');
				return false;
			}
		}
	}

	/*
	 * 下载更新文件
	 *
	 * @param string $updateUrl 更新文件URL
	 * @param string $updateFile 下载文件保存目录
	 */
	public function downloadUpdate($updateUrl, $updateFile)
	{
		$this->log('正在下载更新...');

		$update = file_get_contents($updateUrl, false, stream_context_create($this->arrContextOptions));

		if ($update === false) {
			$this->log('无法下载更新 -->' . $updateUrl);
			return false;
		}

		$handle = fopen($updateFile, 'w');

		if (!$handle) {
			$this->log('无法保存更新文件 -->' . $updateFile);
			return false;
		}

		if (!fwrite($handle, $update)) {
			$this->log('无法写入更新文件 -->' . $updateFile);
			return false;
		}

		fclose($handle);

		return true;
	}

	/*
	 * 安装更新文件
	 *
	 * @param string $updateFile 更新文件路径
	 */
	private function install($updateFile)
	{
		$zip = zip_open($updateFile);

		while ($file = zip_read($zip)) {
			$filename = zip_entry_name($file);
			$foldername = $this->installDir . dirname($filename);

			$this->log('更新中 -->' . $filename);

			if (!is_dir($foldername)) {
				if (!mkdir($foldername, 0755, true)) {
					$this->log('无法创建目录 -->' . $foldername);
				}
			}

			$contents = zip_entry_read($file, zip_entry_filesize($file));

			//跳过目录
			if (substr($filename, -1, 1) == '/')
				continue;

			//写入文件
			if (file_exists($this->installDir . $filename)) {
				if (!is_writable($this->installDir . $filename)) {
					$this->log('无法更新 -->' . $this->installDir . $filename . ' 不可写入!');
					return false;
				}
			} else {
				$this->log('文件 -->' . $this->installDir . $filename . ' 不存在!');
				$new_file = fopen($this->installDir . $filename, "w") or $this->log('文件 -->' . $this->installDir . $filename . ' 不能创建!');
				fclose($new_file);
				$this->log('文件 -->' . $this->installDir . $filename . ' 创建成功.');
			}

			$updateHandle = fopen($this->installDir . $filename, 'w');

			if (!$updateHandle) {
				$this->log('无法更新文件 -->' . $this->installDir . $filename);
				return false;
			}

			if (!fwrite($updateHandle, $contents)) {
				$this->log('无法写入文件 -->' . $this->installDir . $filename);
				return false;
			}
			fclose($updateHandle);
		}

		zip_close($zip);

		if ($this->removeTempDir) {
			$this->log('临时目录 -->' . $this->tempDir . ' 被删除');
			$this->_removeDir($this->tempDir);
		}

		$this->log('更新 -->' . $this->latestVersion . ' 安装完成');

		return true;
	}

	/*
	 * 更新最新版本
	 */
	public function update()
	{
		//检查最新版本
		if ((is_null($this->latestVersion)) or (is_null($this->latestUpdate))) {
			$this->checkUpdate();
		}

		if ((is_null($this->latestVersion)) or (is_null($this->latestUpdate))) {
			return false;
		}

		//开始更新
		if ($this->latestVersion > $this->currentVersion) {
			$this->log('开始更新....');

			//排除文件
			if ($this->tempDir[strlen($this->tempDir) - 1] != '/');
			$this->tempDir = $this->tempDir . '/';

			if ((!is_dir($this->tempDir)) and (!mkdir($this->tempDir, 0777, true))) {
				$this->log('临时目录 -->' . $this->tempDir . ' 不存在并且无法创建!');
				return false;
			}

			if (!is_writable($this->tempDir)) {
				$this->log('临时目录 -->' . $this->tempDir . ' 不可写入!');
				return false;
			}

			$updateFile = $this->tempDir . $this->latestVersion . '.zip';
			$updateUrl = $this->latestUpdate;

			//下载更新
			if (!is_file($updateFile)) {
				if (!$this->downloadUpdate($updateUrl, $updateFile)) {
					$this->log('无法下载更新!');
					return false;
				}

				$this->log('最新更新下载 -->' . $updateFile);
			} else {
				$this->log('最新更新下载到 -->' . $updateFile);
			}

			//解压
			return $this->install($updateFile);
		} else {
			$this->log('没有可用更新！');
			return false;
		}
	}

	/**
	 * 替换旧文件
	 */
	public function replaceupdate()
	{
		if (is_dir($this->installDir)) {
			// @unlink($this->installDir . 'Bty-update/public/favicon.ico');
			// @unlink($this->installDir . 'Bty-update/application/database.php');
		} else {
			$this->log('文件效验失败！');
			return false;
		}

		// $this->recurse_rename($this->installDir . 'Bty-update', $this->installDir);
		return true;
	}

	/**
	 * 移动程序文件替换旧版本
	 * @param  [type] $src 原目录
	 * @param  [type] $dst 移动到的目录 
	 */
	private function recurse_rename($src, $dst)
	{
		$dir = opendir($src);
		mkdir($dst);
		while (false !== ($file = readdir($dir))) {
			if (($file != '.') && ($file != '..')) {
				if (is_dir($src . '/' . $file)) {
					$this->recurse_rename($src . '/' . $file, $dst . '/' . $file);
				} else {
					rename($src . '/' . $file, $dst . '/' . $file);
				}
			}
		}
		closedir($dir);
	}

	/**
	 * curl模拟提交
	 * @param  [type]  $url          访问的URL
	 * @param  string  $post         post数据(不填则为GET)
	 * @param  string  $referer      自定义来路
	 * @param  string  $cookie       提交的$cookies
	 * @param  integer $returnCookie 是否返回$cookies
	 * @param  string  $ua           自定义UA
	 * @return [type]                [description]
	 */
	private function curl_request($url, $post = '', $referer = '', $cookie = '', $returnCookie = 0, $ua = 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:43.0) Gecko/20100101 Firefox/43.0')
	{
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_USERAGENT, $ua);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($curl, CURLOPT_AUTOREFERER, 1);
		curl_setopt($curl, CURLOPT_TIMEOUT, 60);
		curl_setopt($curl, CURLOPT_REFERER, $referer);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		$httpheader[] = "text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8";
		$httpheader[] = "Accept-Encoding:gzip, deflate";
		$httpheader[] = "Accept-Language:zh-CN,zh;q=0.9";
		$httpheader[] = "Connection:close";
		curl_setopt($curl, CURLOPT_HTTPHEADER, $httpheader);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		if ($post) {
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post));
		}
		if ($cookie) {
			curl_setopt($curl, CURLOPT_COOKIE, $cookie);
		}
		curl_setopt($curl, CURLOPT_HEADER, $returnCookie);
		curl_setopt($curl, CURLOPT_TIMEOUT, 10);
		curl_setopt($curl, CURLOPT_ENCODING, "gzip");
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$data = curl_exec($curl);
		if (curl_errno($curl)) {
			return curl_error($curl);
		}
		curl_close($curl);
		if ($returnCookie) {
			list($header, $body) = explode("\r\n\r\n", $data, 2);
			preg_match_all("/Set\-Cookie:([^;]*);/", $header, $matches);
			$info['cookie']  = substr($matches[1][1], 1);
			$info['content'] = $body;
			return $info;
		} else {
			return $data;
		}
	}
}
