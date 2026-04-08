<?php

require_once('config.php');

// 启用会话
session_start();

// 连接数据库（用于权限检查）
$conn = new mysqli($server_hostname, $server_username, $server_password, $server_database);
if ($conn->connect_error) {
    // 如果是 POST 请求，返回 JSON
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => '数据库连接失败']);
        exit;
    } else {
        die("数据库连接失败");
    }
}

// 处理注册请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 设置返回类型为JSON
    header('Content-Type: application/json');
    
    $enableQPT = $_POST['QPT'] ?? '';
    $enableOS = $_POST['OS'] ?? '';
    $user = $_POST['username'] ?? '';
    
    $update_sql = "UPDATE users SET enableQPT = ?, enableOS = ? WHERE username = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("sss", $enableQPT, $enableOS, $user);
    
    if($update_stmt->execute()) {
        echo json_encode(['status' => true]);
    }
    
    $update_stmt->close();
    $conn->close();
    exit;
}
?>