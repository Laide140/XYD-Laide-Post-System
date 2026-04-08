<?php
require_once('config.php');

session_start();
header('Content-Type: application/json');

// 获取POST数据
$senderpost = $_POST['senderpost'] ?? '';
$topost = $_POST['topost'] ?? '';
$sender = $_POST['sender'] ?? '';
$senderp = $_POST['senderphone'] ?? '';
$senderpc = $_POST['senderpc'] ?? '';
$senderaddress = $_POST['senderaddress'] ?? '';
$to = $_POST['to'] ?? '';
$top = $_POST['tophone'] ?? '';
$topc = $_POST['topc'] ?? '';
$toaddress = $_POST['toaddress'] ?? '';
$g = $_POST['g'] ?? '';
$type = $_POST['type'] ?? '';
$b = $_POST['b'] ?? '';

$member = $_POST['member'] ?? '';
$mail = $_POST['mail'] ?? '';

// 连接数据库
$conn = new mysqli($server_hostname, $server_username, $server_password, $server_database);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => '数据库连接失败']);
    exit;
}

// ========== 新增：保存地址信息到address表 ==========
// 保存寄件人地址
if (!empty($senderp)) {
    $address_sql = "INSERT INTO address (name, phone, address, postcode) VALUES (?, ?, ?, ?)";
    $address_stmt = $conn->prepare($address_sql);
    $address_stmt->bind_param("ssss", $sender, $senderp, $senderaddress, $senderpc);
    $address_stmt->execute();
    $address_stmt->close();
}

// 保存收件人地址
if (!empty($top)) {
    $address_sql = "INSERT INTO address (name, phone, address, postcode) VALUES (?, ?, ?, ?)";
    $address_stmt = $conn->prepare($address_sql);
    $address_stmt->bind_param("ssss", $to, $top, $toaddress, $topc);
    $address_stmt->execute();
    $address_stmt->close();
}
// ========== 新增结束 ==========

$description = "机构：".$senderpost."<br />操作：收寄计费信息<br />详情：重量:".$g."g,标准资费:".$b."元,收件人:".$to."<br />人员：".$member;

// 转义特殊字符
// 使用 json_encode 来转义字符串，然后去掉两端的引号
$escaped_description = json_encode($description, JSON_UNESCAPED_UNICODE);
// 去掉 JSON 编码后字符串两端的双引号
$escaped_description = substr($escaped_description, 1, -1);

// 准备新记录 - 使用转义后的描述
$new_record = '{"time":"' . date("Y/m/d H:i:s") . '", "description":"' . $escaped_description . '"}';

// 查询现有记录
$sql = "SELECT mailinfo FROM track WHERE mail = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $mail);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // 记录存在
    $row = $result->fetch_assoc();
    $old_mailinfo = $row['mailinfo'];
    
    if (!empty($old_mailinfo)) {
        // 有旧数据，追加到后面
        $new_mailinfo = $old_mailinfo . ", " . $new_record;
    } else {
        // 没旧数据，直接用新数据
        $new_mailinfo = $new_record;
    }
    
    // 更新记录
    $update_sql = "UPDATE track SET mailinfo = ? WHERE mail = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ss", $new_mailinfo, $mail);
    $update_stmt->execute();
    $update_stmt->close();
    
    $action = 'updated';
    
} else {
    // 记录不存在，插入新记录
    $insert_sql = "INSERT INTO track (mail, mailinfo) VALUES (?, ?)";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("ss", $mail, $new_record);
    $insert_stmt->execute();
    $insert_stmt->close();
    
    $action = 'inserted';
}

if($type != "国内平常信函" && $type != "国内平常印刷品" && $type != "国内平常盲人读物" && $type != "国内平常商业信函")
{
    //登记外网
    $description = "邮政部门已收取快件";
    
    // 转义特殊字符
    // 使用 json_encode 来转义字符串，然后去掉两端的引号
    $escaped_description = json_encode($description, JSON_UNESCAPED_UNICODE);
    // 去掉 JSON 编码后字符串两端的双引号
    $escaped_description = substr($escaped_description, 1, -1);
    
    $new_record = '{"time":"' . date("Y/m/d H:i:s") . '", "description":"' . $escaped_description . '"}';
    
    $sql = "SELECT mailinfo FROM webtrack WHERE mail = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $mail);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // 记录存在
        $row = $result->fetch_assoc();
        $old_mailinfo = $row['mailinfo'];
        
        if (!empty($old_mailinfo)) {
            // 有旧数据，追加到后面
            $new_mailinfo = $old_mailinfo . ", " . $new_record;
        } else {
            // 没旧数据，直接用新数据
            $new_mailinfo = $new_record;
        }
        
        // 更新记录
        $update_sql = "UPDATE webtrack SET mailinfo = ? WHERE mail = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ss", $new_mailinfo, $mail);
        $update_stmt->execute();
        $update_stmt->close();
        
        $action = 'updated';
        
        } else {
        // 记录不存在，插入新记录
        $insert_sql = "INSERT INTO webtrack (mail, mailinfo) VALUES (?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("ss", $mail, $new_record);
        $insert_stmt->execute();
        $insert_stmt->close();
        
        $action = 'inserted';
    }
}

//登记详情
$description = "收寄：".$senderpost."<br />寄达：".$topost."<br /><br />寄件人：".$sender."<br />手机号：".$senderp."<br />邮编：".$senderpc."<br />地址：".$senderaddress."<br /><br />收件人：".$to."<br />手机号：".$top."<br />邮编：".$topc."<br />地址：".$toaddress."<br /><br />类型：".$type."<br />重量：".$g."g";

// 转义特殊字符
// 使用 json_encode 来转义字符串，然后去掉两端的引号
$escaped_description = json_encode($description, JSON_UNESCAPED_UNICODE);
// 去掉 JSON 编码后字符串两端的双引号
$escaped_description = substr($escaped_description, 1, -1);

$new_record = '{"time":"' . date("Y/m/d H:i:s") . '", "description":"' . $escaped_description . '"}';

$sql = "SELECT mailinfo FROM mailinfo WHERE mail = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $mail);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // 记录存在
    $row = $result->fetch_assoc();
    $old_mailinfo = $row['mailinfo'];
    
    $new_mailinfo = $new_record;
    
    // 更新记录
    $update_sql = "UPDATE mailinfo SET mailinfo = ? WHERE mail = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ss", $new_mailinfo, $mail);
    $update_stmt->execute();
    $update_stmt->close();
    
    $action = 'updated';
    
    } else {
    // 记录不存在，插入新记录
    $insert_sql = "INSERT INTO mailinfo (mail, mailinfo) VALUES (?, ?)";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("ss", $mail, $new_record);
    $insert_stmt->execute();
    $insert_stmt->close();
    
    $action = 'inserted';
}

// 返回结果
echo json_encode([
    'success' => true,
    'action' => $action,
    'message' => '操作完成'
]);

$stmt->close();
$conn->close();
?>