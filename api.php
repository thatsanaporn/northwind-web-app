<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

function respond($data, $status = 200)
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function textLength($value)
{
    return preg_match_all('/./us', $value, $matches);
}

function readProductInput($data = null)
{
    if ($data === null) {
        $data = json_decode(file_get_contents('php://input'), true);
    }
    if (!is_array($data)) {
        respond(['status' => 'error', 'message' => 'รูปแบบข้อมูลไม่ถูกต้อง'], 400);
    }

    if (!is_string($data['ProductName'] ?? null) || !is_string($data['Unit'] ?? null)) {
        respond(['status' => 'error', 'message' => 'ชื่อสินค้าและหน่วยต้องเป็นข้อความ'], 422);
    }

    $product = [
        'name' => trim($data['ProductName'] ?? ''),
        'price' => $data['UnitPrice'] ?? null,
        'stock' => $data['UnitsInStock'] ?? 0,
        'supplier' => $data['SupplierID'] ?? null,
        'category' => $data['CategoryID'] ?? null,
        'unit' => trim($data['Unit'] ?? '')
    ];

    if ($product['name'] === '' || textLength($product['name']) > 30 ||
        !is_scalar($product['price']) || !is_numeric($product['price']) || (float) $product['price'] < 0 ||
        !is_scalar($product['stock']) || filter_var($product['stock'], FILTER_VALIDATE_INT) === false || (int) $product['stock'] < 0 ||
        !is_scalar($product['supplier']) || filter_var($product['supplier'], FILTER_VALIDATE_INT) === false || (int) $product['supplier'] <= 0 ||
        !is_scalar($product['category']) || filter_var($product['category'], FILTER_VALIDATE_INT) === false || (int) $product['category'] <= 0 ||
        $product['unit'] === '' || textLength($product['unit']) > 30) {
        respond(['status' => 'error', 'message' => 'กรุณาตรวจสอบชื่อสินค้า ราคา สต็อก หน่วย supplier และ category'], 422);
    }

    return $product;
}

try {
    if ($method === 'GET' && $action === 'read') {
    $search = trim($_GET['search'] ?? '');
        $stmt = $pdo->prepare("SELECT i_ProductID AS ProductID, c_ProductName AS ProductName, i_Price AS UnitPrice, i_UnitsInStock AS UnitsInStock, i_SupplierID AS SupplierID, i_CategoryID AS CategoryID, c_Unit AS Unit FROM tb_products WHERE c_ProductName LIKE ? ORDER BY i_ProductID DESC LIMIT 100");
        $stmt->execute(["%$search%"]);
        respond($stmt->fetchAll());
    }

    if ($method === 'GET' && $action === 'options') {
        $categories = $pdo->query('SELECT i_CategoryID AS CategoryID, c_CategoryName AS CategoryName FROM tb_categories ORDER BY c_CategoryName')->fetchAll();
        $suppliers = $pdo->query('SELECT i_SupplierID AS SupplierID, c_SupplierName AS SupplierName FROM tb_suppliers ORDER BY c_SupplierName')->fetchAll();
        respond(['categories' => $categories, 'suppliers' => $suppliers]);
    }

    if ($method === 'POST' && $action === 'create') {
        $product = readProductInput();
        $stmt = $pdo->prepare('INSERT INTO tb_products (c_ProductName, i_SupplierID, i_CategoryID, c_Unit, i_Price, i_UnitsInStock) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$product['name'], $product['supplier'], $product['category'], $product['unit'], $product['price'], $product['stock']]);
        respond(['status' => 'success', 'message' => 'เพิ่มสินค้าใหม่เรียบร้อยแล้ว']);
    }

    if ($method === 'POST' && $action === 'update') {
        $data = json_decode(file_get_contents('php://input'), true);
        $id = is_array($data) ? ($data['ProductID'] ?? null) : null;
        if (!is_scalar($id) || filter_var($id, FILTER_VALIDATE_INT) === false || (int) $id <= 0) {
            respond(['status' => 'error', 'message' => 'รหัสสินค้าไม่ถูกต้อง'], 422);
        }
        $product = readProductInput($data);
        $stmt = $pdo->prepare('UPDATE tb_products SET c_ProductName = ?, i_SupplierID = ?, i_CategoryID = ?, c_Unit = ?, i_Price = ?, i_UnitsInStock = ? WHERE i_ProductID = ?');
        $stmt->execute([$product['name'], $product['supplier'], $product['category'], $product['unit'], $product['price'], $product['stock'], (int) $data['ProductID']]);
        if ($stmt->rowCount() === 0) {
            $check = $pdo->prepare('SELECT 1 FROM tb_products WHERE i_ProductID = ?');
            $check->execute([(int) $data['ProductID']]);
            if (!$check->fetchColumn()) {
                respond(['status' => 'error', 'message' => 'ไม่พบสินค้าที่ต้องการแก้ไข'], 404);
            }
        }
        respond(['status' => 'success', 'message' => 'อัปเดตข้อมูลสินค้าเรียบร้อยแล้ว']);
    }

    if ($method === 'DELETE' && $action === 'delete') {
        $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
        if ($id === false || $id <= 0) {
            respond(['status' => 'error', 'message' => 'รหัสสินค้าไม่ถูกต้อง'], 422);
        }
        $stmt = $pdo->prepare('DELETE FROM tb_products WHERE i_ProductID = ?');
        $stmt->execute([$id]);
        if ($stmt->rowCount() === 0) {
            respond(['status' => 'error', 'message' => 'ไม่พบสินค้าที่ต้องการลบ'], 404);
        }
        respond(['status' => 'success', 'message' => 'ลบข้อมูลสินค้าเรียบร้อยแล้ว']);
    }

    respond(['status' => 'error', 'message' => 'Invalid Request'], 404);
} catch (PDOException $e) {
    error_log($e->getMessage());
    respond(['status' => 'error', 'message' => 'เกิดข้อผิดพลาดในการติดต่อฐานข้อมูล'], 500);
}
