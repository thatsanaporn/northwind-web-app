<?php
$host = getenv('MYSQLHOST') ?: 'localhost';
$user = getenv('MYSQLUSER') ?: 'root';
$pass = getenv('MYSQLPASSWORD') ?: '';
$db   = getenv('MYSQLDATABASE') ?: 'northwind';
$port = getenv('MYSQLPORT') ?: '3306';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(["status" => "error", "message" => $e->getMessage()]));
}
?>
```[cite: 1, 2]

##### 2. `api.php` (จัดการ CRUD สำหรับสินค้า / Products)[cite: 2]
```php
<?php
header('Content-Type: application/json');
require_once 'db.php';

$action = $_GET['action'] ?? '';

if ($action === 'read') {
    $search = $_GET['search'] ?? '';
    $stmt = $pdo->prepare("SELECT ProductID, ProductName, UnitPrice, UnitsInStock FROM products WHERE ProductName LIKE ? ORDER BY ProductID DESC");
    $stmt->execute(["%$search%"]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} 
elseif ($action === 'create') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (empty($data['ProductName']) || !is_numeric($data['UnitPrice'])) {
        echo json_encode(["status" => "error", "message" => "ข้อมูลไม่ถูกต้อง (Validation Error)"]);
        exit;
    }
    $stmt = $pdo->prepare("INSERT INTO products (ProductName, UnitPrice, UnitsInStock) VALUES (?, ?, ?)");
    $stmt->execute([$data['ProductName'], $data['UnitPrice'], $data['UnitsInStock'] ?? 0]);
    echo json_encode(["status" => "success", "message" => "เพิ่มสินค้าเรียบร้อยแล้ว"]);
} 
elseif ($action === 'update') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (empty($data['ProductID']) || empty($data['ProductName'])) {
        echo json_encode(["status" => "error", "message" => "ข้อมูลไม่ครบถ้วน"]);
        exit;
    }
    $stmt = $pdo->prepare("UPDATE products SET ProductName = ?, UnitPrice = ?, UnitsInStock = ? WHERE ProductID = ?");
    $stmt->execute([$data['ProductName'], $data['UnitPrice'], $data['UnitsInStock'], $data['ProductID']]);
    echo json_encode(["status" => "success", "message" => "แก้ไขข้อมูลสินค้าเรียบร้อยแล้ว"]);
} 
elseif ($action === 'delete') {
    $id = $_GET['id'] ?? 0;
    $stmt = $pdo->prepare("DELETE FROM products WHERE ProductID = ?");
    $stmt->execute([$id]);
    echo json_encode(["status" => "success", "message" => "ลบสินค้าเรียบร้อยแล้ว"]);
}
?>
```[cite: 2]

---

#### ขั้นตอนที่ 3: เขียน Frontend UI & JS (30 นาที)[cite: 1, 2]

##### `index.html` (หน้าจอ ค้นหา เพิ่ม แก้ไข ลบ พร้อม Validation & Alert)[cite: 2]
```html
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ระบบจัดการสินค้า (Northwind Products)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-light container py-4">
    <h2 class="mb-4 text-center">ระบบจัดการสินค้า (Northwind)</h2>

    <!-- ค้นหา & ปุ่มเพิ่ม -->
    <div class="row mb-3">
        <div class="col-md-8">
            <input type="text" id="searchInput" class="form-control" placeholder="ค้นหาชื่อสินค้า..." onkeyup="loadProducts()">
        </div>
        <div class="col-md-4 text-end">
            <button class="btn btn-primary" onclick="openModal()">+ เพิ่มสินค้าใหม่</button>
        </div>
    </div>

    <!-- ตารางแสดงข้อมูล -->
    <table class="table table-bordered bg-white shadow-sm">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>ชื่อสินค้า</th>
                <th>ราคาต่อหน่วย</th>
                <th>จำนวนคงเหลือ</th>
                <th>จัดการ</th>
            </tr>
        </thead>
        <tbody id="productTable"></tbody>
    </table>

    <!-- Modal เพิ่ม/แก้ไข -->
    <div class="modal fade" id="productModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">สินค้า</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="p_id">
                    <div class="mb-3">
                        <label>ชื่อสินค้า *</label>
                        <input type="text" id="p_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>ราคาต่อหน่วย *</label>
                        <input type="number" step="0.01" id="p_price" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>จำนวนในสต็อก</label>
                        <input type="number" id="p_stock" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-success" onclick="saveProduct()">บันทึก</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let modal = new bootstrap.Modal(document.getElementById('productModal'));

        function loadProducts() {
            let search = document.getElementById('searchInput').value;
            fetch(`api.php?action=read&search=${search}`)
                .then(res => res.json())
                .then(data => {
                    let html = '';
                    data.forEach(p => {
                        html += `<tr>
                            <td>${p.ProductID}</td>
                            <td>${p.ProductName}</td>
                            <td>${p.UnitPrice}</td>
                            <td>${p.UnitsInStock}</td>
                            <td>
                                <button class="btn btn-warning btn-sm" onclick="editProduct(${p.ProductID}, '${p.ProductName}', ${p.UnitPrice}, ${p.UnitsInStock})">แก้ไข</button>
                                <button class="btn btn-danger btn-sm" onclick="deleteProduct(${p.ProductID})">ลบ</button>
                            </td>
                        </tr>`;
                    });
                    document.getElementById('productTable').innerHTML = html;
                });
        }

        function openModal() {
            document.getElementById('p_id').value = '';
            document.getElementById('p_name').value = '';
            document.getElementById('p_price').value = '';
            document.getElementById('p_stock').value = '';
            document.getElementById('modalTitle').innerText = 'เพิ่มสินค้าใหม่';
            modal.show();
        }

        function editProduct(id, name, price, stock) {
            document.getElementById('p_id').value = id;
            document.getElementById('p_name').value = name;
            document.getElementById('p_price').value = price;
            document.getElementById('p_stock').value = stock;
            document.getElementById('modalTitle').innerText = 'แก้ไขสินค้า';
            modal.show();
        }

        function saveProduct() {
            let id = document.getElementById('p_id').value;
            let name = document.getElementById('p_name').value.trim();
            let price = document.getElementById('p_price').value;
            let stock = document.getElementById('p_stock').value;

            // Validation[cite: 2]
            if (!name || price === '' || price < 0) {
                Swal.fire('แจ้งเตือน', 'กรุณากรอกข้อมูลชื่อสินค้าและราคาให้ถูกต้อง', 'warning');
                return;
            }

            let action = id ? 'update' : 'create';
            let bodyData = { ProductID: id, ProductName: name, UnitPrice: price, UnitsInStock: stock };

            fetch(`api.php?action=${action}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(bodyData)
            })
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success') {
                    Swal.fire('สำเร็จ', res.message, 'success'); // แจ้งผล CRUD สำเร็จ[cite: 2]
                    modal.hide();
                    loadProducts();
                } else {
                    Swal.fire('ข้อผิดพลาด', res.message, 'error');
                }
            });
        }

        function deleteProduct(id) {
            Swal.fire({
                title: 'ยืนยันการลบ?',
                text: "คุณจะไม่สามารถย้อนกลับได้!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'ลบเลย',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`api.php?action=delete&id=${id}`)
                        .then(res => res.json())
                        .then(res => {
                            Swal.fire('ลบสำเร็จ', res.message, 'success');
                            loadProducts();
                        });
                }
            });
        }

        loadProducts();
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
```[cite: 1, 2]

---

#### ขั้นตอนที่ 4: Deploy ขึ้น Railway (20 นาที)[cite: 2, 3]
1. นำโค้ดทั้งหมด (`db.php`, `api.php`, `index.html`) Push ขึ้น **GitHub Repository**
2. ไปที่ [Railway.com](https://railway.com/) ในโปรเจกต์เดิม -> กด **New** -> **GitHub Repo** -> เลือก Repo ที่เพิ่ง Push ขึ้นไป[cite: 2]
3. เชื่อมต่อ Variable โดยไปที่แท็บ **Variables** ของ Web Service แล้วอ้างอิงค่าจากตัวแปร MySQL (หรือใส่ `MYSQLHOST`, `MYSQLUSER`, `MYSQLPASSWORD`, `MYSQLDATABASE`, `MYSQLPORT` ตรงๆ)[cite: 1, 2]
4. ไปที่แท็บ **Settings** -> **Networking** -> กด **Generate Domain** จะได้ URL สาธารณะเพื่อส่งงาน[cite: 3]

---

#### ขั้นตอนที่ 5: เตรียมส่งงาน (10 นาที)[cite: 3]
1. **Live Application URL**: คัดลอก URL จาก Railway มาวาง[cite: 3]
2. **Process Documentation**:
   - สร้าง Google Doc อธิบายขั้นตอนตั้งแต่การสร้าง MySQL, สรุปโค้ด CRUD, การ Push ขึ้น GitHub และ Deploy บน Railway[cite: 3]
   - Zip ไฟล์ Source Code หรือนำ URL GitHub ไปใส่ใน Google Drive แล้วเปิด Permission เป็น Public[cite: 3]