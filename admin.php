<?php
require 'config.php';
require 'functions.php';

$user = require_login('admin');
$tab = $_GET['tab'] ?? 'products';

// ---- Actions --------------------------------------------------
if (isset($_GET['approve_product'])) {
    $pdo->prepare("UPDATE products SET status='approved' WHERE id=?")->execute([(int)$_GET['approve_product']]);
    flash('success', 'Product approved.');
    redirect('admin.php?tab=products');
}
if (isset($_GET['reject_product'])) {
    $pdo->prepare("UPDATE products SET status='rejected' WHERE id=?")->execute([(int)$_GET['reject_product']]);
    flash('success', 'Product rejected.');
    redirect('admin.php?tab=products');
}
if (isset($_GET['delete_product'])) {
    $pdo->prepare("DELETE FROM products WHERE id=?")->execute([(int)$_GET['delete_product']]);
    flash('success', 'Product deleted.');
    redirect('admin.php?tab=products');
}
if (isset($_GET['approve_artisan'])) {
    $pdo->prepare("UPDATE artisans SET approved=1 WHERE id=?")->execute([(int)$_GET['approve_artisan']]);
    flash('success', 'Artisan approved.');
    redirect('admin.php?tab=artisans');
}
if (isset($_GET['toggle_block'])) {
    $uid = (int)$_GET['toggle_block'];
    $pdo->prepare("UPDATE users SET status = IF(status='active','blocked','active') WHERE id=? AND role != 'admin'")->execute([$uid]);
    flash('success', 'User status updated.');
    redirect('admin.php?tab=users');
}
if (isset($_POST['new_category'])) {
    $name = clean($_POST['new_category']);
    if ($name) {
        $pdo->prepare("INSERT IGNORE INTO categories (name) VALUES (?)")->execute([$name]);
        flash('success', 'Category added.');
    }
    redirect('admin.php?tab=categories');
}
if (isset($_GET['delete_category'])) {
    $pdo->prepare("DELETE FROM categories WHERE id=?")->execute([(int)$_GET['delete_category']]);
    flash('success', 'Category deleted.');
    redirect('admin.php?tab=categories');
}

// ---- Data --------------------------------------------------
$stats = [
    'users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'artisans' => $pdo->query("SELECT COUNT(*) FROM artisans")->fetchColumn(),
    'products' => $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn(),
    'pending' => $pdo->query("SELECT COUNT(*) FROM products WHERE status='pending'")->fetchColumn(),
    'enquiries' => $pdo->query("SELECT COUNT(*) FROM enquiries")->fetchColumn(),
];

$pageTitle = 'Admin panel';
include 'header.php';
?>

<h1>Admin panel</h1>
<div class="row admin-stats mb-4">
  <div class="col"><div class="stat-box"><strong><?= $stats['users'] ?></strong><span>Users</span></div></div>
  <div class="col"><div class="stat-box"><strong><?= $stats['artisans'] ?></strong><span>Artisans</span></div></div>
  <div class="col"><div class="stat-box"><strong><?= $stats['products'] ?></strong><span>Products</span></div></div>
  <div class="col"><div class="stat-box"><strong><?= $stats['pending'] ?></strong><span>Pending review</span></div></div>
  <div class="col"><div class="stat-box"><strong><?= $stats['enquiries'] ?></strong><span>Enquiries</span></div></div>
</div>

<ul class="nav nav-tabs mb-4">
  <li class="nav-item"><a class="nav-link <?= $tab==='products'?'active':'' ?>" href="admin.php?tab=products">Products</a></li>
  <li class="nav-item"><a class="nav-link <?= $tab==='artisans'?'active':'' ?>" href="admin.php?tab=artisans">Artisans</a></li>
  <li class="nav-item"><a class="nav-link <?= $tab==='users'?'active':'' ?>" href="admin.php?tab=users">Users</a></li>
  <li class="nav-item"><a class="nav-link <?= $tab==='categories'?'active':'' ?>" href="admin.php?tab=categories">Categories</a></li>
</ul>

<?php if ($tab === 'products'): ?>
  <?php
  $products = $pdo->query(
      "SELECT p.*, u.name AS artisan_name, c.name AS category_name
       FROM products p JOIN artisans a ON a.id=p.artisan_id JOIN users u ON u.id=a.user_id
       LEFT JOIN categories c ON c.id=p.category_id ORDER BY p.created_at DESC"
  )->fetchAll();
  ?>
  <table class="table table-striped align-middle">
    <thead><tr><th>Name</th><th>Artisan</th><th>Category</th><th>Price</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($products as $p): ?>
      <tr>
        <td><?= clean($p['name']) ?></td>
        <td><?= clean($p['artisan_name']) ?></td>
        <td><?= clean($p['category_name'] ?? '') ?></td>
        <td>&#8377; <?= number_format($p['price'],2) ?></td>
        <td><span class="badge bg-<?= $p['status']==='approved'?'success':($p['status']==='rejected'?'danger':'secondary') ?>"><?= ucfirst($p['status']) ?></span></td>
        <td>
          <?php if ($p['status'] !== 'approved'): ?><a href="admin.php?tab=products&approve_product=<?= $p['id'] ?>" class="btn btn-sm btn-outline-success">Approve</a><?php endif; ?>
          <?php if ($p['status'] !== 'rejected'): ?><a href="admin.php?tab=products&reject_product=<?= $p['id'] ?>" class="btn btn-sm btn-outline-warning">Reject</a><?php endif; ?>
          <a href="admin.php?tab=products&delete_product=<?= $p['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete?')">Delete</a>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>

<?php elseif ($tab === 'artisans'): ?>
  <?php $artisans = $pdo->query("SELECT a.*, u.name, u.email FROM artisans a JOIN users u ON u.id=a.user_id ORDER BY a.created_at DESC")->fetchAll(); ?>
  <table class="table table-striped align-middle">
    <thead><tr><th>Name</th><th>Email</th><th>Craft</th><th>Location</th><th>Approved</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($artisans as $a): ?>
      <tr>
        <td><?= clean($a['name']) ?></td>
        <td><?= clean($a['email']) ?></td>
        <td><?= clean($a['craft_type']) ?></td>
        <td><?= clean($a['location']) ?></td>
        <td><?= $a['approved'] ? 'Yes' : 'No' ?></td>
        <td><?php if (!$a['approved']): ?><a href="admin.php?tab=artisans&approve_artisan=<?= $a['id'] ?>" class="btn btn-sm btn-outline-success">Approve</a><?php endif; ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>

<?php elseif ($tab === 'users'): ?>
  <?php $users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll(); ?>
  <table class="table table-striped align-middle">
    <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($users as $u): ?>
      <tr>
        <td><?= clean($u['name']) ?></td>
        <td><?= clean($u['email']) ?></td>
        <td><?= clean($u['role']) ?></td>
        <td><?= clean($u['status']) ?></td>
        <td><?php if ($u['role'] !== 'admin'): ?>
          <a href="admin.php?tab=users&toggle_block=<?= $u['id'] ?>" class="btn btn-sm btn-outline-secondary">
            <?= $u['status']==='active' ? 'Block' : 'Unblock' ?>
          </a>
        <?php endif; ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>

<?php elseif ($tab === 'categories'): ?>
  <?php $categories = get_categories($pdo); ?>
  <form method="post" class="row g-2 mb-4 col-md-6">
    <div class="col-8"><input type="text" name="new_category" class="form-control" placeholder="New category name" required></div>
    <div class="col-4 d-grid"><button class="btn btn-primary" type="submit">Add</button></div>
  </form>
  <table class="table table-striped col-md-6">
    <thead><tr><th>Name</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($categories as $c): ?>
      <tr>
        <td><?= clean($c['name']) ?></td>
        <td><a href="admin.php?tab=categories&delete_category=<?= $c['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete category?')">Delete</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>

<?php include 'footer.php'; ?>
