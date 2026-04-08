<?php
require_once('config.php');

// 启用会话（如果需要）
session_start();

// 设置返回类型为JSON
header('Content-Type: application/json');

// 获取POST数据
$username = $_POST['username'];

// 连接数据库（确保config.php已正确配置）
$conn = new mysqli($server_hostname, $server_username, $server_password, $server_database);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => '数据库连接失败']);
    exit;
}

// 查询
$sql = "SELECT enableQPT, enableOS FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows > 0) {
    $perms = $result->fetch_assoc();
    echo json_encode(['canQPT' => $perms['enableQPT'] ? "checked" : "", 'canOS' => $perms['enableOS'] ? "checked" : ""]);
}

$stmt->close();
$conn->close();
?>