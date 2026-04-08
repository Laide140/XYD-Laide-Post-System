<?php
require_once('config.php');

session_start();
header('Content-Type: application/json');

// 获取POST数据
$centersenderpost = $_POST['centersenderpost'] ?? '';
$action = $_POST['action'] ?? '';  // 用户操作类型
$info = $_POST['info'] ?? '';
$member = $_POST['member'] ?? '';
$mail = $_POST['mail'] ?? '';

// 构建内部描述内容
$internal_description = "机构：".$centersenderpost."<br />操作：".$action."<br />详情：".$info."<br />人员：".$member;

// 转义特殊字符
$escaped_internal_description = json_encode($internal_description, JSON_UNESCAPED_UNICODE);
$escaped_internal_description = substr($escaped_internal_description, 1, -1);

// 连接数据库
$conn = new mysqli($server_hostname, $server_username, $server_password, $server_database);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => '数据库连接失败']);
    exit;
}

// 准备内部记录
$new_record = '{"time":"' . date("Y/m/d H:i:s") . '", "description":"' . $escaped_internal_description . '"}';

// 查询现有内部记录
$sql = "SELECT mailinfo FROM track WHERE mail = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $mail);
$stmt->execute();
$result = $stmt->get_result();

$db_action = '';  // 用于存储数据库操作类型

if ($result->num_rows > 0) {
    // 记录存在
    $row = $result->fetch_assoc();
    $old_mailinfo = $row['mailinfo'];
    
    if (!empty($old_mailinfo)) {
        $new_mailinfo = $old_mailinfo . ", " . $new_record;
    } else {
        $new_mailinfo = $new_record;
    }
    
    // 更新记录
    $update_sql = "UPDATE track SET mailinfo = ? WHERE mail = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ss", $new_mailinfo, $mail);
    $update_stmt->execute();
    $update_stmt->close();
    
    $db_action = 'updated';
    
} else {
    // 记录不存在，插入新记录
    $insert_sql = "INSERT INTO track (mail, mailinfo) VALUES (?, ?)";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("ss", $mail, $new_record);
    $insert_stmt->execute();
    $insert_stmt->close();
    
    $db_action = 'inserted';
}

// 外网查询 - 只在特定用户操作类型时执行
$user_action = $_POST['action'] ?? '';  // 保存用户操作类型

if($user_action == "到达投递机构" || $user_action == "投递邮件接收-下段" || $user_action == "投递结果反馈-妥投") {
    
    // 构建外网描述内容
    $web_description = "";
    if($user_action == "到达投递机构") {
        $web_description = "邮件到达【".$centersenderpost."】";
    } elseif($user_action == "投递邮件接收-下段") {
        $web_description = "邮件正在派送中，请耐心等待，保持电话畅通，准备签收，如有疑问请电联快递员或揽投部。";
    } elseif($user_action == "投递结果反馈-妥投") {
        $web_description = "您的邮件已签收，如有疑问请电联快递员。有事呼叫我，少一次投诉，多一份感恩。邮政部门将全心呵护您的所托。";
    }
    
    // 转义外网描述
    $escaped_web_description = json_encode($web_description, JSON_UNESCAPED_UNICODE);
    $escaped_web_description = substr($escaped_web_description, 1, -1);
    
    // 准备外网记录
    $new_web_record = '{"time":"' . date("Y/m/d H:i:s") . '", "description":"' . $escaped_web_description . '"}';
    
    // 查询外网现有记录
    $web_sql = "SELECT mailinfo FROM webtrack WHERE mail = ? LIMIT 1";
    $web_stmt = $conn->prepare($web_sql);
    $web_stmt->bind_param("s", $mail);
    $web_stmt->execute();
    $web_result = $web_stmt->get_result();
    
    if ($web_result->num_rows > 0) {
        // 记录存在
        $web_row = $web_result->fetch_assoc();
        $old_web_mailinfo = $web_row['mailinfo'];
        
        if (!empty($old_web_mailinfo)) {
            $new_web_mailinfo = $old_web_mailinfo . ", " . $new_web_record;
        } else {
            $new_web_mailinfo = $new_web_record;
        }
        
        // 更新外网记录
        $update_web_sql = "UPDATE webtrack SET mailinfo = ? WHERE mail = ?";
        $update_web_stmt = $conn->prepare($update_web_sql);
        $update_web_stmt->bind_param("ss", $new_web_mailinfo, $mail);
        $update_web_stmt->execute();
        $update_web_stmt->close();
        
    } else {
        // 记录不存在，插入新记录
        $insert_web_sql = "INSERT INTO webtrack (mail, mailinfo) VALUES (?, ?)";
        $insert_web_stmt = $conn->prepare($insert_web_sql);
        $insert_web_stmt->bind_param("ss", $mail, $new_web_record);
        $insert_web_stmt->execute();
        $insert_web_stmt->close();
    }
    
    $web_stmt->close();
}

// 返回结果
echo json_encode([
    'success' => true,
    'action' => $db_action,
    'user_action' => $user_action,  // 可选：返回用户操作类型
    'message' => '操作完成'
]);

if (isset($stmt)) {
    $stmt->close();
}
$conn->close();
?>