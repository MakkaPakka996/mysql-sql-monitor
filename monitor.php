<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

$host = "127.0.0.1";
$user = "root";
$pass = "123456";
$dbname = "wx_novel";   // 这里写你的业务库也可以

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    echo json_encode(["error" => "DB connect failed: " . $conn->connect_error]);
    exit;
}

$sql = "
SELECT event_time, argument
FROM mysql.general_log
WHERE command_type = 'Query'
  AND argument NOT LIKE '%mysql.general_log%'
ORDER BY event_time DESC
LIMIT 100
";

$res = $conn->query($sql);

if (!$res) {
    echo json_encode([
        "error" => $conn->error,
        "sql" => $sql
    ]);
    exit;
}

$data = [];
while ($row = $res->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);