<?php
// components/header.php
// Cabeçalho HTML comum — recebe $title opcional
if (!isset($title)) $title = "Grafo";
?>
<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title><?= htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE) ?></title>
    <link rel="stylesheet" href="/css/style.css" />
</head>

<body>
    <header class="site-header">
        <div class="container">
            <h1><?= htmlspecialchars($title) ?></h1>
        </div>
    </header>