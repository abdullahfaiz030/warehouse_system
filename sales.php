<?php
require_once 'auth_check.php';
requireRole(['Admin', 'Cashier']);
require_once 'db.php';
$pdo = db();

/* ================= SAVE SALE ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'record_sale') {

    $location_id = (int)$_POST['location_id'];
    $discount    = (float)($_POST['discount'] ?? 0);
    $tax_rate    = (float)($_POST['tax_rate'] ?? 0);
    $payment_method = 'Cash';

    $product_ids = $_POST['product_id'] ?? [];
    $qtys        = $_POST['qty'] ?? [];
    $prices      = $_POST['price'] ?? [];

    if (empty($product_ids)) {
        die("Cart empty");
    }

    try {
        $pdo->beginTransaction();

        $invoice_no = 'INV-' . date('YmdHis');
        $subtotal = 0;

        foreach ($product_ids as $i => $pid) {
            $subtotal += $prices[$i] * $qtys[$i];
        }

        if ($discount > $subtotal) $discount = $subtotal;

        $tax_amount = ($subtotal - $discount) * ($tax_rate / 100);
        $net_total  = ($subtotal - $discount) + $tax_amount;

        $stmt = $pdo->prepare("
            INSERT INTO sales
            (invoice_no, location_id, payment_method,
             total_amount, discount_amount, tax_amount, net_amount)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $invoice_no, $location_id, $payment_method,
            $subtotal, $discount, $tax_amount, $net_total
        ]);

        $sale_id = $pdo->lastInsertId();

        foreach ($product_ids as $i => $pid) {
            $qty_needed = (int)$qtys[$i];
            
            // 1. CHECK STOCK
            $stmtStock = $pdo->prepare("SELECT quantity FROM stock WHERE product_id = ? AND location_id = ? FOR UPDATE");
            $stmtStock->execute([$pid, $location_id]);
            $current_stock = $stmtStock->fetchColumn();

            if ($current_stock === false) { 
                // Stock record doesn't exist, treat as 0
                $current_stock = 0; 
            }

            if ($current_stock < $qty_needed) {
                throw new Exception("Insufficient stock for Product ID $pid (Available: $current_stock, Requested: $qty_needed)");
            }

            $item_sub = $prices[$i] * $qty_needed;

            $pdo->prepare("
                INSERT INTO sales_items
                (sale_id, product_id, qty, unit_price, subtotal)
                VALUES (?, ?, ?, ?, ?)
            ")->execute([
                $sale_id, $pid, $qty_needed, $prices[$i], $item_sub
            ]);

            $pdo->prepare("
                UPDATE stock
                SET quantity = quantity - ?
                WHERE product_id = ? AND location_id = ?
            ")->execute([$qty_needed, $pid, $location_id]);
        }

        $pdo->commit();
        header("Location: receipt.php?id=$sale_id");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        die("<div style='color:red; padding:20px;'>Error: " . $e->getMessage() . " <br><a href='sales.php'>Back</a></div>");
    }
}

/* ================= FETCH DATA ================= */
$products  = $pdo->query("SELECT * FROM products WHERE status='Active'")->fetchAll();
$locations = $pdo->query("SELECT * FROM locations")->fetchAll();
// Fetch current stock for all products across all locations
$stocks    = $pdo->query("SELECT product_id, location_id, quantity FROM stock")->fetchAll();

require 'header.php';
?>

<div class="container-fluid animate-fade-in px-4">

<div class="glass-card p-4 shadow-lg">

<h4 class="fw-bold text-primary mb-4">Billing Terminal</h4>

<!-- Branch -->
<div class="mb-3">
    <select class="form-select rounded-pill" id="cartBranch" onchange="branchChanged()">
        <?php foreach ($locations as $l): ?>
            <option value="<?= $l['id'] ?>"><?= htmlspecialchars($l['name']) ?></option>
        <?php endforeach ?>
    </select>
</div>

<!-- SEARCH -->
<div class="position-relative mb-3">
    <input type="text" id="searchInput" class="form-control rounded-pill px-4" 
           placeholder="Search product name or SKU" 
           oninput="showResults(this.value)">
    <div id="searchResults" 
         class="position-absolute w-100 bg-white rounded-3 shadow mt-2" 
         style="z-index:1000; display:none; max-height:300px; overflow-y:auto;"></div>
</div>

<!-- CART TABLE -->
<div class="table-responsive">
<table class="table table-glass align-middle">
<thead>
<tr>
    <th>Product</th>
    <th>Price</th>
    <th width="120">Qty</th>
    <th width="60"></th>
</tr>
</thead>
<tbody id="cartBody"></tbody>
</table>
</div>

<!-- TOTALS -->
<div class="row justify-content-end mt-4">
<div class="col-md-4">

<div class="d-flex justify-content-between mb-2">
    <span>Total Qty</span>
    <strong id="totalQty">0</strong>
</div>

<div class="d-flex justify-content-between mb-2">
    <span>Subtotal</span>
    <strong id="subtotalDisplay">LKR 0.00</strong>
</div>

<div class="d-flex justify-content-between mb-2">
    <span>Total Discount</span>
    <input type="number" id="discountInput" 
           class="form-control form-control-sm w-50 text-end" 
           oninput="calculateCart()">
</div>

<div class="d-flex justify-content-between mb-2">
    <span>Tax (%)</span>
    <input type="number" id="taxInput" 
           class="form-control form-control-sm w-50 text-end" 
           oninput="calculateCart()">
</div>

<hr>

<div class="d-flex justify-content-between fs-5">
    <strong>Net Total</strong>
    <strong id="netTotal">LKR 0.00</strong>
</div>

<form method="post" id="saleForm">
    <input type="hidden" name="action" value="record_sale">
    <input type="hidden" name="location_id" id="formLoc">
    <input type="hidden" name="discount" id="formDiscount">
    <input type="hidden" name="tax_rate" id="formTax">
    <div id="hiddenItems"></div>

    <button type="button" 
            class="btn btn-primary w-100 mt-3" 
            onclick="submitSale()">
        FINALIZE SALE
    </button>
</form>

</div>
</div>

</div>
</div>

<!-- ROW TEMPLATE -->
<template id="cartRowTemplate">
<tr>
    <td>
        <div class="fw-bold item-name"></div>
        <small class="text-muted item-stock-info" style="font-size: 0.75rem"></small>
    </td>
    <td class="item-price text-muted"></td>
    <td>
        <input type="number" 
               class="form-control form-control-sm text-center qty-input" 
               min="1" value="1"
               oninput="qtyChanged(this)">
    </td>
    <td class="text-center">
        <button class="btn btn-sm text-danger" 
                onclick="removeItem(this)">✕</button>
    </td>
</tr>
</template>

<script>
const products = <?= json_encode($products) ?>;
const stockData = <?= json_encode($stocks) ?>;
let cart = [];

function getAvailableStock(productId, locationId) {
    // Determine the product ID carefully (type safety)
    const pid = parseInt(productId);
    const lid = parseInt(locationId);
    const record = stockData.find(s => s.product_id == pid && s.location_id == lid);
    return record ? parseInt(record.quantity) : 0;
}

function branchChanged() {
    // When branch changes, re-validate cart or clear it? 
    // For safety, let's just re-render to update max limitations if we wanted to simplify
    // But usually clearing is safer or showing warnings. 
    // For now, let's clear cart to avoid location mixing exploits easily
    if(cart.length > 0) {
        if(confirm("Changing branch will clear the cart. Continue?")) {
            cart = [];
            renderCart();
        } else {
            // Revert selection (this is a bit tricky without previous value, 
            // but for simplicity let's just leave it or strictly clear)
            cart = [];
            renderCart();
        }
    }
}

/* SEARCH RESULTS */
function showResults(q) {
    const box = document.getElementById('searchResults');
    q = q.toLowerCase().trim();
    if (!q) { box.style.display='none'; return; }

    const locId = document.getElementById('cartBranch').value;

    const matches = products.filter(p => 
        p.name.toLowerCase().includes(q) || 
        p.sku.toLowerCase().includes(q)
    );

    if (matches.length === 0) {
        box.innerHTML = '<div class="p-3 text-muted text-center">No results</div>';
    } else {
        box.innerHTML = matches.map(p => {
            const avail = getAvailableStock(p.id, locId);
            const isOutOfStock = avail <= 0;
            const stockClass = isOutOfStock ? 'text-danger' : 'text-success';
            const stockText = isOutOfStock ? 'Out of Stock' : avail + ' In Stock';
            const cursor = isOutOfStock ? 'not-allowed' : 'pointer';
            const onClick = isOutOfStock ? '' : `onclick='addToCart(${JSON.stringify(p)})'`;
            const opacity = isOutOfStock ? '0.6' : '1';

            return `
            <div class="p-3 border-bottom hover-bg-light" 
                 style="cursor:${cursor}; opacity:${opacity}" 
                 ${onClick}>
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-bold">${p.name}</div>
                        <small class="text-muted">${p.sku}</small>
                    </div>
                    <div class="text-end">
                        <div class="fw-bold text-primary">
                            LKR ${parseFloat(p.selling_price).toLocaleString()}
                        </div>
                        <small class="${stockClass} fw-bold">${stockText}</small>
                    </div>
                </div>
            </div>
            `;
        }).join('');
    }
    box.style.display = 'block';
}

/* ADD TO CART */
function addToCart(p) {
    const locId = document.getElementById('cartBranch').value;
    const maxStock = getAvailableStock(p.id, locId);

    if (maxStock <= 0) {
        alert("This item is out of stock!");
        return;
    }

    const existing = cart.find(i => i.id === p.id);
    if (existing) {
        if (existing.qty + 1 > maxStock) {
            alert("Cannot add more. Max available stock is " + maxStock);
            return;
        }
        existing.qty += 1;
    } else {
        cart.push({
            id: p.id,
            name: p.name,
            price: parseFloat(p.selling_price),
            qty: 1,
            max: maxStock
        });
    }
    document.getElementById('searchResults').style.display = 'none';
    document.getElementById('searchInput').value = '';
    renderCart();
}

/* RENDER CART */
function renderCart() {
    const body = document.getElementById('cartBody');
    body.innerHTML = '';
    const tpl = document.getElementById('cartRowTemplate');

    cart.forEach(item => {
        const row = tpl.content.cloneNode(true);
        row.querySelector('.item-name').innerText = item.name;
        // Update max stock dynamically in case it wasn't set (e.g. from page reload if persisted)
        const locId = document.getElementById('cartBranch').value;
        const currentMax = getAvailableStock(item.id, locId); 
        item.max = currentMax;

        row.querySelector('.item-stock-info').innerText = `Max: ${item.max}`;
        
        row.querySelector('.item-price').innerText = 
            'LKR ' + item.price.toLocaleString(undefined,{minimumFractionDigits:2});
        
        const qtyInput = row.querySelector('.qty-input');
        qtyInput.value = item.qty;
        qtyInput.max = item.max; // Set HTML max attribute

        body.appendChild(row);
    });

    calculateCart();
}

/* QTY CHANGE */
function qtyChanged(input) {
    const row = input.closest('tr');
    const name = row.querySelector('.item-name').innerText;
    const item = cart.find(i => i.name === name);
    
    let val = parseInt(input.value) || 1;
    if (val < 1) val = 1;

    // Enforce Stock Limit
    const locId = document.getElementById('cartBranch').value;
    const maxStock = getAvailableStock(item.id, locId);
    
    if (val > maxStock) {
        alert(`Only ${maxStock} units available.`);
        val = maxStock;
        input.value = val;
    }

    item.qty = val;
    calculateCart();
}

/* REMOVE */
function removeItem(btn) {
    const name = btn.closest('tr').querySelector('.item-name').innerText;
    cart = cart.filter(i => i.name !== name);
    renderCart();
}

/* TOTALS */
function calculateCart() {
    let subtotal = 0, qty = 0;

    cart.forEach(i => {
        subtotal += i.price * i.qty;
        qty += i.qty;
    });

    let discount = +document.getElementById('discountInput').value || 0;
    let tax = +document.getElementById('taxInput').value || 0;
    if (discount > subtotal) discount = subtotal;

    let net = (subtotal - discount) * (1 + tax / 100);

    document.getElementById('subtotalDisplay').innerText = 
        'LKR ' + subtotal.toLocaleString(undefined,{minimumFractionDigits:2});
    document.getElementById('totalQty').innerText = qty;
    document.getElementById('netTotal').innerText = 
        'LKR ' + net.toLocaleString(undefined,{minimumFractionDigits:2});
}

/* SUBMIT */
function submitSale() {
    if (cart.length === 0) return alert('Cart empty');

    const box = document.getElementById('hiddenItems');
    box.innerHTML = '';

    cart.forEach(i => {
        box.innerHTML += `<input type="hidden" name="product_id[]" value="${i.id}">`;
        box.innerHTML += `<input type="hidden" name="qty[]" value="${i.qty}">`;
        box.innerHTML += `<input type="hidden" name="price[]" value="${i.price}">`;
    });

    document.getElementById('formLoc').value = 
        document.getElementById('cartBranch').value;
    document.getElementById('formDiscount').value = 
        document.getElementById('discountInput').value;
    document.getElementById('formTax').value = 
        document.getElementById('taxInput').value;

    document.getElementById('saleForm').submit();
}
</script>

<?php require 'footer.php'; ?>
