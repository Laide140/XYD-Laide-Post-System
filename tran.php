<?php
require_once('config.php');

session_start();
header('Content-Type: application/json');

// 获取POST数据
$centersenderpost = $_POST['centersenderpost'] ?? '';
$centertopost = $_POST['centertopost'] ?? '';
$centernum = $_POST['centernum'] ?? '';
$centercode = $_POST['centercode'] ?? '';
$postpath = $_POST['postpath'] ?? '';
$action = $_POST['action'] ?? '';
$member = $_POST['member'] ?? '';
$mail = $_POST['mail'] ?? '';

// 构建描述内容 - 内部使用
if($action == "揽投封发") $internal_description = "机构：".$centersenderpost."<br />操作：".$action."<br />详情：总包寄达局:".$centertopost.",总包号码:".$centernum.",总包条码:".$centercode."<br />人员：".$member;
if($action == "揽投发运/封车") $internal_description = "机构：".$centersenderpost."<br />操作：".$action."<br />详情：发送对象:".$postpath.",下一站:".$centertopost."<br />人员：".$member;
if($action == "处理中心解车") $internal_description =  "机构：".$centersenderpost."<br />操作：".$action."<br />详情：接收对象:".$postpath."<br />人员：".$member;
if($action == "邮件到达处理中心") $internal_description = "机构：".$centersenderpost."<br />操作：".$action."<br />详情：自动生成"."<br />人员：".$member;
if($action == "扫描封发") $internal_description = "机构：".$centersenderpost."<br />操作：".$action."<br />详情：总包寄达局:".$centertopost.",总包号码:".$centernum.",总包条码:".$centercode."<br />人员：".$member;
if($action == "处理中心扫描配发") $internal_description = "机构：".$centersenderpost."<br />操作：".$action."<br />详情：配发对象:邮路 ".$postpath.",下一站:".$centertopost."<br />人员：".$member;
if($action == "邮件离开处理中心") $internal_description = "机构：".$centersenderpost."<br />操作：".$action."<br />详情：自动生成；邮件离开处理中心,下一站:".$centertopost."<br />人员：".$member;
if($action == "处理中心封车") $internal_description =  "机构：".$centersenderpost."<br />操作：".$action."<br />详情：发送对象:".$postpath.",下一站:".$centertopost."<br />人员：".$member;

// 转义特殊字符
$escaped_internal_description = json_encode($internal_description, JSON_UNESCAPED_UNICODE);
$escaped_internal_description = substr($escaped_internal_description, 1, -1);

// 连接数据库
$conn = new mysqli($server_hostname, $server_username, $server_password, $server_database);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => '数据库连接失败']);
    exit;
}

// 准备新记录 - 内部记录
$new_record = '{"time":"' . date("Y/m/d H:i:s") . '", "description":"' . $escaped_internal_description . '"}';

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
        $new_mailinfo = $old_mailinfo . ", " . $new_record;
    } else {
        $new_mailinfo = $new_record;
    }
    
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

// 外网查询 - 只处理特定操作
if($action == "揽投发运/封车" || $action == "扫描封发" || $action == "邮件到达处理中心" || $action == "邮件离开处理中心") {
    
    // 构建外网描述内容
    $web_description = "";
    if($action == "揽投发运/封车") $web_description = "邮件离开【".$centersenderpost."】，正在发往【".$centertopost."】";
    if($action == "扫描封发") $web_description = "邮件已在【".$centersenderpost."】完成分拣，准备发出";
    if($action == "邮件到达处理中心") $web_description = "邮件到达【".$centersenderpost."】";
    if($action == "邮件离开处理中心") $web_description = "邮件离开【".$centersenderpost."】";
    
    // 转义外网描述
    $escaped_web_description = json_encode($web_description, JSON_UNESCAPED_UNICODE);
    $escaped_web_description = substr($escaped_web_description, 1, -1);
    
    // 准备外网记录
    $web_record = '{"time":"' . date("Y/m/d H:i:s") . '", "description":"' . $escaped_web_description . '"}';
    
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
            $new_web_mailinfo = $old_web_mailinfo . ", " . $web_record;
        } else {
            $new_web_mailinfo = $web_record;
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
        $insert_web_stmt->bind_param("ss", $mail, $web_record);
        $insert_web_stmt->execute();
        $insert_web_stmt->close();
    }
    
    $web_stmt->close();
}

// 返回结果
echo json_encode([
    'success' => true,
    'action' => $db_action,
    'message' => '操作完成'
]);

if (isset($stmt)) {
    $stmt->close();
}
$conn->close();
?>