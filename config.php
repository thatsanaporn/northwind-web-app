<?php
declare(strict_types=1);

/**
 * Railway MySQL connection and first-run import of the supplied Northwind dump.
 * Credentials must be supplied by Railway environment variables; never commit them.
 */
function northwindDatabaseConfig(): array
{
    $url = getenv('DATABASE_URL') ?: getenv('MYSQL_PRIVATE_URL') ?: getenv('MYSQL_URL') ?: '';
    if ($url !== '') {
        $parts = parse_url($url);
        if (!is_array($parts) || empty($parts['host'])) {
            throw new RuntimeException('DATABASE_URL / MYSQL_URL is invalid.');
        }

        return [
            'host' => $parts['host'],
            'port' => $parts['port'] ?? 3306,
            'user' => rawurldecode($parts['user'] ?? ''),
            'pass' => rawurldecode($parts['pass'] ?? ''),
            'name' => getenv('MYSQLDATABASE') ?: getenv('MYSQL_DATABASE') ?: rawurldecode(ltrim($parts['path'] ?? '', '/')),
        ];
    }

    return [
        'host' => getenv('MYSQLHOST') ?: getenv('DB_HOST') ?: '127.0.0.1',
        'port' => getenv('MYSQLPORT') ?: getenv('DB_PORT') ?: '3306',
        'user' => getenv('MYSQLUSER') ?: getenv('DB_USER') ?: 'root',
        'pass' => getenv('MYSQLPASSWORD') ?: getenv('DB_PASS') ?: '',
        'name' => getenv('MYSQLDATABASE') ?: getenv('MYSQL_DATABASE') ?: getenv('DB_NAME') ?: 'db_northwind_cpe2204',
    ];
}

/** Split a MySQL dump into individual statements, preserving semicolons in quoted values. */
function northwindSqlStatements(string $sql): array
{
    $sql = preg_replace('~/\*.*?\*/~s', '', $sql) ?? $sql;
    $statements = [];
    $statement = '';
    $quote = null;
    $escaped = false;
    $length = strlen($sql);

    for ($i = 0; $i < $length; $i++) {
        $char = $sql[$i];
        $next = $i + 1 < $length ? $sql[$i + 1] : '';

        if ($quote === null && ($char === '#' || ($char === '-' && $next === '-' && ($i + 2 >= $length || ctype_space($sql[$i + 2]))))) {
            while ($i < $length && $sql[$i] !== "\n") $i++;
            $statement .= "\n";
            continue;
        }

        if ($quote !== null) {
            $statement .= $char;
            if ($escaped) {
                $escaped = false;
            } elseif ($char === "\\" && $quote !== '`') {
                $escaped = true;
            } elseif ($char === $quote) {
                if ($next === $quote) {
                    $statement .= $next;
                    $i++;
                } else {
                    $quote = null;
                }
            }
            continue;
        }

        if ($char === "'" || $char === '"' || $char === '`') {
            $quote = $char;
            $statement .= $char;
        } elseif ($char === ';') {
            if (trim($statement) !== '') $statements[] = trim($statement);
            $statement = '';
        } else {
            $statement .= $char;
        }
    }

    if (trim($statement) !== '') $statements[] = trim($statement);
    return $statements;
}

function importNorthwindDump(PDO $pdo): void
{
    $path = __DIR__ . '/dbNorthwind.sql';
    $sql = is_file($path) ? file_get_contents($path) : false;
    if ($sql === false || trim($sql) === '') {
        throw new RuntimeException('The required dbNorthwind.sql file is missing or empty.');
    }

    foreach (northwindSqlStatements($sql) as $statement) {
        $pdo->exec($statement);
    }
}

try {
    $dbConfig = northwindDatabaseConfig();
    if ($dbConfig['name'] === '') throw new RuntimeException('Set MYSQLDATABASE or provide a database name in DATABASE_URL.');

    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $dbConfig['host'], $dbConfig['port'], $dbConfig['name']);
    $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_TIMEOUT => 10,
    ]);

    $exists = $pdo->query("SHOW TABLES LIKE 'tb_products'")->fetchColumn();
    if (!$exists) {
        if ((int)$pdo->query("SELECT GET_LOCK('northwind-dbNorthwind-bootstrap', 30)")->fetchColumn() !== 1) {
            throw new RuntimeException('Timed out waiting for Northwind database initialization.');
        }
        try {
            // Another first request may have initialized the database while this request waited.
            $exists = $pdo->query("SHOW TABLES LIKE 'tb_products'")->fetchColumn();
            if (!$exists) importNorthwindDump($pdo);
        } finally {
            $pdo->query("SELECT RELEASE_LOCK('northwind-dbNorthwind-bootstrap')");
        }
    }

    $requiredTables = ['tb_products', 'tb_categories', 'tb_suppliers'];
    foreach ($requiredTables as $table) {
        if (!$pdo->query("SHOW TABLES LIKE " . $pdo->quote($table))->fetchColumn()) {
            throw new RuntimeException("Northwind table {$table} is missing after initialization.");
        }
    }
    $productColumns = $pdo->query("SHOW COLUMNS FROM `tb_products`")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('i_UnitsInStock', $productColumns, true)) {
        throw new RuntimeException('The supplied schema is missing tb_products.i_UnitsInStock.');
    }
} catch (Throwable $e) {
    error_log('Northwind database initialization failed: ' . $e->getMessage());
    http_response_code(500);
    if (!headers_sent()) header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถเชื่อมต่อหรือเตรียมฐานข้อมูล Northwind ได้'], JSON_UNESCAPED_UNICODE);
    exit;
}
