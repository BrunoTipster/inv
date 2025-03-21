<?php
/**
 * API de Transações
 * 
 * Endpoint para gerenciamento de transações
 * 
 * @package InvestSystem
 * @version 1.0.0
 * @author Bruno Tipster
 * @copyright 2025 InvestSystem
 */

require_once '../includes/config.php';

// Verificar método HTTP
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Headers da API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . SITE_URL);
header('Access-Control-Allow-Methods: GET, POST, PUT');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Verificar autenticação
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Rate limiting
$rate_limit = checkRateLimit($_SESSION['user_id']);
if (!$rate_limit['allowed']) {
    http_response_code(429);
    echo json_encode([
        'error' => 'Too Many Requests',
        'retry_after' => $rate_limit['retry_after']
    ]);
    exit;
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    switch ($method) {
        case 'GET':
            switch ($action) {
                case 'list':
                    // Parâmetros de filtro
                    $type = filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING);
                    $status = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_STRING);
                    $start_date = filter_input(INPUT_GET, 'start_date', FILTER_SANITIZE_STRING);
                    $end_date = filter_input(INPUT_GET, 'end_date', FILTER_SANITIZE_STRING);

                    // Construir query
                    $query = "SELECT * FROM transactions WHERE user_id = ?";
                    $params = [$_SESSION['user_id']];
                    $types = "i";

                    if ($type) {
                        $query .= " AND type = ?";
                        $params[] = $type;
                        $types .= "s";
                    }

                    if ($status) {
                        $query .= " AND status = ?";
                        $params[] = $status;
                        $types .= "s";
                    }

                    if ($start_date) {
                        $query .= " AND DATE(created_at) >= ?";
                        $params[] = $start_date;
                        $types .= "s";
                    }

                    if ($end_date) {
                        $query .= " AND DATE(created_at) <= ?";
                        $params[] = $end_date;
                        $types .= "s";
                    }

                    $query .= " ORDER BY created_at DESC";

                    $stmt = $conn->prepare($query);
                    $stmt->bind_param($types, ...$params);
                    $stmt->execute();
                    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

                    echo json_encode(['success' => true, 'data' => $result]);
                    break;

                case 'summary':
                    // Sumário de transações
                    $stmt = $conn->prepare("
                        SELECT 
                            SUM(CASE WHEN type = 'deposit' AND status = 'completed' THEN amount ELSE 0 END) as total_deposits,
                            SUM(CASE WHEN type = 'withdrawal' AND status = 'completed' THEN amount ELSE 0 END) as total_withdrawals,
                            SUM(CASE WHEN type = 'investment' AND status = 'completed' THEN amount ELSE 0 END) as total_investments,
                            SUM(CASE WHEN type = 'return' AND status = 'completed' THEN amount ELSE 0 END) as total_returns
                        FROM transactions 
                        WHERE user_id = ?
                    ");
                    $stmt->bind_param("i", $_SESSION['user_id']);
                    $stmt->execute();
                    $summary = $stmt->get_result()->fetch_assoc();

                    echo json_encode(['success' => true, 'data' => $summary]);
                    break;

                case 'export':
                    // Exportar transações
                    $stmt = $conn->prepare("
                        SELECT 
                            transaction_code,
                            type,
                            amount,
                            status,
                            created_at,
                            description
                        FROM transactions 
                        WHERE user_id = ?
                        ORDER BY created_at DESC
                    ");
                    $stmt->bind_param("i", $_SESSION['user_id']);
                    $stmt->execute();
                    $transactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

                    // Gerar CSV
                    $filename = "transactions_" . date('Y-m-d_His') . ".csv";
                    header('Content-Type: text/csv');
                    header('Content-Disposition: attachment; filename="' . $filename . '"');

                    $output = fopen('php://output', 'w');
                    fputcsv($output, array_keys($transactions[0]));
                    
                    foreach ($transactions as $row) {
                        fputcsv($output, $row);
                    }
                    fclose($output);
                    exit;
                    break;

                default:
                    throw new Exception('Ação não reconhecida');
            }
            break;

        case 'POST':
            // Criar nova transação
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['type'], $data['amount'])) {
                throw new Exception('Dados incompletos');
            }

            // Validar tipo e valor
            if (!in_array($data['type'], ['deposit', 'withdrawal'])) {
                throw new Exception('Tipo de transação inválido');
            }

            if ($data['type'] == 'deposit' && $data['amount'] < MIN_DEPOSIT) {
                throw new Exception('Valor mínimo para depósito: R$ ' . MIN_DEPOSIT);
            }

            if ($data['type'] == 'withdrawal' && $data['amount'] < MIN_WITHDRAWAL) {
                throw new Exception('Valor mínimo para saque: R$ ' . MIN_WITHDRAWAL);
            }

            // Processar transação
            $conn->begin_transaction();

            try {
                $transaction_code = generateTransactionCode($data['type'] == 'deposit' ? 'DEP' : 'WIT');
                
                $stmt = $conn->prepare("
                    INSERT INTO transactions (
                        user_id, type, amount, status, 
                        transaction_code, description, created_at
                    ) VALUES (
                        ?, ?, ?, 'pending', 
                        ?, ?, NOW()
                    )
                ");
                
                $description = $data['type'] == 'deposit' 
                    ? 'Depósito via ' . ($data['payment_method'] ?? 'não especificado')
                    : 'Solicitação de saque';

                $stmt->bind_param("isdss", 
                    $_SESSION['user_id'],
                    $data['type'],
                    $data['amount'],
                    $transaction_code,
                    $description
                );
                $stmt->execute();

                $transaction_id = $conn->insert_id;

                $conn->commit();
                echo json_encode([
                    'success' => true,
                    'message' => 'Transação registrada com sucesso',
                    'transaction_id' => $transaction_id,
                    'transaction_code' => $transaction_code
                ]);

            } catch (Exception $e) {
                $conn->rollback();
                throw $e;
            }
            break;

        default:
            throw new Exception('Método não permitido');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}