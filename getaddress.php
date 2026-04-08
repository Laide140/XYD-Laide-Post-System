<?php
require_once('config.php');

session_start();
header('Content-Type: application/json');

// 连接数据库
$conn = new mysqli($server_hostname, $server_username, $server_password, $server_database);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => '数据库连接失败']);
    exit;
}

// 获取手机号参数
$phone = isset($_GET['phone']) ? trim($_GET['phone']) : '';

// 验证手机号
if (empty($phone)) {
    echo json_encode([
        'success' => false,
        'message' => '请输入手机号'
    ]);
    exit;
}

// 准备响应数组
$response = [
    'success' => true,
    'message' => '',
    'data' => null,
    'meta' => [
        'query_phone' => $phone,
        'timestamp' => date('Y-m-d H:i:s'),
        'count' => 0
    ]
];

try {
    // 修改：精确查询所有匹配的手机号记录（移除 LIMIT 1）
    $sql = "SELECT id, name, address, postcode, phone 
            FROM address 
            WHERE phone = ?
            GROUP BY name, address, postcode, phone
            ORDER BY id DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // 获取查询结果 - 现在返回所有匹配的记录
    if ($result->num_rows > 0) {
        $records = [];
        while ($row = $result->fetch_assoc()) {
            $records[] = [
                'id' => (int)$row['id'],
                'name' => $row['name'],
                'address' => $row['address'],
                'postcode' => $row['postcode'],
                'phone' => $row['phone']
            ];
        }
        $response['data'] = $records;  // 现在是数组的数组
        $response['message'] = '找到 ' . $result->num_rows . ' 条记录';
        $response['meta']['found'] = true;
        $response['meta']['count'] = $result->num_rows;
    } else {
        $response['data'] = [];  // 空数组表示没有记录
        $response['message'] = '未找到该手机号的记录';
        $response['meta']['found'] = false;
        $response['meta']['count'] = 0;
    }
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = '查询失败：' . $e->getMessage();
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    $conn->close();
}

// 输出JSON
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>