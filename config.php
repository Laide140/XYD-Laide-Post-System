<?php
// 安全配置
error_reporting(0);
ini_set('display_errors', 0);

header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");

// 数据库配置
$server_hostname = "localhost";
$server_username = "your_username";
$server_password = "your_password";
$server_database = "your_dbname";

// 安全密钥
define('SECURITY_KEY', 'your_secure_random_key_here_change_this');
?>
