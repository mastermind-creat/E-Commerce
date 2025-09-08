<?php
require_once "../includes/db.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST['name']);
    $slug = strtolower(str_replace(" ", "-", $name));

    $stmt = $pdo->prepare("INSERT INTO categories (name, slug) VALUES (?, ?)");
    $stmt->execute([$name, $slug]);

    header("Location: categories.php?success=1");
    exit;
}
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <script src="https://cdn.tailwindcss.com"></script>
    <title>Add Category</title>
</head>

<body class="p-6 bg-gray-100">
    <div class="max-w-lg mx-auto bg-white p-6 rounded shadow">
        <h1 class="text-xl font-bold mb-4">Add Category</h1>
        <form method="post">
            <label class="block mb-2">Category Name</label>
            <input type="text" name="name" required class="w-full border p-2 rounded mb-4">
            <button class="bg-blue-600 text-white px-4 py-2 rounded">Save</button>
        </form>
    </div>
</body>

</html>