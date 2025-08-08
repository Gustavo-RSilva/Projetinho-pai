<?php
session_start();

if (!isset($_SESSION['nome_completo'])) {
    header("Location: login-google.php");
    exit;
}

echo "Bem-vindo, " . htmlspecialchars($_SESSION['nome_completo'] . header("Location: teste.php"));
// Aqui você pode adicionar o conteúdo da área protegida
