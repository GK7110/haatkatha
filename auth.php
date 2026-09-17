<?php
require 'config.php';
require 'functions.php';

$action = $_GET['action'] ?? 'login';

// ---- LOGOUT --------------------------------------------------
if ($action === 'logout') {
    session_destroy();
    redirect('index.php');
}

// ---- REGISTER (POST) --------------------------------------------------
if ($action === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = clean($_POST['name']);
    $email = clean($_POST['email']);
    $password = $_POST['password'] ?? '';
    $role = in_array($_POST['role'] ?? '', ['artisan', 'customer']) ? $_POST['role'] : 'customer';

    if (!$name || !$email || strlen($password) < 6) {
        flash('error', 'Please fill all fields. Password must be at least 6 characters.');
        redirect('auth.php?action=register');
    }

    $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$email]);
    if ($check->fetch()) {
        flash('error', 'An account with that email already exists.');
        redirect('auth.php?action=register');
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)")
        ->execute([$name, $email, $hash, $role]);
    $userId = $pdo->lastInsertId();

    // Artisans get an empty profile row right away so dashboard.php can update it
    if ($role === 'artisan') {
        $pdo->prepare("INSERT INTO artisans (user_id) VALUES (?)")->execute([$userId]);
    }

    flash('success', 'Account created. Please log in.');
    redirect('auth.php?action=login');
}

// ---- LOGIN (POST) --------------------------------------------------
if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = clean($_POST['email']);
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        flash('error', 'Incorrect email or password.');
        redirect('auth.php?action=login');
    }
    if ($user['status'] === 'blocked') {
        flash('error', 'This account has been blocked by the admin.');
        redirect('auth.php?action=login');
    }

    $_SESSION['user'] = [
        'id' => $user['id'],
        'name' => $user['name'],
        'role' => $user['role'],
    ];

    if ($user['role'] === 'admin') redirect('admin.php');
    if ($user['role'] === 'artisan') redirect('dashboard.php');
    redirect('index.php');
}

$pageTitle = $action === 'register' ? 'Create account' : 'Log in';
include 'header.php';
?>

<div class="auth-wrap">
  <?php if ($action === 'login'): ?>
    <h1>Log in</h1>
    <form method="post" action="auth.php?action=login">
      <label>Email
        <input type="email" name="email" required>
      </label>
      <label>Password
        <input type="password" name="password" required>
      </label>
      <button type="submit">Log in</button>
    </form>
    <p>New here? <a href="auth.php?action=register">Create an account</a></p>
    <p class="hint">Admin demo login: admin@haatkatha.local / Admin@123</p>

  <?php else: ?>
    <h1>Create an account</h1>
    <form method="post" action="auth.php?action=register">
      <label>Full name
        <input type="text" name="name" required>
      </label>
      <label>Email
        <input type="email" name="email" required>
      </label>
      <label>Password
        <input type="password" name="password" required minlength="6">
      </label>
      <label>I am a
        <select name="role">
          <option value="customer">Customer</option>
          <option value="artisan">Artisan</option>
        </select>
      </label>
      <button type="submit">Create account</button>
    </form>
    <p>Already have an account? <a href="auth.php?action=login">Log in</a></p>
  <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
