@echo off
echo 正在启动 Laravel 开发服务器...
set PHP_PATH=C:\php\php.exe
set ARTISAN=D:\work\laravel_work\artisan

%PHP_PATH% %ARTISAN% serve --host=127.0.0.1 --port=8080
pause