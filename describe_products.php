<?php
require_once 'db.php';
$pdo = db();
$stmt = $pdo->query("DESCRIBE products");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . " " . $row['Type'] . "\n";
}
?>