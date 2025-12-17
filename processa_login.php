<?php
// Inicia a sessão segura
session_start();

// Configurações de Erro
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Conexão com Banco
$servidor = "....";
$usuario = "....";
$senha = "....";
$banco = "....";

$conexao = new mysqli($servidor, $usuario, $senha, $banco);

if ($conexao->connect_error) {
    die("Falha na conexão: " . $conexao->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email_login'];
    $senha_input = $_POST['senha_login'];

    // 1. Busca o usuário pelo Email (Prepared Statement para evitar SQL Injection)
    $sql = "SELECT id, nome, senha FROM usuarios WHERE email = ?";
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($usuario = $resultado->fetch_assoc()) {
        // 2. Verifica a Senha (O banco tem o HASH, o input é texto plano)
        // A função password_verify faz a mágica de comparar seguro
        if (password_verify($senha_input, $usuario['senha'])) {

            // LOGIN SUCESSO!
            // Proteção contra Session Fixation (CIS Control)
            session_regenerate_id(true);

            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];

            header("Location: index.php?status=login_sucesso");
            exit();

        } else {
            // Senha errada
            header("Location: index.php?status=login_erro&error_detail=Senha incorreta");
            exit();
        }
    } else {
        // Usuário não encontrado
        header("Location: index.php?status=login_erro&error_detail=Email não encontrado");
        exit();
    }

    $stmt->close();
} else {
    header("Location: index.php");
    exit();
}

$conexao->close();
?>
