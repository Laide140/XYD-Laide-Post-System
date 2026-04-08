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
    
    // 获取POST数据
    $password = $_POST['password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $headimg = $_POST['headimg'] ?? '';
    
    // 检查Cookie是否存在
    if (!isset($_COOKIE['username'])) {
        echo json_encode(['success' => false, 'message' => '用户未登录']);
        $conn->close();
        exit;
    }
    
    $username = $_COOKIE['username'];
    
    if((!empty($password) && !empty($new_password)) && empty($headimg)) {
        // 验证输入
        if (empty($password) || empty($new_password)) {
            echo json_encode(['success' => false, 'message' => '密码不能为空']);
            $conn->close();
            exit;
        }
        
        
        // 检查旧密码是否正确
        $check_sql = "SELECT id FROM users WHERE username = ? AND password = ?";
        $check_stmt = $conn->prepare($check_sql);
        $hashed_password = md5($password);
        $check_stmt->bind_param("ss", $username, $hashed_password);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows === 0) {
            // 旧密码错误
            echo json_encode(['success' => false, 'message' => '原密码错误']);
            $check_stmt->close();
            $conn->close();
            exit;
        }
        
        $check_stmt->close();
        
        // 新密码MD5加密
        $hashed_new_password = md5($new_password);
        
        // 更新密码（注意：应该只更新当前用户的密码）
        $update_sql = "UPDATE users SET password = ? WHERE username = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ss", $hashed_new_password, $username);
        
        if ($update_stmt->execute()) {
            echo json_encode(['success' => true, 'message' => '密码更新成功', 'username' => $username]);
        } else {
            echo json_encode(['success' => false, 'message' => '更新失败: ' . $conn->error]);
        }
        
        $update_stmt->close();
    }
    else if(!empty($headimg)) {
        $update_sql = "UPDATE users SET headimg = ? WHERE username = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ss", $headimg, $username);
        
        if ($update_stmt->execute()) {
            echo json_encode(['success' => true, 'message' => '头像更新成功', 'username' => $username]);
        } else {
            echo json_encode(['success' => false, 'message' => '更新失败: ' . $conn->error]);
        }
    }
    else {
        echo json_encode(['success' => false, 'message' => '如您需要修改密码，请填写原密码和新密码；如您需要修改头像，请上传头像图片！']);
        $conn->close();
        exit;
    }
}
$conn->close();
exit;
?>