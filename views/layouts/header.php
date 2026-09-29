<?php
/**
 * Layout: cabecera (head) y apertura del wrapper de AdminLTE.
 * Variables esperadas: $tituloPagina
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta http-equiv="x-ua-compatible" content="ie=edge">

    <title><?= htmlspecialchars($tituloPagina) ?> | <?= htmlspecialchars(APP_NOMBRE) ?></title>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="<?= BOWER_URL ?>font-awesome/css/font-awesome.min.css">

    <!-- Bootstrap 4 -->
    <link rel="stylesheet" href="<?= BOWER_URL ?>bootstrap4/dist/css/bootstrap.min.css">

    <!-- AdminLTE -->
    <link rel="stylesheet" href="<?= ADMINLTE_URL ?>css/adminlte.min.css">

    <!-- CSS propio (sistema de diseño) -->
    <link rel="stylesheet" href="<?= ASSETS_URL ?>css/app.css?v=<?= APP_VERSION ?>">

</head>

<body class="hold-transition sidebar-mini layout-fixed">

<div class="wrapper">

    <!-- Navbar -->
    <?php require APP_ROOT . '/views/layouts/navbar.php'; ?>

    <!-- Sidebar -->
    <?php require APP_ROOT . '/views/layouts/sidebar.php'; ?>

    <!-- Contenido -->
    <div class="content-wrapper">

        <section class="content">
            <div class="container-fluid p-0">
