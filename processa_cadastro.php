<?php
// Configurações de Erro
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// URL para redirecionamento
$redirect_url = 'index.php';

// Conexão com localhost
$servidor = "....";
$usuario = "....";
$senha = "....";
$banco = "....";

// Conexão
$conexao = new mysqli($servidor, $usuario, $senha, $banco);

if ($conexao->connect_error) {
    header("Location: " . $redirect_url . "?status=cadastro_erro&error_detail=Falha na conexão com banco.");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $senha_plana = $_POST['senha'];
    $telefone = isset($_POST['telefone']) ? $_POST['telefone'] : '';

    $senha_hashed = password_hash($senha_plana, PASSWORD_DEFAULT);

    $sql_insert = "INSERT INTO usuarios (nome, email, senha, telefone) VALUES (?, ?, ?, ?)";
    $stmt = $conexao->prepare($sql_insert);

    if ($stmt === false) {
        header("Location: " . $redirect_url . "?status=cadastro_erro&error_detail=Erro na preparação SQL.");
        exit();
    }

    $stmt->bind_param("ssss", $nome, $email, $senha_hashed, $telefone);

    if ($stmt->execute()) {
        header("Location: " . $redirect_url . "?status=cadastro_sucesso");
        exit();
    } else {
        $erro = $conexao->error;
        // Verifica duplicidade de email
        $msg_erro = (strpos($erro, 'Duplicate entry') !== false) ? "Este email já está cadastrado." : "Erro ao salvar no banco.";
        header("Location: " . $redirect_url . "?status=cadastro_erro&error_detail=" . urlencode($msg_erro));
        exit();
    }
    $stmt->close();
} else {
    header("Location: index.php");
    exit();
}
$conexao->close();
?>
