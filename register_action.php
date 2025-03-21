<?php
session_start();
include 'includes/db.php';
include 'includes/functions.php';

// Verificando se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Limpando dados de entrada
    $name = clean_input($_POST['name']);
    $email = clean_input($_POST['email']);
    $password = clean_input($_POST['password']);
    $confirm_password = clean_input($_POST['confirm_password']);

    // Verificando se as senhas coincidem
    if ($password !== $confirm_password) {
        display_error("As senhas não coincidem.");
        header("Location: register.php");
        exit();
    }

    // Verificando se o email já está registrado
    $sql = "SELECT * FROM users WHERE email = '$email'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        display_error("Email já registrado.");
        header("Location: register.php");
        exit();
    }

    // Criptografando a senha
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Inserindo o novo usuário no banco de dados
    $sql = "INSERT INTO users (name, email, password) VALUES ('$name', '$email', '$hashed_password')";

    if ($conn->query($sql) === TRUE) {
        display_success("Registro bem-sucedido. Faça login para continuar.");
        header("Location: login.php");
        exit();
    } else {
        display_error("Erro ao registrar usuário: " . $conn->error);
        header("Location: register.php");
        exit();
    }
} else {
    header("Location: register.php");
    exit();
}
?>