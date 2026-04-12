<?php
require_once('config.php');

// 启用会话（如果需要）
session_start();

// 设置返回类型为JSON
header('Content-Type: application/json');

// 获取POST数据
$mail = $_POST['mail'];
$u = $_POST['u'];
$p = $_POST['p'];


// 连接数据库（确保config.php已正确配置）
$conn = new mysqli($server_hostname, $server_username, $server_password, $server_database);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => '数据库连接失败']);
    exit;
}

$checksql = "SELECT password FROM users WHERE username = ?";
$checkstmt = $conn->prepare($checksql);
$checkstmt->bind_param("s", $u);
$checkstmt->execute();
$checkresult = $checkstmt->get_result();

if($checkresult->num_rows > 0) {
    $checkinfo = $checkresult->fetch_assoc();
    if($checkinfo['password'] == $p) {
        // 查询
        $sql = "SELECT mailinfo FROM track WHERE mail = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $mail);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($result->num_rows > 0) {
            $mailinfo = $result->fetch_assoc();
            echo json_encode(['success' => true, 'mailinfo' => "{\"mailinfo\":[".$mailinfo['mailinfo']."]}"]);
        }
        
        $stmt->close();
    }
    else {
        echo json_encode(['success' => true, 'message' => "用户校验出错"]);
    }
}

$checkstmt->close();
$conn->close();
?>