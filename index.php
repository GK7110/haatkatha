<?php
require 'config.php';
require 'functions.php';

$search = clean($_GET['q'] ?? '');
$categoryId = (int)($_GET['category'] ?? 0);

$sql = "SELECT p.*, a.craft_type, a.location, u.name AS artisan_name, c.name AS category_name
        FROM products p
        JOIN artisans a ON a.id = p.artisan_id
        JOIN users u ON u.id = a.user_id
        LEFT JOIN categories c ON c.id = p.category_id
        WHERE p.status = 'approved'";
$params = [];

if ($search !== '') {
    $sql .= " AND (p.name LIKE ? OR p.description LIKE ? OR p.keywords LIKE ?)";
    $like = "%$search%";
    array_push($params, $like, $like, $like);
}
if ($categoryId > 0) {
    $sql .= " AND p.category_id = ?";
    $params[] = $categoryId;
}
$sql .= " ORDER BY p.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$categories = get_categories($pdo);
$pageTitle = 'Browse handmade products';
include 'header.php';
?>

<div class="hero">
  <h1>Discover Assamese craftsmanship</h1>
  <p>Handloom, bamboo, pottery, and silk &mdash; made by artisans, sold directly by them.</p>
</div>

<form method="get" class="row g-2 filter-bar">
  <div class="col-md-7">
    <input type="text" name="q" class="form-control" placeholder="Search products..." value="<?= clean($search) ?>">
  </div>
  <div class="col-md-3">
    <select name="category" class="form-select">
      <option value="0">All categories</option>
      <?php foreach ($categories as $cat): ?>
        <option value="<?= $cat['id'] ?>" <?= $categoryId === (int)$cat['id'] ? 'selected' : '' ?>>
          <?= clean($cat['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-2 d-grid">
    <button class="btn btn-primary" type="submit">Filter</button>
  </div>
</form>

<div class="row product-grid">
  <?php if (!$products): ?>
    <p class="text-muted mt-4">No products found yet. Check back soon.</p>
  <?php endif; ?>
  <?php foreach ($products as $p): ?>
    <div class="col-sm-6 col-md-4 col-lg-3 mb-4">
      <a href="product.php?id=<?= $p['id'] ?>" class="card product-card text-decoration-none">
        <img src="<?= $p['image'] ? 'assets/uploads/' . clean($p['image']) : 'assets/img-placeholder.svg' ?>"
             class="card-img-top" alt="<?= clean($p['name']) ?>"
             onerror="this.src='https://placehold.co/400x300?text=No+Image'">
        <div class="card-body">
          <h5 class="card-title"><?= clean($p['name']) ?></h5>
          <p class="card-text text-muted small"><?= clean($p['category_name'] ?? 'Uncategorised') ?></p>
          <p class="card-text fw-bold">&#8377; <?= number_format($p['price'], 2) ?></p>
          <p class="card-text small">by <?= clean($p['artisan_name']) ?>, <?= clean($p['location']) ?></p>
        </div>
      </a>
    </div>
  <?php endforeach; ?>
</div>

<?php include 'footer.php'; ?>
