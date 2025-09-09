<?php
require_once __DIR__ . '/../includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$productId = $_GET['product_id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $rating = $_POST['rating'];
    $comment = $_POST['comment'];

    $stmt = $pdo->prepare("INSERT INTO reviews (user_id, product_id, rating, comment) VALUES (?, ?, ?, ?)");
    $stmt->execute([$userId, $productId, $rating, $comment]);

    header("Location: orders.php");
    exit;
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Leave Review</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50 text-gray-800">
    <div class="max-w-md mx-auto p-6 bg-white shadow rounded-xl mt-12">
        <h1 class="text-xl font-bold mb-4">Leave a Review</h1>
        <form method="post" class="space-y-4">
            <label class="block">
                <span class="text-gray-700">Rating (1-5)</span>
                <input type="number" name="rating" min="1" max="5" required
                    class="mt-1 block w-full border rounded p-2">
            </label>
            <label class="block">
                <span class="text-gray-700">Comment</span>
                <textarea name="comment" rows="3" class="mt-1 block w-full border rounded p-2"></textarea>
            </label>
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">Submit
                Review</button>
        </form>
    </div>
</body>

</html>