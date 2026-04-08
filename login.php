<?php
require_once('config.php');

// 启用会话（如果需要）
session_start();

// 设置返回类型为JSON
header('Content-Type: application/json');

// 获取POST数据
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

// 这里应该添加您的数据库验证逻辑
// 示例：验证用户名密码
if(empty($username) || empty($password)) {
    echo json_encode(['success' => false, 'message' => '用户名和密码不能为空']);
    exit;
}

// 连接数据库（确保config.php已正确配置）
$conn = new mysqli($server_hostname, $server_username, $server_password, $server_database);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => '数据库连接失败']);
    exit;
}

// 查询用户（示例，请根据您的实际表结构调整）
$sql = "SELECT id, username FROM users WHERE username = ? AND password = ?";
$stmt = $conn->prepare($sql);
$hashed_password = md5($password); // 实际使用中应该使用更安全的哈希方式
$stmt->bind_param("ss", $username, $hashed_password);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    
    // 设置Cookie（3小时有效期）
    setcookie("username", $user['username'], time() + (60 * 60 * 3), "/");
    
    echo json_encode(['success' => true, 'username' => $user['username']]);
} else {
    echo json_encode(['success' => false, 'message' => '用户名或密码错误']);
}

$stmt->close();
$conn->close();
?>