<?php
require_once 'auth_check.php';
requireRole(['Admin', 'Warehouse Staff']);
require_once 'db.php';
$pdo = db();

$today = date('Y-m-d');
$thisMonth = date('Y-m');
$thisYear = date('Y');

// 1. Sales Summaries
$dailySales = $pdo->prepare("SELECT SUM(net_amount) as total FROM sales WHERE DATE(created_at) = ?");
$dailySales->execute([$today]);
$dailySalesAmt = (float) ($dailySales->fetch()['total'] ?? 0);

$monthlySales = $pdo->prepare("SELECT SUM(net_amount) as total FROM sales WHERE DATE_FORMAT(created_at, '%Y-%m') = ?");
$monthlySales->execute([$thisMonth]);
$monthlySalesAmt = (float) ($monthlySales->fetch()['total'] ?? 0);

$monthlyPurchase = $pdo->prepare("SELECT SUM(qty * purchase_price) as total FROM movements m JOIN products p ON m.product_id = p.id WHERE m.movement_type = 'IN' AND DATE_FORMAT(m.created_at, '%Y-%m') = ?");
$monthlyPurchase->execute([$thisMonth]);
$monthlyPurchaseAmt = (float) ($monthlyPurchase->fetch()['total'] ?? 0);

$yearlySales = $pdo->prepare("SELECT SUM(net_amount) as total FROM sales WHERE YEAR(created_at) = ?");
$yearlySales->execute([$thisYear]);
$yearlySalesAmt = (float) ($yearlySales->fetch()['total'] ?? 0);

// 2. Stock Movement Summaries
$dailyMvmt = $pdo->prepare("SELECT movement_type, SUM(qty) as total FROM movements WHERE DATE(created_at) = ? GROUP BY movement_type");
$dailyMvmt->execute([$today]);
$dailyMvmtRes = $dailyMvmt->fetchAll(PDO::FETCH_KEY_PAIR);

$monthlyMvmt = $pdo->prepare("SELECT movement_type, SUM(qty) as total FROM movements WHERE DATE_FORMAT(created_at, '%Y-%m') = ? GROUP BY movement_type");
$monthlyMvmt->execute([$thisMonth]);
$monthlyMvmtRes = $monthlyMvmt->fetchAll(PDO::FETCH_KEY_PAIR);

$yearlyMvmt = $pdo->prepare("SELECT movement_type, SUM(qty) as total FROM movements WHERE YEAR(created_at) = ? GROUP BY movement_type");
$yearlyMvmt->execute([$thisYear]);
$yearlyMvmtRes = $yearlyMvmt->fetchAll(PDO::FETCH_KEY_PAIR);

// 3. Category Sales (for Pie Chart)
$catSales = $pdo->query("
    SELECT 
        COALESCE(NULLIF(p.category, ''), 'Uncategorized') as cat, 
        SUM(si.subtotal) as total
    FROM sales_items si
    JOIN products p ON si.product_id = p.id
    GROUP BY cat
    ORDER BY total DESC
")->fetchAll(PDO::FETCH_KEY_PAIR);

$catLabels = array_keys($catSales);
$catData = array_values($catSales);

// 5. Fast vs Slow Moving Items (Last 30 Days)
// Fast Moving
$fastQuery = "
    SELECT p.name, SUM(si.qty) as total_qty 
    FROM sales_items si 
    JOIN sales s ON si.sale_id = s.id 
    JOIN products p ON si.product_id = p.id 
    WHERE s.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
    GROUP BY p.id 
    ORDER BY total_qty DESC 
    LIMIT 5
";
$fastMoving = $pdo->query($fastQuery)->fetchAll();

// Slow Moving (Low sales or No sales)
// Note: This logic finds products with lowest sales volume in the period
$slowQuery = "
    SELECT p.name, COALESCE(SUM(si.qty), 0) as total_qty 
    FROM products p 
    LEFT JOIN sales_items si ON p.id = si.product_id 
    LEFT JOIN sales s ON si.sale_id = s.id AND s.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
    GROUP BY p.id 
    ORDER BY total_qty ASC 
    LIMIT 5
";
$slowMoving = $pdo->query($slowQuery)->fetchAll();


// 6. Current Stock Status
$stockRows = $pdo->query("
  SELECT p.sku, p.name AS product_name, l.name AS location_name, s.quantity
  FROM stock s
  JOIN products p ON p.id = s.product_id
  JOIN locations l ON l.id = s.location_id
  ORDER BY p.name, l.name
")->fetchAll();

require_once 'header.php';
?>

<div class="container animate-fade-in" style="padding-top: 2rem;">
  
  <!-- Page Header -->
  <div class="d-flex justify-content-between align-items-end mb-5">
    <div>
        <h6 class="text-uppercase text-muted fw-bold small tracking-wider mb-2">Performance Analytics</h6>
        <h2 class="display-5 fw-bold text-gradient mb-0">Business Intelligence</h2>
    </div>
    <div class="d-none d-md-block">
        <button class="btn btn-light border shadow-sm rounded-pill px-4" onclick="window.print()">
            <i class="fas fa-download me-2 text-muted"></i> Export Report
        </button>
    </div>
  </div>

  <!-- Analytics Dashboard -->
  <div class="row g-4 mb-5">
    
    <!-- Stock Movements Widget -->
    <div class="col-xl-8 col-lg-7">
      <div class="glass-card p-4 h-100 position-relative overflow-hidden">
          <!-- Decorative Background Blob -->
          <div style="position: absolute; top: -50px; right: -50px; width: 150px; height: 150px; background: radial-gradient(circle, rgba(99,102,241,0.1) 0%, rgba(255,255,255,0) 70%); border-radius: 50%;"></div>
          
          <div class="d-flex align-items-center mb-4">
              <div class="bg-indigo-100 rounded-circle p-3 me-3" style="background: rgba(99, 102, 241, 0.1);">
                  <i class="fas fa-truck-ramp-box text-primary fs-4"></i>
              </div>
              <div>
                  <h5 class="fw-bold mb-0">Stock Movements</h5>
                  <p class="small text-muted mb-0">Inbound vs Outbound Flow</p>
              </div>
          </div>

          <div class="row g-3">
            <!-- Today -->
            <div class="col-md-4">
              <div class="p-3 rounded-4 h-100" style="background: rgba(255, 255, 255, 0.5); border: 1px solid rgba(255,255,255,0.6);">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="small text-uppercase fw-bold text-muted op-75">Today</span>
                    <span class="badge bg-white text-dark shadow-sm px-2 rounded-pill small">24h</span>
                </div>
                <div class="d-flex justify-content-between align-items-end mb-2">
                  <span class="text-success small fw-bold"><i class="fas fa-arrow-down me-1"></i> IN</span>
                  <span class="fw-bold h5 mb-0 text-dark"><?= (int) ($dailyMvmtRes['IN'] ?? 0) ?></span>
                </div>
                <div class="d-flex justify-content-between align-items-end">
                  <span class="text-danger small fw-bold"><i class="fas fa-arrow-up me-1"></i> OUT</span>
                  <span class="fw-bold h5 mb-0 text-dark"><?= (int) ($dailyMvmtRes['OUT'] ?? 0) ?></span>
                </div>
              </div>
            </div>

            <!-- Month -->
            <div class="col-md-4">
              <div class="p-3 rounded-4 h-100" style="background: rgba(255, 255, 255, 0.5); border: 1px solid rgba(255,255,255,0.6);">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="small text-uppercase fw-bold text-muted op-75">This Month</span>
                    <i class="fas fa-calendar-alt text-muted opacity-25"></i>
                </div>
                 <div class="d-flex justify-content-between align-items-end mb-2">
                  <span class="text-success small fw-bold"><i class="fas fa-arrow-down me-1"></i> IN</span>
                  <span class="fw-bold h5 mb-0 text-dark"><?= (int) ($monthlyMvmtRes['IN'] ?? 0) ?></span>
                </div>
                <div class="d-flex justify-content-between align-items-end">
                  <span class="text-danger small fw-bold"><i class="fas fa-arrow-up me-1"></i> OUT</span>
                  <span class="fw-bold h5 mb-0 text-dark"><?= (int) ($monthlyMvmtRes['OUT'] ?? 0) ?></span>
                </div>
              </div>
            </div>

            <!-- Year -->
            <div class="col-md-4">
               <div class="p-3 rounded-4 h-100" style="background: rgba(255, 255, 255, 0.5); border: 1px solid rgba(255,255,255,0.6);">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="small text-uppercase fw-bold text-muted op-75">This Year</span>
                    <i class="fas fa-calendar text-muted opacity-25"></i>
                </div>
                 <div class="d-flex justify-content-between align-items-end mb-2">
                  <span class="text-success small fw-bold"><i class="fas fa-arrow-down me-1"></i> IN</span>
                  <span class="fw-bold h5 mb-0 text-dark"><?= (int) ($yearlyMvmtRes['IN'] ?? 0) ?></span>
                </div>
                <div class="d-flex justify-content-between align-items-end">
                  <span class="text-danger small fw-bold"><i class="fas fa-arrow-up me-1"></i> OUT</span>
                  <span class="fw-bold h5 mb-0 text-dark"><?= (int) ($yearlyMvmtRes['OUT'] ?? 0) ?></span>
                </div>
              </div>
            </div>
          </div>
      </div>
    </div>

    <!-- Side Panel: KPIs & Chart -->
    <div class="col-xl-4 col-lg-5">
        <div class="row g-3 h-100">
            <!-- KPI 1 -->
            <div class="col-12">
              <div class="glass-card p-3 border-0 d-flex align-items-center justify-content-between" style="background: linear-gradient(135deg, rgba(255,255,255,0.8), rgba(255,255,255,0.4));">
                <div>
                    <div class="small text-uppercase fw-bold text-muted mb-1">Today's Sales</div>
                    <div class="h3 fw-bold mb-0 text-primary">LKR <?= number_format($dailySalesAmt, 2) ?></div>
                </div>
                <div class="stat-icon bg-primary-subtle text-primary mb-0 shadow-sm" style="width: 48px; height: 48px; font-size: 1.25rem;">
                    <i class="fas fa-wallet"></i>
                </div>
              </div>
            </div>

            <!-- KPI 2 -->
             <div class="col-12">
              <div class="glass-card p-3 border-0 d-flex align-items-center justify-content-between">
                <div>
                   <div class="small text-muted text-uppercase fw-bold mb-1">Monthly Revenue</div>
                   <div class="h3 fw-bold mb-0 text-dark">LKR <?= number_format($monthlySalesAmt, 2) ?></div>
                </div>
                <div class="stat-icon bg-success-subtle text-success mb-0 shadow-sm" style="width: 48px; height: 48px; font-size: 1.25rem;">
                    <i class="fas fa-chart-line"></i>
                </div>
              </div>
            </div>

            <!-- KPI 3 -->
             <div class="col-12">
              <div class="glass-card p-3 border-0 d-flex align-items-center justify-content-between">
                <div>
                   <div class="small text-muted text-uppercase fw-bold mb-1">Stock Investment</div>
                   <div class="h3 fw-bold mb-0 text-dark">LKR <?= number_format($monthlyPurchaseAmt, 2) ?></div>
                   <span class="badge bg-warning-subtle text-warning rounded-pill px-2" style="font-size: 0.7rem;">Monthly</span>
                </div>
                 <div class="stat-icon bg-warning-subtle text-warning mb-0 shadow-sm" style="width: 48px; height: 48px; font-size: 1.25rem;">
                    <i class="fas fa-coins"></i>
                </div>
              </div>
            </div>
            
            <!-- Pie Chart in Side Panel -->
            <div class="col-12 mt-2">
                <div class="glass-card p-3 border-0">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                         <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-chart-pie me-2 text-info"></i> Sales Dist.</h6>
                    </div>
                    <div style="height: 180px;">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>
            </div>

        </div>
    </div>
    
  </div>

  <!-- Fast & Slow Moving Items -->
  <div class="row g-4 mb-5">
      <!-- Fast Moving -->
      <div class="col-md-6">
          <div class="glass-card p-4 h-100">
              <h5 class="fw-bold mb-4 d-flex align-items-center text-dark">
                  <span class="bg-success-subtle text-success rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width:40px; height:40px;"><i class="fas fa-rocket"></i></span>
                  Fast Moving Items
              </h5>
              <?php if(empty($fastMoving)): ?>
                  <p class="text-muted text-center py-4">No sales data available yet.</p>
              <?php else: ?>
                  <div class="d-flex flex-column gap-3">
                      <?php foreach($fastMoving as $item): ?>
                          <?php 
                             // Calculate rough percentage relative to top item for visual bar
                             $max = $fastMoving[0]['total_qty'];
                             $pct = ($item['total_qty'] / $max) * 100;
                          ?>
                          <div>
                              <div class="d-flex justify-content-between align-items-end mb-1">
                                  <span class="fw-bold text-dark"><?= htmlspecialchars($item['name']) ?></span>
                                  <span class="badge bg-success-subtle text-success rounded-pill"><?= (int)$item['total_qty'] ?> Sold</span>
                              </div>
                              <div class="progress" style="height: 6px; background-color: #f1f5f9;">
                                  <div class="progress-bar bg-success" role="progressbar" style="width: <?= $pct ?>%; border-radius: 10px;"></div>
                              </div>
                          </div>
                      <?php endforeach; ?>
                  </div>
              <?php endif; ?>
          </div>
      </div>

      <!-- Slow Moving -->
      <div class="col-md-6">
          <div class="glass-card p-4 h-100">
              <h5 class="fw-bold mb-4 d-flex align-items-center text-dark">
                  <span class="bg-danger-subtle text-danger rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width:40px; height:40px;"><i class="fas fa-hourglass-half"></i></span>
                  Slow Moving Items
              </h5>
              <?php if(empty($slowMoving)): ?>
                  <p class="text-muted text-center py-4">All items seem to be moving well!</p>
              <?php else: ?>
                  <div class="d-flex flex-column gap-3">
                      <?php foreach($slowMoving as $item): ?>
                          <div class="d-flex justify-content-between align-items-center p-2 rounded-3" style="background: rgba(241, 245, 249, 0.5);">
                              <span class="text-muted fw-medium"><?= htmlspecialchars($item['name']) ?></span>
                              <?php if($item['total_qty'] == 0): ?>
                                  <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">No Sales</span>
                              <?php else: ?>
                                  <span class="badge bg-warning-subtle text-warning border border-warning-subtle"><?= (int)$item['total_qty'] ?> Sold</span>
                              <?php endif; ?>
                          </div>
                      <?php endforeach; ?>
                  </div>
              <?php endif; ?>
          </div>
      </div>
  </div>

  <!-- Detailed Stock Table -->
  <div class="glass-card p-0 border-0 overflow-hidden shadow-sm mb-5">
    <div class="p-4 border-bottom bg-white bg-opacity-50 backdrop-blur d-flex justify-content-between align-items-center">
      <div>
        <h5 class="fw-bold mb-1">Current Inventory</h5>
        <p class="small text-muted mb-0">Real-time stock levels across all locations</p>
      </div>
      <div class="input-group" style="width: 250px;">
        <span class="input-group-text bg-transparent border-end-0"><i class="fas fa-search text-muted"></i></span>
        <input type="text" class="form-control border-start-0 bg-transparent ps-0" placeholder="Search item...">
      </div>
    </div>
    <div class="table-responsive">
      <table class="table table-hover table-glass mb-0 align-middle">
        <thead class="bg-light bg-opacity-50">
          <tr>
            <th class="px-4 py-3 text-secondary ps-5">SKU</th>
            <th class="px-4 py-3 text-secondary">Product Name</th>
            <th class="px-4 py-3 text-secondary">Location</th>
            <th class="px-4 py-3 text-secondary text-center">Available Qty</th>
            <th class="px-4 py-3 text-secondary text-end pe-5">Status</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($stockRows)): ?>
            <tr>
              <td colspan="5" class="text-center py-5 text-muted">
                  <div class="py-4">
                      <i class="fas fa-box-open fs-1 text-muted opacity-25 mb-3"></i>
                      <p class="mb-0">No inventory data available.</p>
                  </div>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($stockRows as $r): ?>
                <?php 
                    $statusClass = 'bg-success-subtle text-success';
                    $statusText = 'In Stock';
                    if($r['quantity'] <= 5) {
                        $statusClass = 'bg-warning-subtle text-warning';
                        $statusText = 'Low Stock';
                    }
                    if($r['quantity'] == 0) {
                        $statusClass = 'bg-danger-subtle text-danger';
                        $statusText = 'Out of Stock';
                    }
                ?>
              <tr>
                <td class="px-4 py-3 font-monospace small text-muted ps-5"><?= htmlspecialchars($r['sku']) ?></td>
                <td class="px-4 py-3 fw-bold text-dark"><?= htmlspecialchars($r['product_name']) ?></td>
                <td class="px-4 py-3">
                    <span class="d-inline-flex align-items-center">
                        <i class="fas fa-map-pin text-primary opacity-50 me-2 small"></i>
                        <?= htmlspecialchars($r['location_name']) ?>
                    </span>
                </td>
                <td class="px-4 py-3 text-center">
                  <span class="fw-bold fs-6 text-dark"><?= (int) $r['quantity'] ?></span>
                </td>
                <td class="px-4 py-3 text-end pe-5">
                    <span class="badge rounded-pill <?= $statusClass ?> px-3 py-2" style="font-weight: 600;">
                        <?= $statusText ?>
                    </span>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <div class="p-3 border-top bg-light bg-opacity-50 text-end">
        <small class="text-muted fst-italic">Data updated at <?= date('H:i A') ?></small>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // --- CATEGORY PIE CHART ---
    const ctxCat = document.getElementById('categoryChart').getContext('2d');
    
    new Chart(ctxCat, {
        type: 'pie',
        data: {
            labels: <?= json_encode($catLabels) ?>,
            datasets: [{
                data: <?= json_encode($catData) ?>,
                backgroundColor: [
                    '#3b82f6', // blue
                    '#a855f7', // purple
                    '#f43f5e', // rose
                    '#10b981', // emerald
                    '#f59e0b', // amber
                    '#06b6d4', // cyan
                    '#6366f1'  // indigo
                ],
                borderWidth: 1,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: { usePointStyle: true, boxWidth: 10, font: { family: "'Inter', sans-serif", size: 11 } }
                },
                tooltip: {
                     backgroundColor: 'rgba(255, 255, 255, 0.95)',
                    titleColor: '#1e293b',
                    bodyColor: '#475569',
                    borderColor: '#e2e8f0',
                    borderWidth: 1,
                    padding: 10,
                    bodyFont: { family: "'Inter', sans-serif" },
                    callbacks: {
                        label: function(context) {
                            let val = context.parsed;
                            return ' LKR ' + val.toLocaleString();
                        }
                    }
                }
            }
        }
    });
</script>

<?php require_once 'footer.php'; ?>
