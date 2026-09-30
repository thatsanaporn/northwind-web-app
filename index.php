<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Northwind - Web Application Management</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-light">

    <div class="container py-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-md-12 text-center">
                <h2 class="fw-bold text-primary">ระบบจัดการข้อมูลสินค้า (Northwind)</h2>
                <p class="text-muted">Cloud Platform (PaaS) - Railway Deployment</p>
            </div>
        </div>

        <!-- Search & Add Button -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-md-9">
                        <input type="text" id="searchInput" class="form-control form-control-lg" placeholder="🔍 ค้นหาชื่อสินค้า..." onkeyup="loadProducts()">
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-success btn-lg w-100 fw-bold" onclick="openAddModal()">+ เพิ่มสินค้าใหม่</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Product Table -->
        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-center" style="width: 10%;">ID</th>
                                <th style="width: 30%;">ชื่อสินค้า (Product Name)</th>
                                <th style="width: 20%;">หน่วย</th>
                                <th class="text-end" style="width: 12%;">ราคา ($)</th>
                                <th class="text-end" style="width: 12%;">คงเหลือ</th>
                                <th class="text-center" style="width: 16%;">การจัดการ</th>
                            </tr>
                        </thead>
                        <tbody id="productTable">
                            <tr><td colspan="6" class="text-center py-4">กำลังโหลดข้อมูล...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Form (Add / Edit) -->
    <div class="modal fade" id="productModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalTitle">จัดการข้อมูลสินค้า</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="productForm">
                        <input type="hidden" id="p_id">
                        <div class="mb-3">
                            <label class="form-label fw-bold">ชื่อสินค้า <span class="text-danger">*</span></label>
                            <input type="text" id="p_name" class="form-control" placeholder="ระบุชื่อสินค้า" maxlength="30" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">หน่วยบรรจุ <span class="text-danger">*</span></label>
                            <input type="text" id="p_unit" class="form-control" placeholder="เช่น 12 bottles" maxlength="30" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">ผู้จัดจำหน่าย <span class="text-danger">*</span></label>
                            <select id="p_supplier" class="form-select" required>
                                <option value="">เลือกผู้จัดจำหน่าย</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">หมวดหมู่ <span class="text-danger">*</span></label>
                            <select id="p_category" class="form-select" required>
                                <option value="">เลือกหมวดหมู่</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">ราคาต่อหน่วย ($) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" id="p_price" class="form-control" placeholder="0.00" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">จำนวนคงเหลือในสต็อก</label>
                            <input type="number" min="0" step="1" id="p_stock" class="form-control" placeholder="0" value="0">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-primary fw-bold" onclick="saveProduct()">บันทึกข้อมูล</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const productModal = new bootstrap.Modal(document.getElementById('productModal'));

        function escapeHtml(value) {
            return String(value ?? '').replace(/[&<>"']/g, char => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
            })[char]);
        }

        async function loadOptions() {
            try {
                const response = await fetch('api.php?action=options');
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'โหลดตัวเลือกไม่สำเร็จ');
                document.getElementById('p_supplier').innerHTML += data.suppliers.map(item =>
                    `<option value="${item.SupplierID}">${escapeHtml(item.SupplierName)}</option>`
                ).join('');
                document.getElementById('p_category').innerHTML += data.categories.map(item =>
                    `<option value="${item.CategoryID}">${escapeHtml(item.CategoryName)}</option>`
                ).join('');
            } catch (error) {
                console.error('Failed to load supplier and category options:', error);
                Swal.fire('โหลดข้อมูลไม่สำเร็จ', error.message || 'ไม่สามารถโหลด supplier และ category จากฐานข้อมูลได้', 'error');
            }
        }

        async function loadProducts() {
            const search = document.getElementById('searchInput').value;
            const table = document.getElementById('productTable');
            try {
                const response = await fetch(`api.php?action=read&search=${encodeURIComponent(search)}`);
                const data = await response.json();
                if (!response.ok || !Array.isArray(data)) throw new Error(data.message || 'โหลดสินค้าไม่สำเร็จ');
                if (data.length === 0) {
                    table.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">ไม่พบข้อมูลสินค้า</td></tr>';
                    return;
                }
                table.innerHTML = data.map(product => `
                    <tr>
                        <td class="text-center font-monospace">${product.ProductID}</td>
                        <td class="fw-bold">${escapeHtml(product.ProductName)}</td>
                        <td>${escapeHtml(product.Unit)}</td>
                        <td class="text-end">$${Number(product.UnitPrice).toFixed(2)}</td>
                        <td class="text-end">${product.UnitsInStock}</td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-warning me-1" data-action="edit" data-id="${product.ProductID}" data-name="${escapeHtml(product.ProductName)}" data-unit="${escapeHtml(product.Unit)}" data-price="${product.UnitPrice}" data-stock="${product.UnitsInStock}" data-supplier="${product.SupplierID}" data-category="${product.CategoryID}">แก้ไข</button>
                            <button class="btn btn-sm btn-outline-danger" data-action="delete" data-id="${product.ProductID}">ลบ</button>
                        </td>
                    </tr>`).join('');
            } catch (error) {
                table.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-danger">ไม่สามารถโหลดข้อมูลสินค้าได้</td></tr>';
            }
        }

        function openAddModal() {
            document.getElementById('productForm').reset();
            document.getElementById('p_id').value = '';
            document.getElementById('p_stock').value = '0';
            document.getElementById('modalTitle').innerText = 'เพิ่มสินค้าใหม่';
            productModal.show();
        }

        function openEditModal(product) {
            document.getElementById('p_id').value = product.id;
            document.getElementById('p_name').value = product.name;
            document.getElementById('p_unit').value = product.unit;
            document.getElementById('p_price').value = product.price;
            document.getElementById('p_stock').value = product.stock;
            document.getElementById('p_supplier').value = product.supplier;
            document.getElementById('p_category').value = product.category;
            document.getElementById('modalTitle').innerText = 'แก้ไขข้อมูลสินค้า';
            productModal.show();
        }

        async function saveProduct() {
            const id = document.getElementById('p_id').value;
            const payload = {
                ProductID: id,
                ProductName: document.getElementById('p_name').value.trim(),
                Unit: document.getElementById('p_unit').value.trim(),
                UnitPrice: document.getElementById('p_price').value,
                UnitsInStock: document.getElementById('p_stock').value,
                SupplierID: document.getElementById('p_supplier').value,
                CategoryID: document.getElementById('p_category').value
            };
            const price = Number(payload.UnitPrice);
            const stock = Number(payload.UnitsInStock);
            if (!payload.ProductName || payload.ProductName.length > 30 || !payload.Unit ||
                !payload.SupplierID || !payload.CategoryID || payload.UnitPrice === '' ||
                !Number.isFinite(price) || price < 0 || !Number.isInteger(stock) || stock < 0) {
                Swal.fire('แจ้งเตือน Validation', 'กรุณากรอกข้อมูลสินค้าให้ครบถ้วนและถูกต้อง', 'warning');
                return;
            }

            try {
                const response = await fetch(`api.php?action=${id ? 'update' : 'create'}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const result = await response.json();
                if (!response.ok || result.status !== 'success') throw new Error(result.message || 'บันทึกไม่สำเร็จ');
                await Swal.fire('ทำรายการสำเร็จ!', result.message, 'success');
                productModal.hide();
                loadProducts();
            } catch (error) {
                Swal.fire('เกิดข้อผิดพลาด!', error.message, 'error');
            }
        }

        async function deleteProduct(id) {
            Swal.fire({
                title: 'ยืนยันการลบสินค้า?',
                text: "หากลบแล้วข้อมูลจะหายไปจากระบบ",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'ยืนยันลบ',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`api.php?action=delete&id=${encodeURIComponent(id)}`, { method: 'DELETE' })
                        .then(async response => {
                            const result = await response.json();
                            if (!response.ok || result.status !== 'success') throw new Error(result.message || 'ลบไม่สำเร็จ');
                            await Swal.fire('ลบสำเร็จ!', result.message, 'success');
                            loadProducts();
                        })
                        .catch(error => Swal.fire('ไม่สามารถลบได้!', error.message, 'error'));
                }
            });
        }

        document.getElementById('searchInput').addEventListener('input', loadProducts);
        document.getElementById('productTable').addEventListener('click', event => {
            const button = event.target.closest('button[data-action]');
            if (!button) return;
            if (button.dataset.action === 'delete') {
                deleteProduct(button.dataset.id);
            } else {
                openEditModal(button.dataset);
            }
        });
        document.querySelector('#productModal .btn-primary').addEventListener('click', saveProduct);

        loadOptions();
        loadProducts();
    </script>
</body>
</html>
