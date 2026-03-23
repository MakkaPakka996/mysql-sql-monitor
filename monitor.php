<?php
ini_set('display_errors', 0);
ini_set('html_errors', 0);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
ob_start();

header('Content-Type: application/json; charset=utf-8');

function respond(array $payload, $status = 200)
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

set_exception_handler(function ($e) {
    respond(['error' => 'Server error: ' . $e->getMessage()], 500);
});

set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }

    throw new ErrorException($message, 0, $severity, $file, $line);
});

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error === null) {
        return;
    }

    $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
    if (!in_array($error['type'], $fatalTypes, true)) {
        return;
    }

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'error' => 'Fatal error: ' . $error['message']
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
});

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['error' => 'Only POST is allowed.'], 405);
}

$rawInput = file_get_contents('php://input');
$request = json_decode($rawInput, true);

if (!is_array($request)) {
    respond(['error' => 'Invalid JSON payload.'], 400);
}

$host = trim((string)(isset($request['host']) ? $request['host'] : ''));
$port = (int)(isset($request['port']) ? $request['port'] : 3306);
$user = trim((string)(isset($request['user']) ? $request['user'] : ''));
$pass = (string)(isset($request['pass']) ? $request['pass'] : '');
$dbname = trim((string)(isset($request['dbname']) ? $request['dbname'] : ''));
$limit = (int)(isset($request['limit']) ? $request['limit'] : 100);
$action = isset($request['action']) ? (string)$request['action'] : 'fetch';

if ($host === '' || $user === '') {
    respond(['error' => 'Host and user are required.'], 400);
}

if ($port < 1 || $port > 65535) {
    respond(['error' => 'Port must be between 1 and 65535.'], 400);
}

if ($limit < 1 || $limit > 1000) {
    respond(['error' => 'Limit must be between 1 and 1000.'], 400);
}

$conn = new mysqli($host, $user, $pass, $dbname, $port);
$conn->set_charset('utf8mb4');
$currentThreadId = (int)$conn->thread_id;
$monitorTag = '/* mysql-monitor */';

if ($action === 'enable_logging') {
    $conn->query("SET GLOBAL log_output = 'TABLE'");
    $conn->query("SET GLOBAL general_log = 'ON'");
    respond([
        'ok' => true,
        'message' => "已执行: SET GLOBAL log_output='TABLE'; SET GLOBAL general_log='ON';"
    ]);
}

if ($action === 'clear_logs') {
    $conn->query("SET GLOBAL log_output = 'TABLE'");
    $conn->query("SET GLOBAL general_log = 'OFF'");
    $conn->query("TRUNCATE TABLE mysql.general_log");
    $conn->query("SET GLOBAL general_log = 'ON'");
    respond([
        'ok' => true,
        'message' => "已清空 mysql.general_log，并重新开启日志记录。"
    ]);
}

$settings = array();
$settingsRes = $conn->query("
{$monitorTag} SELECT variable_name, variable_value
FROM performance_schema.global_variables
WHERE variable_name IN ('general_log', 'log_output')
");

while ($settingRow = $settingsRes->fetch_assoc()) {
    $settings[strtolower($settingRow['variable_name'])] = $settingRow['variable_value'];
}

$generalLog = isset($settings['general_log']) ? strtoupper($settings['general_log']) : '';
$logOutput = isset($settings['log_output']) ? strtoupper($settings['log_output']) : '';

if ($generalLog !== 'ON') {
    respond([
        'error' => "MySQL general_log 当前为 OFF，日志不会继续写入。先执行: SET GLOBAL general_log = 'ON';"
    ], 409);
}

if (strpos($logOutput, 'TABLE') === false) {
    respond([
        'error' => "MySQL log_output 当前为 {$logOutput}，monitor 只能读取 TABLE。先执行: SET GLOBAL log_output = 'TABLE';"
    ], 409);
}

$sql = "
{$monitorTag} SELECT event_time, argument
FROM mysql.general_log
WHERE command_type = 'Query'
  AND thread_id <> " . $currentThreadId . "
  AND argument IS NOT NULL
  AND argument <> ''
  AND argument NOT LIKE '" . $conn->real_escape_string($monitorTag) . "%'
  AND argument NOT LIKE 'SELECT variable_name, variable_value%performance_schema.global_variables%'
  AND argument NOT LIKE 'SHOW VARIABLES LIKE ''general_log'''
  AND argument NOT LIKE 'SHOW VARIABLES LIKE ''log_output'''
  AND argument NOT LIKE 'SET GLOBAL general_log%'
  AND argument NOT LIKE 'SET GLOBAL log_output%'
  AND argument NOT LIKE 'SET NAMES %'
  AND argument NOT LIKE 'TRUNCATE TABLE mysql.general_log%'
  AND argument NOT LIKE '%mysql.general_log%'
ORDER BY event_time DESC
LIMIT " . $limit . "
";

$res = $conn->query($sql);

$data = [];
while ($row = $res->fetch_assoc()) {
    $data[] = $row;
}

respond($data);
