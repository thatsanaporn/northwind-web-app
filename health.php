<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/config.php';

try {
    $productCount = (int)$pdo->query('SELECT COUNT(*) FROM `tb_products`')->fetchColumn();
    $categoryCount = (int)$pdo->query('SELECT COUNT(*) FROM `tb_categories`')->fetchColumn();
    $supplierCount = (int)$pdo->query('SELECT COUNT(*) FROM `tb_suppliers`')->fetchColumn();

    echo json_encode([
        'status' => 'ok',
        'database' => 'connected',
        'products' => $productCount,
        'categories' => $categoryCount,
        'suppliers' => $supplierCount,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('Health check failed: ' . $e->getMessage());
    http_response_code(503);
    echo json_encode(['status' => 'error', 'database' => 'unavailable'], JSON_UNESCAPED_UNICODE);
}
