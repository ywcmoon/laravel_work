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