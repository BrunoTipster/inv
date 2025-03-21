<?php
/**
 * API de Investimentos
 * 
 * Endpoint para gerenciamento de investimentos
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
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
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
                    // Listar investimentos do usuário
                    $stmt = $conn->prepare("
                        SELECT i.*, p.name as package_name, p.return_rate 
                        FROM investments i 
                        JOIN investment_packages p ON i.package_id = p.id 
                        WHERE i.user_id = ? 
                        ORDER BY i.created_at DESC
                    ");
                    $stmt->bind_param("i", $_SESSION['user_id']);
                    $stmt->execute();
                    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                    echo json_encode(['success' => true, 'data' => $result]);
                    break;

                case 'details':
                    // Detalhes de um investimento específico
                    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
                    if (!$id) {
                        throw new Exception('ID inválido');
                    }

                    $stmt = $conn->prepare("
                        SELECT i.*, p.name as package_name, p.return_rate,
                               (SELECT COUNT(*) FROM investment_returns 
                                WHERE investment_id = i.id) as total_returns,
                               (SELECT SUM(amount) FROM investment_returns 
                                WHERE investment_id = i.id) as total_return_amount
                        FROM investments i 
                        JOIN investment_packages p ON i.package_id = p.id 
                        WHERE i.id = ? AND i.user_id = ?
                    ");
                    $stmt->bind_param("ii", $id, $_SESSION['user_id']);
                    $stmt->execute();
                    $investment = $stmt->get_result()->fetch_assoc();

                    if (!$investment) {
                        throw new Exception('Investimento não encontrado');
                    }

                    // Buscar retornos do investimento
                    $stmt = $conn->prepare("
                        SELECT * FROM investment_returns 
                        WHERE investment_id = ? 
                        ORDER BY created_at DESC
                    ");
                    $stmt->bind_param("i", $id);
                    $stmt->execute();
                    $returns = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

                    $investment['returns'] = $returns;
                    echo json_encode(['success' => true, 'data' => $investment]);
                    break;

                case 'stats':
                    // Estatísticas dos investimentos
                    $stmt = $conn->prepare("
                        SELECT 
                            COUNT(*) as total_investments,
                            SUM(amount) as total_invested,
                            SUM(return_amount) as total_returns,
                            COUNT(CASE WHEN status = 'active' THEN 1 END) as active_investments
                        FROM investments 
                        WHERE user_id = ?
                    ");
                    $stmt->bind_param("i", $_SESSION['user_id']);
                    $stmt->execute();
                    $stats = $stmt->get_result()->fetch_assoc();
                    echo json_encode(['success' => true, 'data' => $stats]);
                    break;

                default:
                    throw new Exception('Ação não reconhecida');
            }
            break;

        case 'POST':
            // Criar novo investimento
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['package_id'], $data['amount'])) {
                throw new Exception('Dados incompletos');
            }

            // Validar pacote e valor
            $stmt = $conn->prepare("
                SELECT * FROM investment_packages 
                WHERE id = ? AND status = 'active'
            ");
            $stmt->bind_param("i", $data['package_id']);
            $stmt->execute();
            $package = $stmt->get_result()->fetch_assoc();

            if (!$package) {
                throw new Exception('Pacote inválido ou inativo');
            }

            if ($data['amount'] < $package['min_amount'] || $data['amount'] > $package['max_amount']) {
                throw new Exception('Valor fora dos limites permitidos');
            }

            // Criar investimento
            $conn->begin_transaction();

            try {
                // Inserir investimento
                $stmt = $conn->prepare("
                    INSERT INTO investments (
                        user_id, package_id, amount, return_amount, 
                        next_return_date, status, created_at
                    ) VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL ? DAY), 'active', NOW())
                ");
                
                $return_amount = $data['amount'] * ($package['return_rate'] / 100);
                $stmt->bind_param("iiddi", 
                    $_SESSION['user_id'],
                    $data['package_id'],
                    $data['amount'],
                    $return_amount,
                    $package['period_days']
                );
                $stmt->execute();
                $investment_id = $conn->insert_id;

                // Deduzir do saldo
                $stmt = $conn->prepare("
                    UPDATE users 
                    SET balance = balance - ? 
                    WHERE id = ? AND balance >= ?
                ");
                $stmt->bind_param("did", 
                    $data['amount'],
                    $_SESSION['user_id'],
                    $data['amount']
                );
                
                if (!$stmt->execute() || $stmt->affected_rows == 0) {
                    throw new Exception('Saldo insuficiente');
                }

                $conn->commit();
                echo json_encode([
                    'success' => true,
                    'message' => 'Investimento criado com sucesso',
                    'investment_id' => $investment_id
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