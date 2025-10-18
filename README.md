Workerman + Laravel + Vue.js 实战聊天室
=================

版本
======================

OS windows

Laravel Framework 12.31.1

PHP 8.2.9   

启动
======================

双击项目 laravel_work\socket\GatewayWorker 目录下的 `start_for_win.bat` 脚本启动socket服务

如果启动内置的 PHP 开发服务器php artisan serve无效，则在根目录下新建 start-server.php 文件
```shell
<?php
// 自定义服务器启动脚本
$host = '127.0.0.1';
$port = 8080;
$publicDir = __DIR__ . '/public';

echo "启动 Laravel 开发服务器: http://{$host}:{$port}\n";

// 构建服务器命令
$command = sprintf(
    'php -S %s:%d -t %s',
    $host,
    $port,
    escapeshellarg($publicDir)
);

// 执行命令
passthru($command);
```

开发模式下，Vite 需要一个持续运行的开发服务器来提供资源，并保持终端运行.
```shell
 npm run dev
 ```

监视文件变化自动编译
```shell
npm run watch
```

启动laravel服务器
```shell
php start-serve.php
```

登陆账号进入home目录 http://127.0.0.1:8080/home