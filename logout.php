<?php
// 启用会话（如果需要）
session_start();

// 设置返回类型为JSON
header('Content-Type: application/json');

// 清除Cookie
if(isset($_COOKIE["username"])) {
    setcookie("username", "", time() - 3600, "/");
}

// 清除会话
session_unset();
session_destroy();

echo json_encode(['success' => true]);
?>