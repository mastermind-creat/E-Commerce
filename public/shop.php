<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/db.php';

// Fetch categories
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);


// Category icons (extendable)
$categoryIcons = [
    "Clothes" => "👕",
    "Jewelry" => "💍",
    "Default" => "🛍️"
];
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>
    <title>Shop</title>
</head>

<body class="bg-gray-50">
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="max-w-7xl mx-auto p-6">
        <h1 class="text-3xl font-bold text-center mb-8">🛍️ Our Shop</h1>

        <?php foreach ($categories as $cat): ?>
        <?php
      $products = $pdo->prepare("SELECT * FROM products WHERE category_id = ? AND status='active'");
      $products->execute([$cat['id']]);
      $products = $products->fetchAll(PDO::FETCH_ASSOC);
      if (!$products) continue;

      $icon = $categoryIcons[$cat['name']] ?? $categoryIcons["Default"];
      ?>

        <section class="mb-12">
            <!-- Category Title -->
            <h2 class="text-2xl font-semibold mb-6 flex items-center">
                <span class="mr-2 text-3xl"><?= $icon ?></span>
                <?= htmlspecialchars($cat['name']) ?>
            </h2>

            <!-- Product Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-6">
                <?php foreach ($products as $prod): ?>
                <?php
            $img = $pdo->prepare("SELECT image_url FROM product_images WHERE product_id = ? ORDER BY is_primary DESC LIMIT 1");
            $img->execute([$prod['id']]);
            $imgUrl = $img->fetchColumn() ?: 'assets/images/placeholder.png';

            ?>
                <div
                    class="bg-white rounded-xl shadow hover:shadow-lg transition transform hover:-translate-y-1 p-4 flex flex-col">
                    <img src="<?= htmlspecialchars($imgUrl) ?>" alt="<?= htmlspecialchars($prod['name']) ?>"
                        class="w-full h-48 object-cover rounded mb-3">
                    <h3 class="text-lg font-semibold line-clamp-1"><?= htmlspecialchars($prod['name']) ?></h3>
                    <p class="text-blue-600 font-bold mt-1">KSh <?= number_format($prod['price'], 2) ?></p>

                    <div class="mt-3 flex gap-2">
                        <a href="product.php?id=<?= $prod['id'] ?>"
                            class="flex-1 inline-block px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 text-center transition">
                            View
                        </a>
                        <form action="add_to_cart.php" method="post">
                            <input type="hidden" name="product_id" value="<?= $prod['id'] ?>">
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit"
                                class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition flex items-center justify-center">
                                🛒
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endforeach; ?>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>

</html>