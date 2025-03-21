<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Verificação de autenticação
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'client') {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();

// Buscar dados do usuário
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Processar atualização do perfil
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action == 'update_profile') {
        $full_name = filter_input(INPUT_POST, 'full_name', FILTER_SANITIZE_STRING);
        $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);

        // Upload de foto
        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png'];
            $filename = $_FILES['profile_photo']['name'];
            $filetype = pathinfo($filename, PATHINFO_EXTENSION);

            if (in_array(strtolower($filetype), $allowed)) {
                $newname = "user_{$_SESSION['user_id']}.{$filetype}";
                $upload_path = "../uploads/profiles/{$newname}";

                if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $upload_path)) {
                    $photo_path = "profiles/{$newname}";
                    $stmt = $conn->prepare("UPDATE users SET profile_photo = ? WHERE id = ?");
                    $stmt->bind_param("si", $photo_path, $_SESSION['user_id']);
                    $stmt->execute();
                }
            }
        }

        // Atualizar dados
        $stmt = $conn->prepare("UPDATE users SET full_name = ?, phone = ? WHERE id = ?");
        $stmt->bind_param("ssi", $full_name, $phone, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            $success = "Perfil atualizado com sucesso!";
            // Atualizar dados na sessão
            $user['full_name'] = $full_name;
            $user['phone'] = $phone;
        }
    }
    elseif ($action == 'change_password') {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if (password_verify($current_password, $user['password'])) {
            if ($new_password == $confirm_password) {
                $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->bind_param("si", $password_hash, $_SESSION['user_id']);
                
                if ($stmt->execute()) {
                    $success = "Senha alterada com sucesso!";
                }
            } else {
                $error = "As novas senhas não conferem";
            }
        } else {
            $error = "Senha atual incorreta";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .profile-photo {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 20px;
        }
        .upload-photo {
            cursor: pointer;
            position: relative;
            display: inline-block;
        }
        .upload-photo:hover::after {
            content: "Alterar foto";
            position: absolute;
            bottom: 20px;
            left: 0;
            right: 0;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 5px;
            font-size: 12px;
            border-bottom-left-radius: 75px;
            border-bottom-right-radius: 75px;
        }
    </style>
</head>
<body>
    <?php require_once '../includes/client_header.php'; ?>

    <div class="container py-4">
        <?php if(isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if(isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="row">
            <!-- Dados do Perfil -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Dados do Perfil</h4>
                    </div>
                    <div class="card-body text-center">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="update_profile">
                            
                            <label class="upload-photo">
                                <img src="<?php 
                                    echo $user['profile_photo'] 
                                        ? "../uploads/{$user['profile_photo']}" 
                                        : "https://via.placeholder.com/150"; 
                                ?>" class="profile-photo" alt="Foto de Perfil">
                                <input type="file" name="profile_photo" class="d-none" 
                                       accept="image/jpeg,image/png">
                            </label>

                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" value="<?php echo $user['email']; ?>" disabled>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Nome Completo</label>
                                <input type="text" class="form-control" name="full_name" 
                                       value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Telefone</label>
                                <input type="tel" class="form-control" name="phone" 
                                       value="<?php echo htmlspecialchars($user['phone']); ?>">
                            </div>

                            <button type="submit" class="btn btn-primary">Atualizar Perfil</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Alterar Senha -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Alterar Senha</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="change_password">

                            <div class="mb-3">
                                <label class="form-label">Senha Atual</label>
                                <input type="password" class="form-control" name="current_password" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Nova Senha</label>
                                <input type="password" class="form-control" name="new_password" 
                                       minlength="6" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Confirmar Nova Senha</label>
                                <input type="password" class="form-control" name="confirm_password" 
                                       minlength="6" required>
                            </div>

                            <button type="submit" class="btn btn-primary">Alterar Senha</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php require_once '../includes/footer.php'; ?>

    <script>
    // Preview da foto antes do upload
    document.querySelector('input[name="profile_photo"]').addEventListener('change', function(e) {
        if (e.target.files && e.target.files[0]) {
            const reader = new FileReader();
            reader.onload = function(event) {
                document.querySelector('.profile-photo').src = event.target.result;
            }
            reader.readAsDataURL(e.target.files[0]);
        }
    });
    </script>
</body>
</html>