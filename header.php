<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($pageTitle) ? clean($pageTitle) . ' — ' : '' ?><?= SITE_NAME ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="assets/css/style.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg site-nav">
  <div class="container">
    <a class="navbar-brand" href="index.php"><?= SITE_NAME ?></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="nav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="index.php">Browse products</a></li>
        <?php $u = current_user(); ?>
        <?php if (!$u): ?>
          <li class="nav-item"><a class="nav-link" href="auth.php?action=login">Log in</a></li>
          <li class="nav-item"><a class="nav-link" href="auth.php?action=register">Sign up</a></li>
        <?php elseif ($u['role'] === 'artisan'): ?>
          <li class="nav-item"><a class="nav-link" href="dashboard.php">My dashboard</a></li>
          <li class="nav-item"><a class="nav-link" href="auth.php?action=logout">Log out (<?= clean($u['name']) ?>)</a></li>
        <?php elseif ($u['role'] === 'admin'): ?>
          <li class="nav-item"><a class="nav-link" href="admin.php">Admin panel</a></li>
          <li class="nav-item"><a class="nav-link" href="auth.php?action=logout">Log out</a></li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="auth.php?action=logout">Log out (<?= clean($u['name']) ?>)</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<main class="container py-4">
  <?php if ($msg = flash('success')): ?>
    <div class="alert alert-success"><?= clean($msg) ?></div>
  <?php endif; ?>
  <?php if ($msg = flash('error')): ?>
    <div class="alert alert-danger"><?= clean($msg) ?></div>
  <?php endif; ?>
