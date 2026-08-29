<?php
/**
 * Cabecera común para las páginas internas (con barra de navegación).
 * Incluye este archivo ANTES de cualquier salida; ya imprime <!DOCTYPE html>.
 */
require_once __DIR__ . '/_helpers.php';

$userType = current_user_type();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= isset($pageTitle) ? e($pageTitle) : 'Reservas de Eventos' ?></title>
  <link rel="stylesheet" href="<?= e(css_url()) ?>">
</head>
<body>
<header class="topbar">
  <div class="container">
    <a class="brand" href="<?= e(base_url('venue', 'catalog')) ?>">
      <span>&#127881;</span> Reservas Eventos
    </a>
    <nav class="nav">
      <?php if ($userType === 'client'): ?>
        <a href="<?= e(base_url('client', 'dashboard')) ?>">Mi panel</a>
        <a href="<?= e(base_url('venue', 'catalog')) ?>">Explorar locales</a>
        <a href="<?= e(base_url('booking', 'myBookings')) ?>">Mis reservas</a>
        <a href="<?= e(base_url('invoice', 'list')) ?>">Mis facturas</a>
        <a href="<?= e(base_url('client', 'profile')) ?>">Perfil</a>
      <?php elseif ($userType === 'owner'): ?>
        <a href="<?= e(base_url('owner', 'dashboard')) ?>">Mi panel</a>
        <a href="<?= e(base_url('venue', 'list')) ?>">Mis locales</a>
        <a href="<?= e(base_url('owner', 'paymentData')) ?>">Mis cobros</a>
        <a href="<?= e(base_url('notification', 'list')) ?>">Notificaciones</a>
        <a href="<?= e(base_url('owner', 'profile')) ?>">Perfil</a>
      <?php elseif ($userType === 'admin'): ?>
        <a href="<?= e(base_url('admin', 'dashboard')) ?>">Panel</a>
        <a href="<?= e(base_url('admin', 'users')) ?>">Usuarios</a>
        <a href="<?= e(base_url('service', 'pending')) ?>">Servicios por aprobar</a>
        <a href="<?= e(base_url('admin', 'bookings')) ?>">Reservas</a>
        <a href="<?= e(base_url('paymentMethod', 'list')) ?>">Métodos de pago</a>
      <?php else: ?>
        <a href="<?= e(base_url('venue', 'catalog')) ?>">Explorar locales</a>
      <?php endif; ?>
      <?php if ($userType !== null): ?>
        <a class="btn btn-sm btn-ghost" href="<?= e(base_url('auth', 'logout')) ?>">Cerrar sesión</a>
      <?php else: ?>
        <a class="btn btn-sm btn-primary" href="<?= e(base_url('auth', 'showLogin')) ?>">Iniciar sesión</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<main class="container">
