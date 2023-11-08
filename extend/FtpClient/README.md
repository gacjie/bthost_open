# nicolab/php-ftp-client

A flexible FTP and SSL-FTP client for PHP.
This lib provides helpers easy to use to manage the remote files.

> This package is aimed to remain simple and light. It's only a wrapper of the FTP native API of PHP, with some useful helpers. If you want to customize some methods, you can do this by inheriting one of the [3 classes of the package](src/FtpClient).


## Install

  * Use composer: _require_ `nicolab/php-ftp-client`

  * Or use GIT clone command: `git clone git@github.com:Nicolab/php-ftp-client.git`

  * Or download the library, configure your autoloader or include the 3 files of `php-ftp-client/src/FtpClient` directory.


## Getting Started

连接到服务器FTP:

```php
$ftp = new \FtpClient\FtpClient();
$ftp->connect($host);
$ftp->login($login, $password);
```

OR

通过SSL(端口990或其他端口)连接到服务器FTP:

```php
$ftp = new \FtpClient\FtpClient();
$ftp->connect($host, true, 990);
$ftp->login($login, $password);
```

注意:连接在脚本执行结束时隐式关闭(当对象被销毁时)。
因此，除了显式重新连接外，没有必要调用' $ftp->close() '。


### Usage

上传所有文件和目录很容易:

```php
// 上传与二进制模式
$ftp->putAll($source_directory, $target_directory);

// 等于
$ftp->putAll($source_directory, $target_directory, FTP_BINARY);

// 或以ASCII模式上传
$ftp->putAll($source_directory, $target_directory, FTP_ASCII);
```

*注意:FTP_ASCII和FTP_BINARY是预定义的PHP内部常量。*

获得一个目录大小:

```php
// 当前目录的大小
$size = $ftp->dirSize();

// 给定目录的大小
$size = $ftp->dirSize('/path/of/directory');
```

计算目录中的项目:

```php
// 在当前目录中计数
$total = $ftp->count();

// 在给定目录中计数
$total = $ftp->count('/path/of/directory');

// 只计算当前目录中的“文件”
$total_file = $ftp->count('.', 'file');

// 只计算给定目录中的“文件”
$total_file = $ftp->count('/path/of/directory', 'file');

// 只计算给定目录中的“目录”
$total_dir = $ftp->count('/path/of/directory', 'directory');

// 只计算给定目录中的“符号链接”
$total_link = $ftp->count('/path/of/directory', 'link');
```

所有文件和目录的详细列表:

```php
// 扫描当前目录并返回每个项目的详细信息
$items = $ftp->scanDir();

// 扫描当前目录(递归)并返回每个项的详细信息
var_dump($ftp->scanDir('.', true));
```

Result:

	'directory#www' =>
	    array (size=10)
	      'permissions' => string 'drwx---r-x' (length=10)
	      'number'      => string '3' (length=1)
	      'owner'       => string '32385' (length=5)
	      'group'       => string 'users' (length=5)
	      'size'        => string '5' (length=1)
	      'month'       => string 'Nov' (length=3)
	      'day'         => string '24' (length=2)
	      'time'        => string '17:25' (length=5)
	      'name'        => string 'www' (length=3)
	      'type'        => string 'directory' (length=9)

	  'link#www/index.html' =>
	    array (size=11)
	      'permissions' => string 'lrwxrwxrwx' (length=10)
	      'number'      => string '1' (length=1)
	      'owner'       => string '0' (length=1)
	      'group'       => string 'users' (length=5)
	      'size'        => string '38' (length=2)
	      'month'       => string 'Nov' (length=3)
	      'day'         => string '16' (length=2)
	      'time'        => string '14:57' (length=5)
	      'name'        => string 'index.html' (length=10)
	      'type'        => string 'link' (length=4)
	      'target'      => string '/var/www/shared/index.html' (length=26)

	'file#www/README' =>
	    array (size=10)
	      'permissions' => string '-rw----r--' (length=10)
	      'number'      => string '1' (length=1)
	      'owner'       => string '32385' (length=5)
	      'group'       => string 'users' (length=5)
	      'size'        => string '0' (length=1)
	      'month'       => string 'Nov' (length=3)
	      'day'         => string '24' (length=2)
	      'time'        => string '17:25' (length=5)
	      'name'        => string 'README' (length=6)
	      'type'        => string 'file' (length=4)


所有的FTP PHP函数支持和一些改进:

```php
// 请求在FTP服务器上执行命令
$ftp->exec($command);

// 开启或关闭被动模式
$ftp->pasv(true);

// 通过FTP设置文件的权限
$ftp->chmod(0777, 'file.php');

// 删除一个目录
$ftp->rmdir('path/of/directory/to/remove');

// 删除目录(递归)
$ftp->rmdir('path/of/directory/to/remove', true);

// 创建一个目录
$ftp->mkdir('path/of/directory/to/create');

// 创建一个目录(递归)，
// 如果不存在，则自动创建子目录
$ftp->mkdir('path/of/directory/to/create', true);

// 和更多的……
```

获取远程FTP服务器的帮助信息:

```php
var_dump($ftp->help());
```

Result :

	array (size=6)
	  0 => string '214-The following SITE commands are recognized' (length=46)
	  1 => string ' ALIAS' (length=6)
	  2 => string ' CHMOD' (length=6)
	  3 => string ' IDLE' (length=5)
	  4 => string ' UTIME' (length=6)
	  5 => string '214 Pure-FTPd - http://pureftpd.org/' (length=36)


_Note:结果取决于FTP server._


### 扩展

创建您的自定义“FtpClient”。

```php
// MyFtpClient.php

/**
 * My custom FTP Client
 * @inheritDoc
 */
class MyFtpClient extends \FtpClient\FtpClient {

  public function removeByTime($path, $timestamp) {
      // your code here
  }

  public function search($regex) {
      // your code here
  }
}
```

```php
// example.php
$ftp = new MyFtpClient();
$ftp->connect($host);
$ftp->login($login, $password);

// 删除旧文件
$ftp->removeByTime('/www/mysite.com/demo', time() - 86400));

// 搜索PNG文件
$ftp->search('/(.*)\.png$/i');
```


## API doc

See the [source code](https://github.com/Nicolab/php-ftp-client/tree/master/src/FtpClient) for more details.
文档完整:blue_book:


## Testing

使用“atoum”单元测试框架进行测试。


## License

[MIT](https://github.com/Nicolab/php-ftp-client/blob/master/LICENSE) c) 2014, Nicolas Tallefourtane.


## Author

| [![Nicolas Tallefourtane - Nicolab.net](http://www.gravatar.com/avatar/d7dd0f4769f3aa48a3ecb308f0b457fc?s=64)](http://nicolab.net) |
|---|
| [Nicolas Talle](http://nicolab.net) |
| [![Make a donation via Paypal](https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=PGRH4ZXP36GUC) |
