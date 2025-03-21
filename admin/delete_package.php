<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$package_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$package_id) {
    header('Location: dashboard.php');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();

// Verificar se existem investimentos ativos neste pacote
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM investments WHERE package_id = ? AND status = 'active'");
$stmt->bind_param("i", $package_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

if ($result['count'] > 0) {
    $_SESSION['error'] = "Não é possível excluir este pacote pois existem investimentos ativos vinculados a ele.";
    header('Location: dashboard.php');
    exit;
}

// Se não houver investimentos ativos, excluir o pacote
$stmt = $conn->prepare("DELETE FROM investment_packages WHERE id = ?");
$stmt->bind_param("i", $package_id);

if ($stmt->execute()) {
    $_SESSION['success'] = "Pacote excluído com sucesso!";
} else {
    $_SESSION['error'] = "Erro ao excluir o pacote.";
}

header('Location: dashboard.php');
exit;
?>