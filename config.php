<?php
// อ่านค่า Config จาก Railway Environment Variables
$privateUrl = getenv('MYSQL_PRIVATE_URL') ?: getenv('MYSQL_URL');

if ($privateUrl) {
    $databaseUrl = parse_url($privateUrl);
    if (!$databaseUrl || ($databaseUrl['scheme'] ?? '') !== 'mysql' || empty($databaseUrl['host']) || empty($databaseUrl['path'])) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "รูปแบบ MYSQL_PRIVATE_URL ไม่ถูกต้อง"]);
        exit;
    }

    $host = $databaseUrl['host'];
    $user = rawurldecode($databaseUrl['user'] ?? '');
    $pass = rawurldecode($databaseUrl['pass'] ?? '');
    $db   = getenv('MYSQLDATABASE') ?: getenv('MYSQL_DATABASE') ?: rawurldecode(ltrim($databaseUrl['path'], '/'));
    $port = $databaseUrl['port'] ?? 3306;
} else {
    $host = getenv('MYSQLHOST') ?: 'localhost';
    $user = getenv('MYSQLUSER') ?: 'root';
    $pass = getenv('MYSQLPASSWORD') ?: '';
    $db   = getenv('MYSQLDATABASE') ?: getenv('MYSQL_DATABASE') ?: 'railway';
    $port = getenv('MYSQLPORT') ?: '3306';
}

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    error_log($e->getMessage());
    echo json_encode(["status" => "error", "message" => "ไม่สามารถเชื่อมต่อฐานข้อมูลได้"]);
    exit;
}
?>
