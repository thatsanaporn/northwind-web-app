<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// -------------------------------------------------------------
// READ: ค้นหาและดึงข้อมูลสินค้า (Search & Get Products)
// -------------------------------------------------------------
if ($method === 'GET' && $action === 'read') {
    $search = trim($_GET['search'] ?? '');
    try {
        $stmt = $pdo->prepare("SELECT ProductID, ProductName, UnitPrice, UnitsInStock FROM products WHERE ProductName LIKE ? ORDER BY ProductID DESC LIMIT 100");
        $stmt->execute(["%$search%"]);
        echo json_encode($stmt->fetchAll());
    } catch (PDOException $e) {
        $stmt = $pdo->prepare("SELECT ProductID, ProductName, UnitPrice, UnitsInStock FROM Products WHERE ProductName LIKE ? ORDER BY ProductID DESC LIMIT 100");
        $stmt->execute(["%$search%"]);
        echo json_encode($stmt->fetchAll());
    }
    exit;
}

// -------------------------------------------------------------
// CREATE: เพิ่มข้อมูลสินค้าใหม่ (Create Product)
// -------------------------------------------------------------
if ($method === 'POST' && $action === 'create') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty(trim($data['ProductName'] ?? '')) || !is_numeric($data['UnitPrice']) || floatval($data['UnitPrice']) < 0) {
        echo json_encode(["status" => "error", "message" => "ข้อมูลไม่ถูกต้อง กรุณาระบุชื่อสินค้าและราคาที่ไม่ติดลบ"]);
        exit;
    }

    $name  = trim($data['ProductName']);
    $price = floatval($data['UnitPrice']);
    $stock = isset($data['UnitsInStock']) ? intval($data['UnitsInStock']) : 0;

    try {
        $stmt = $pdo->prepare("INSERT INTO products (ProductName, UnitPrice, UnitsInStock) VALUES (?, ?, ?)");
        $stmt->execute([$name, $price, $stock]);
    } catch (PDOException $e) {
        $stmt = $pdo->prepare("INSERT INTO Products (ProductName, UnitPrice, UnitsInStock) VALUES (?, ?, ?)");
        $stmt->execute([$name, $price, $stock]);
    }

    echo json_encode(["status" => "success", "message" => "เพิ่มสินค้าใหม่เรียบร้อยแล้ว"]);
    exit;
}

// -------------------------------------------------------------
// UPDATE: แก้ไขข้อมูลสินค้า (Update Product)
// -------------------------------------------------------------
if ($method === 'POST' && $action === 'update') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['ProductID']) || empty(trim($data['ProductName'] ?? '')) || !is_numeric($data['UnitPrice'])) {
        echo json_encode(["status" => "error", "message" => "ข้อมูลไม่ครบถ้วนหรือไม่ถูกต้อง"]);
        exit;
    }

    $id    = intval($data['ProductID']);
    $name  = trim($data['ProductName']);
    $price = floatval($data['UnitPrice']);
    $stock = intval($data['UnitsInStock'] ?? 0);

    try {
        $stmt = $pdo->prepare("UPDATE products SET ProductName = ?, UnitPrice = ?, UnitsInStock = ? WHERE ProductID = ?");
        $stmt->execute([$name, $price, $stock, $id]);
    } catch (PDOException $e) {
        $stmt = $pdo->prepare("UPDATE Products SET ProductName = ?, UnitPrice = ?, UnitsInStock = ? WHERE ProductID = ?");
        $stmt->execute([$name, $price, $stock, $id]);
    }

    echo json_encode(["status" => "success", "message" => "อัปเดตข้อมูลสินค้าเรียบร้อยแล้ว"]);
    exit;
}

// -------------------------------------------------------------
// DELETE: ลบข้อมูลสินค้า (Delete Product)
// -------------------------------------------------------------
if (($method === 'GET' || $method === 'DELETE') && $action === 'delete') {
    $id = intval($_GET['id'] ?? 0);

    if ($id <= 0) {
        echo json_encode(["status" => "error", "message" => "รหัสสินค้าไม่ถูกต้อง"]);
        exit;
    }

    try {
        try {
            $stmt = $pdo->prepare("DELETE FROM products WHERE ProductID = ?");
            $stmt->execute([$id]);
        } catch (PDOException $e) {
            $stmt = $pdo->prepare("DELETE FROM Products WHERE ProductID = ?");
            $stmt->execute([$id]);
        }
        echo json_encode(["status" => "success", "message" => "ลบข้อมูลสินค้าเรียบร้อยแล้ว"]);
    } catch (PDOException $e) {
        echo json_encode(["status" => "error", "message" => "ไม่สามารถลบได้เนื่องจากสินค้านี้ถูกเชื่อมโยงอยู่ในรายการสั่งซื้อ"]);
    }
    exit;
}

echo json_encode(["status" => "error", "message" => "Invalid Request"]);
?>
