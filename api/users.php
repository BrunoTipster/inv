<?php
/**
 * API de Usuários
 * 
 * Endpoint para gerenciamento de usuários
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
            // Verificar permissão admin para listagem
            if ($_SESSION['user_type'] !== 'admin' && $action == 'list') {
                throw new Exception('Permissão negada');
            }

            switch ($action) {
                case 'list':
                    // Listar usuários (admin only)
                    $status = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_STRING);
                    $search = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_STRING);

                    $query = "SELECT id, username, email, full_name, type, status, created_at, last_login FROM users WHERE 1=1";
                    $params = [];
                    $types = "";

                    if ($status) {
                        $query .= " AND status = ?";
                        $params[] = $status;
                        $types .= "s";
                    }

                    if ($search) {
                        $query .= " AND (username LIKE ? OR email LIKE ? OR full_name LIKE ?)";
                        $search = "%$search%";
                        $params = array_merge($params, [$search, $search, $search]);
                        $types .= "sss";
                    }

                    $query .= " ORDER BY created_at DESC";

                    $stmt = $conn->prepare($query);
                    if (!empty($params)) {
                        $stmt->bind_param($types, ...$params);
                    }
                    $stmt->execute();
                    $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

                    echo json_encode(['success' => true, 'data' => $users]);
                    break;

                case 'profile':
                    // Buscar perfil do usuário
                    $stmt = $conn->prepare("
                        SELECT u.*, 
                            (SELECT COUNT(*) FROM investments WHERE user_id = u.id) as total_investments,
                            (SELECT SUM(amount) FROM investments WHERE user_id = u.id) as total_invested
                        FROM users u 
                        WHERE u.id = ?
                    ");
                    $stmt->bind_param("i", $_SESSION['user_id']);
                    $stmt->execute();
                    $profile = $stmt->get_result()->fetch_assoc();

                    // Remover dados sensíveis
                    unset($profile['password']);
                    echo json_encode(['success' => true, 'data' => $profile]);
                    break;

                default:
                    throw new Exception('Ação não reconhecida');
            }
            break;

        case 'POST':
            if ($action == 'update_profile') {
                $data = json_decode(file_get_contents('php://input'), true);

                // Validar dados
                if (empty($data['full_name']) || empty($data['phone'])) {
                    throw new Exception('Dados incompletos');
                }

                // Upload de foto de perfil
                if (isset($_FILES['profile_photo'])) {
                    $photo = $_FILES['profile_photo'];
                    $allowed = ['jpg', 'jpeg', 'png'];
                    $ext = strtolower(pathinfo($photo['name'], PATHINFO_EXTENSION));

                    if (!in_array($ext, $allowed)) {
                        throw new Exception('Tipo de arquivo não permitido');
                    }

                    $photo_name = 'profile_' . $_SESSION['user_id'] . '_' . time() . '.' . $ext;
                    $photo_path = '../uploads/profiles/' . $photo_name;

                    if (!move_uploaded_file($photo['tmp_name'], $photo_path)) {
                        throw new Exception('Erro ao fazer upload da foto');
                    }

                    $data['profile_photo'] = 'profiles/' . $photo_name;
                }

                // Atualizar perfil
                $query = "UPDATE users SET full_name = ?, phone = ?";
                $params = [$data['full_name'], $data['phone']];
                $types = "ss";

                if (isset($data['profile_photo'])) {
                    $query .= ", profile_photo = ?";
                    $params[] = $data['profile_photo'];
                    $types .= "s";
                }

                $query .= " WHERE id = ?";
                $params[] = $_SESSION['user_id'];
                $types .= "i";

                $stmt = $conn->prepare($query);
                $stmt->bind_param($types, ...$params);
                
                if ($stmt->execute()) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Perfil atualizado com sucesso'
                    ]);
                } else {
                    throw new Exception('Erro ao atualizar perfil');
                }
            } else {
                throw new Exception('Ação não reconhecida');
            }
            break;

        case 'PUT':
            if ($_SESSION['user_type'] !== 'admin') {
                throw new Exception('Permissão negada');
            }

            $data = json_decode(file_get_contents('php://input'), true);
            $user_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

            if (!$user_id || empty($data['status'])) {
                throw new Exception('Dados inválidos');
            }

            // Atualizar status do usuário
            $stmt = $conn->prepare("
                UPDATE users 
                SET status = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            $stmt->bind_param("si", $data['status'], $user_id);
            
            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Status atualizado com sucesso'
                ]);
            } else {
                throw new Exception('Erro ao atualizar status');
            }
            break;

        default:
            throw new Exception('Método não permitido');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}