<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/auth.php';

function columnExists(PDO $pdo, string $table, string $column): bool {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
    $stmt->execute([$table, $column]);
    return $stmt->fetchColumn() > 0;
}

$messages = [];
$errors = [];

try {
    // Ensure product_variants table exists
    $pdo->query("CREATE TABLE IF NOT EXISTS product_variants (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT,
        color VARCHAR(50) NULL,
        size VARCHAR(50) NULL,
        stock INT DEFAULT 0,
        extra_price DECIMAL(10,2) DEFAULT 0,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");
    $messages[] = 'Ensured product_variants table exists.';

    // Migrate variant_stock -> stock
    if (columnExists($pdo, 'product_variants', 'variant_stock') && !columnExists($pdo, 'product_variants', 'stock')) {
        $pdo->exec("ALTER TABLE product_variants ADD COLUMN stock INT DEFAULT 0 AFTER size");
        $pdo->exec("UPDATE product_variants SET stock = COALESCE(variant_stock, 0)");
        $messages[] = 'Added stock column and migrated values from variant_stock.';
    }

    // Add stock if missing
    if (!columnExists($pdo, 'product_variants', 'stock')) {
        $pdo->exec("ALTER TABLE product_variants ADD COLUMN stock INT DEFAULT 0 AFTER size");
        $messages[] = 'Added missing product_variants.stock column.';
    }

    // Add extra_price if missing
    if (!columnExists($pdo, 'product_variants', 'extra_price')) {
        $pdo->exec("ALTER TABLE product_variants ADD COLUMN extra_price DECIMAL(10,2) DEFAULT 0 AFTER stock");
        $messages[] = 'Added missing product_variants.extra_price column.';
    }

    // Ensure color/size columns exist
    if (!columnExists($pdo, 'product_variants', 'color')) {
        $pdo->exec("ALTER TABLE product_variants ADD COLUMN color VARCHAR(50) NULL AFTER product_id");
        $messages[] = 'Added color column to product_variants.';
    }
    if (!columnExists($pdo, 'product_variants', 'size')) {
        $pdo->exec("ALTER TABLE product_variants ADD COLUMN size VARCHAR(50) NULL AFTER color");
        $messages[] = 'Added size column to product_variants.';
    }

    // Migrate variant_name -> size (fallback) - only if variant_name exists and size is still empty
    if (columnExists($pdo, 'product_variants', 'variant_name')) {
        $pdo->exec("UPDATE product_variants SET size = COALESCE(size, variant_name) WHERE (size IS NULL OR size = '') AND variant_name IS NOT NULL");
        $messages[] = 'Migrated variant_name values into size where size was empty.';
    }

    // Remove variant_name column if it exists and has been migrated/is no longer needed
    if (columnExists($pdo, 'product_variants', 'variant_name')) {
        $pdo->exec("ALTER TABLE product_variants DROP COLUMN variant_name");
        $messages[] = 'Dropped legacy product_variants.variant_name column.';
    }

    // Normalize NULLs
    $pdo->exec("UPDATE product_variants SET stock = 0 WHERE stock IS NULL");
    $pdo->exec("UPDATE product_variants SET extra_price = 0 WHERE extra_price IS NULL");
    $messages[] = 'Normalized NULLs in product_variants.';

    // Ensure products.stock exists
    if (!columnExists($pdo, 'products', 'stock')) {
        $pdo->exec("ALTER TABLE products ADD COLUMN stock INT DEFAULT 0 AFTER price");
        $messages[] = 'Added missing products.stock column.';
    }

    // Ensure products.sku exists
    if (!columnExists($pdo, 'products', 'sku')) {
        $pdo->exec("ALTER TABLE products ADD COLUMN sku VARCHAR(100) NULL AFTER name");
        $messages[] = 'Added missing products.sku column.';
    }

    // Sync products.stock with sum of variants if variants exist
    $pdo->exec("UPDATE products p
                JOIN (
                    SELECT product_id, SUM(stock) AS sum_stock FROM product_variants GROUP BY product_id
                ) v ON v.product_id = p.id
                SET p.stock = COALESCE(v.sum_stock, 0)");
    $messages[] = 'Updated products.stock to sum of variant stock.';

    // Drop orders.notes if it exists to avoid strict mode errors
    if (columnExists($pdo, 'orders', 'notes')) {
        $pdo->exec("ALTER TABLE orders DROP COLUMN notes");
        $messages[] = 'Dropped orders.notes column.';
    }

    // Ensure users.default_address exists for checkout defaults
    if (!columnExists($pdo, 'users', 'default_address')) {
        $pdo->exec("ALTER TABLE users ADD COLUMN default_address TEXT NULL AFTER phone");
        $messages[] = 'Added users.default_address column.';
    }

} catch (Exception $e) {
    $errors[] = $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Maintenance - Fix Schema</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 min-h-screen">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <main class="flex-1 p-6 md:ml-64">
        <h1 class="text-2xl font-bold mb-4">Database Maintenance</h1>
        <p class="text-gray-600 mb-6">Align schema for variants and stock, and normalize data.</p>

        <?php if (!empty($errors)): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 p-4 rounded mb-4">
            <h2 class="font-semibold mb-2">Errors</h2>
            <ul class="list-disc ml-6">
                <?php foreach ($errors as $err): ?>
                <li><?= htmlspecialchars($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <?php if (!empty($messages)): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 p-4 rounded mb-4">
            <h2 class="font-semibold mb-2">Actions</h2>
            <ul class="list-disc ml-6">
                <?php foreach ($messages as $msg): ?>
                <li><?= htmlspecialchars($msg) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <div class="bg-white rounded-xl shadow p-6">
            <a href="fix_schema.php"
                class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Run
                Again</a>
        </div>
    </main>
</body>

</html>