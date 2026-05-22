<?php
require_once 'db.php';
$pdo = db();

if (!isset($_GET['id'])) {
    die("Invalid Receipt ID");
}

$id = (int) $_GET['id'];

// Fetch Sale
$stmt = $pdo->prepare("
    SELECT s.*, l.name as branch_name 
    FROM sales s 
    JOIN locations l ON l.id = s.location_id 
    WHERE s.id = ?
");
$stmt->execute([$id]);
$sale = $stmt->fetch();

if (!$sale) {
    die("Receipt not found.");
}

// Fetch Items
$stmt = $pdo->prepare("
    SELECT si.*, p.name, p.sku 
    FROM sales_items si 
    JOIN products p ON p.id = si.product_id 
    WHERE si.sale_id = ?
");
$stmt->execute([$id]);
$items = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #<?= htmlspecialchars($sale['invoice_no']) ?></title>
    <style>
        body { font-family: 'Courier New', Courier, monospace; font-size: 14px; max-width: 400px; margin: 0 auto; color: #000; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 1px dashed #000; padding-bottom: 10px; }
        .company { font-size: 18px; font-weight: bold; }
        .meta { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td { text-align: left; padding: 5px 0; border-bottom: 1px dotted #ccc; }
        .right { text-align: right; }
        .total-row { font-weight: bold; border-top: 1px solid #000; border-bottom: 1px solid #000; }
        .footer { text-align: center; margin-top: 20px; font-size: 12px; border-top: 1px dashed #000; padding-top: 10px; }
        @media print {
            body { max-width: 100%; width: 100%; margin: 0; padding: 0; }
            .no-print { display: none; }
        }
        .btn { display: inline-block; background: #000; color: #fff; text-decoration: none; padding: 8px 16px; margin: 10px 0; border-radius: 4px; font-family: sans-serif; cursor: pointer; }
    </style>
</head>
<body>

    <div class="header">
        <div class="company">WAREHOUSE SYSTEM</div>
        <div>Branch: <?= htmlspecialchars($sale['branch_name']) ?></div>
        <div>Tel: +94 77 123 4567</div>
    </div>

    <div class="meta">
        <div>INV: <?= htmlspecialchars($sale['invoice_no']) ?></div>
        <div><?= date('Y-m-d H:i', strtotime($sale['created_at'])) ?></div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th class="right">Qty</th>
                <th class="right">Price</th>
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): 
                $original_price = $item['unit_price'] * $item['qty'];
            ?>
                <tr>
                    <td colspan="4" style="border:0; padding-bottom: 0;">
                        <?= htmlspecialchars($item['name']) ?>
                    </td>
                </tr>
                <tr>
                    <td style="padding-top:0;"></td>
                    <td class="right"><?= $item['qty'] ?></td>
                    <td class="right"><?= number_format($item['unit_price'], 2) ?></td>
                    <td class="right"><?= number_format($item['subtotal'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <table>
        <tr>
            <td>Subtotal</td>
            <td class="right"><?= number_format($sale['net_amount'] - $sale['tax_amount'] + $sale['discount_amount'], 2) ?></td>
        </tr>
        <tr>
            <td>Global Discount</td>
            <td class="right">- <?= number_format($sale['discount_amount'], 2) ?></td>
        </tr>
        <tr>
            <td>Tax</td>
            <td class="right">+ <?= number_format($sale['tax_amount'], 2) ?></td>
        </tr>
        <tr class="total-row">
            <td>NET TOTAL</td>
            <td class="right">LKR <?= number_format($sale['net_amount'], 2) ?></td>
        </tr>
    </table>

    <div class="footer">
        <p>Thank you for your purchase!</p>
        <p>No Returns or Exchanges</p>
    </div>

    <div class="header no-print" style="border:0;">
        <button class="btn" onclick="window.print()">Print Receipt</button>
        <a href="sales.php" class="btn" style="background: #ccc; color: #000;">New Sale</a>
    </div>

    <script>
        // Auto print if opened shortly after sale?
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>
