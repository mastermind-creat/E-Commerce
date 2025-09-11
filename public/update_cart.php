<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $qty = max(1, intval($_POST['quantity']));
    if (isset($_SESSION['cart'][$id])) {
        $_SESSION['cart'][$id]['quantity'] = $qty;
    }
}
header("Location: cart.php");
exit;