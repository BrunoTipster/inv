<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Verifica se é admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$pageTitle = "Gerenciamento de Usuários";
require_once '../includes/admin_header.php';

$db = Database::getInstance();
$conn = $db->getConnection();

// Ações de gerenciamento
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    $action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);
    
    if ($user_id && $action) {
        switch ($action) {
            case 'activate':
                $stmt = $conn->prepare("UPDATE users SET status = 'active' WHERE id = ?");
                break;
            case 'suspend':
                $stmt = $conn->prepare("UPDATE users SET status = 'suspended' WHERE id = ?");
                break;
            case 'delete':
                // Primeiro verifica se tem investimentos ativos
                $check = $conn->prepare("SELECT COUNT(*) as count FROM investments WHERE user_id = ? AND status = 'active'");
                $check->bind_param("i", $user_id);
                $check->execute();
                $result = $check->get_result()->fetch_assoc();
                
                if ($result['count'] == 0) {
                    $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND type != 'admin'");
                }
                break;
        }
        
        if (isset($stmt)) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            
            if ($stmt->affected_rows > 0) {
                $success = "Ação realizada com sucesso!";
            }
        }
    }
}

// Buscar usuários
$users = $conn->query("
    SELECT 
        u.*,
        COUNT(DISTINCT i.id) as total_investments,
        SUM(CASE WHEN i.status = 'active' THEN i.amount ELSE 0 END) as active_investments,
        COUNT(DISTINCT t.id) as total_transactions
    FROM users u
    LEFT JOIN investments i ON u.id = i.user_id
    LEFT JOIN transactions t ON u.id = t.user_id
    WHERE u.type = 'client'
    GROUP BY u.id
    ORDER BY u.created_at DESC
")->fetch_all(MYSQLI_ASSOC);
?>

<div class="container-fluid">
    <?php if(isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Usuários do Sistema</h6>
            <div class="d-flex">
                <input type="text" id="searchUser" class="form-control me-2" placeholder="Buscar usuário...">
                <button class="btn btn-primary" onclick="exportUsers()">
                    <i class="bi bi-download"></i> Exportar
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="usersTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Investimentos</th>
                            <th>Saldo</th>
                            <th>Status</th>
                            <th>Cadastro</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td>
                                <?php echo htmlspecialchars($user['full_name']); ?><br>
                                <small class="text-muted"><?php echo $user['username']; ?></small>
                            </td>
                            <td><?php echo $user['email']; ?></td>
                            <td>
                                Total: <?php echo $user['total_investments']; ?><br>
                                <small class="text-muted">
                                    Ativos: R$ <?php echo number_format($user['active_investments'] ?? 0, 2, ',', '.'); ?>
                                </small>
                            </td>
                            <td>R$ <?php echo number_format($user['balance'], 2, ',', '.'); ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $user['status'] == 'active' ? 'success' : 
                                        ($user['status'] == 'suspended' ? 'danger' : 'warning'); 
                                ?>">
                                    <?php echo ucfirst($user['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-primary dropdown-toggle" 
                                            data-bs-toggle="dropdown">
                                        Ações
                                    </button>
                                    <ul class="dropdown-menu">
                                        <?php if($user['status'] != 'active'): ?>
                                        <li>
                                            <form method="POST" class="dropdown-item">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <input type="hidden" name="action" value="activate">
                                                <button type="submit" class="btn btn-link text-success p-0">
                                                    <i class="bi bi-check-circle"></i> Ativar
                                                </button>
                                            </form>
                                        </li>
                                        <?php endif; ?>
                                        
                                        <?php if($user['status'] != 'suspended'): ?>
                                        <li>
                                            <form method="POST" class="dropdown-item">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <input type="hidden" name="action" value="suspend">
                                                <button type="submit" class="btn btn-link text-warning p-0">
                                                    <i class="bi bi-pause-circle"></i> Suspender
                                                </button>
                                            </form>
                                        </li>
                                        <?php endif; ?>
                                        
                                        <?php if($user['active_investments'] == 0): ?>
                                        <li>
                                            <form method="POST" class="dropdown-item" 
                                                  onsubmit="return confirm('Tem certeza que deseja excluir este usuário?')">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <button type="submit" class="btn btn-link text-danger p-0">
                                                    <i class="bi bi-trash"></i> Excluir
                                                </button>
                                            </form>
                                        </li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// Função de busca
document.getElementById('searchUser').addEventListener('keyup', function() {
    let searchText = this.value.toLowerCase();
    let table = document.getElementById('usersTable');
    let rows = table.getElementsByTagName('tr');

    for (let i = 1; i < rows.length; i++) {
        let show = false;
        let cells = rows[i].getElementsByTagName('td');
        
        for (let j = 0; j < cells.length; j++) {
            if (cells[j].textContent.toLowerCase().indexOf(searchText) > -1) {
                show = true;
                break;
            }
        }
        
        rows[i].style.display = show ? '' : 'none';
    }
});

// Função para exportar usuários
function exportUsers() {
    let table = document.getElementById('usersTable');
    let csv = [];
    let rows = table.getElementsByTagName('tr');

    for (let i = 0; i < rows.length; i++) {
        let row = [], cols = rows[i].getElementsByTagName('td');
        if (cols.length === 0) cols = rows[i].getElementsByTagName('th');
        
        for (let j = 0; j < cols.length - 1; j++) { // -1 para ignorar a coluna de ações
            row.push(cols[j].textContent.trim());
        }
        
        csv.push(row.join(','));
    }

    let csvContent = "data:text/csv;charset=utf-8," + csv.join('\n');
    let encodedUri = encodeURI(csvContent);
    let link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "usuarios_" + new Date().toISOString().split('T')[0] + ".csv");
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>

<?php require_once '../includes/footer.php'; ?>