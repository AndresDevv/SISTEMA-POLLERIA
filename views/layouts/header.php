<?php
/**
 * Layout: cabecera (head) y apertura del wrapper de AdminLTE.
 * Variables esperadas: $tituloPagina
 */

// La define index.php antes del require. El valor por defecto solo evita el
// aviso de "variable no definida" si alguien abre el layout por su cuenta.
$tituloPagina = $tituloPagina ?? 'Sistema';
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

<?php /* Ver nota del script de abajo: el <body> arranca SIN la clase de menú
        cerrado a propósito, para que este script pueda ponerla. */ ?>

<!-- Menú lateral: cerrado o abierto antes de que se dibuje la página.

    Este script va aquí, pegado al <body> y no en el pie como app.js, por un
    motivo concreto: el navegador pinta la página a medida que la va leyendo.
    Si la clase "lg-menu-cerrado" se pusiera más abajo, en tablet el menú se
    vería abierto durante un instante en cada carga —y en cada cambio de
    pestaña— hasta que el script del pie lo cerrara.

    La regla que decide es la misma que usa app.js: en pantalla ancha se
    respeta lo que el usuario dejó la última vez; en tablet (991 px o menos)
    siempre arranca cerrado. -->
<script>
    (function () {
        var CERRADO = 'lg-menu-cerrado';
        var ANCHO_MINIMO = 991;

        try {
            var guardado = window.localStorage.getItem(CERRADO);

            if (!(guardado === '1' && window.innerWidth > ANCHO_MINIMO)) {
                document.body.className += ' ' + CERRADO;
            }
        } catch (e) {
            // Sin localStorage (modo privado) se empieza cerrado, que es lo
            // seguro: un menú tapado siempre se abre, uno abierto por error
            // esconde la pantalla.
            document.body.className += ' ' + CERRADO;
        }
    })();
</script>

<div class="wrapper">

    <!-- Navbar -->
    <?php require APP_ROOT . '/views/layouts/navbar.php'; ?>

    <!-- Sidebar -->
    <?php require APP_ROOT . '/views/layouts/sidebar.php'; ?>

    <!-- Contenido -->
    <div class="content-wrapper">

        <section class="content">
            <!-- El id permite refrescar solo esta zona cuando algo cambia, sin
                recargar la pagina entera: el menu, el usuario y la posicion
                dentro de una lista se quedan donde estan. -->
            <div class="container-fluid p-0" id="lg-contenido">

