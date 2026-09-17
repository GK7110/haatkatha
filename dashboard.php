<?php
require 'config.php';
require 'functions.php';

$user = require_login('artisan');

$artisanStmt = $pdo->prepare("SELECT * FROM artisans WHERE user_id = ?");
$artisanStmt->execute([$user['id']]);
$artisan = $artisanStmt->fetch();

$action = $_GET['action'] ?? 'home';

// ---- Update profile --------------------------------------------------
if ($action === 'profile' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo->prepare(
        "UPDATE artisans SET craft_type=?, location=?, experience_years=?, story=? WHERE user_id=?"
    )->execute([
        clean($_POST['craft_type']),
        clean($_POST['location']),
        (int)$_POST['experience_years'],
        clean($_POST['story']),
        $user['id'],
    ]);
    flash('success', 'Profile updated.');
    redirect('dashboard.php');
}

// ---- Add / edit product --------------------------------------------------
if ($action === 'save_product' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = (int)($_POST['product_id'] ?? 0);
    $name = clean($_POST['name']);
    $material = clean($_POST['material']);
    $price = (float)$_POST['price'];
    $categoryId = (int)$_POST['category_id'];
    $description = clean($_POST['description']);
    $keywords = clean($_POST['keywords']);

    // image upload (optional)
    $imageName = $_POST['existing_image'] ?? null;
    if (!empty($_FILES['image']['name'])) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $imageName = uniqid('prod_') . '.' . strtolower($ext);
        move_uploaded_file($_FILES['image']['tmp_name'], __DIR__ . '/assets/uploads/' . $imageName);
    }

    if ($productId) {
        // Make sure this product belongs to this artisan
        $own = $pdo->prepare("SELECT id FROM products WHERE id=? AND artisan_id=?");
        $own->execute([$productId, $artisan['id']]);
        if (!$own->fetch()) { flash('error', 'Product not found.'); redirect('dashboard.php'); }

        $pdo->prepare(
            "UPDATE products SET name=?, material=?, price=?, category_id=?, description=?, keywords=?, image=?, status='pending'
             WHERE id=?"
        )->execute([$name, $material, $price, $categoryId, $description, $keywords, $imageName, $productId]);
        flash('success', 'Product updated and sent for admin review.');
    } else {
        $pdo->prepare(
            "INSERT INTO products (artisan_id, category_id, name, material, price, description, keywords, image)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        )->execute([$artisan['id'], $categoryId, $name, $material, $price, $description, $keywords, $imageName]);
        flash('success', 'Product added and sent for admin review.');
    }
    redirect('dashboard.php');
}

// ---- Delete product --------------------------------------------------
if ($action === 'delete_product') {
    $productId = (int)($_GET['id'] ?? 0);
    $pdo->prepare("DELETE FROM products WHERE id=? AND artisan_id=?")->execute([$productId, $artisan['id']]);
    flash('success', 'Product deleted.');
    redirect('dashboard.php');
}

// ---- Data for the page --------------------------------------------------
$products = $pdo->prepare("SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE artisan_id=? ORDER BY p.created_at DESC");
$products->execute([$artisan['id']]);
$products = $products->fetchAll();

$editProduct = null;
if ($action === 'edit_product') {
    $editStmt = $pdo->prepare("SELECT * FROM products WHERE id=? AND artisan_id=?");
    $editStmt->execute([(int)($_GET['id'] ?? 0), $artisan['id']]);
    $editProduct = $editStmt->fetch();
}

$categories = get_categories($pdo);
$pageTitle = 'My dashboard';
include 'header.php';
?>

<h1>Welcome, <?= clean($user['name']) ?></h1>

<ul class="nav nav-tabs mb-4">
  <li class="nav-item"><a class="nav-link <?= $action==='home'?'active':'' ?>" href="dashboard.php">My products</a></li>
  <li class="nav-item"><a class="nav-link <?= in_array($action,['add_product','edit_product'])?'active':'' ?>" href="dashboard.php?action=add_product">Add product</a></li>
  <li class="nav-item"><a class="nav-link <?= $action==='edit_profile'?'active':'' ?>" href="dashboard.php?action=edit_profile">My profile</a></li>
</ul>

<?php if ($action === 'edit_profile'): ?>

  <form method="post" action="dashboard.php?action=profile" class="col-md-6">
    <label>Craft type
      <input type="text" name="craft_type" class="form-control" value="<?= clean($artisan['craft_type']) ?>" placeholder="e.g. Muga silk weaving">
    </label>
    <label>Location
      <input type="text" name="location" class="form-control" value="<?= clean($artisan['location']) ?>" placeholder="e.g. Sualkuchi, Assam">
    </label>
    <label>Years of experience
      <input type="number" name="experience_years" class="form-control" value="<?= (int)$artisan['experience_years'] ?>">
    </label>
    <label>Your story
      <textarea name="story" class="form-control" rows="5" placeholder="Tell customers about your craft and background"><?= clean($artisan['story']) ?></textarea>
    </label>
    <button class="btn btn-primary mt-2" type="submit">Save profile</button>
  </form>

<?php elseif (in_array($action, ['add_product', 'edit_product'])): ?>

  <form method="post" action="dashboard.php?action=save_product" enctype="multipart/form-data" class="col-md-7" id="productForm">
    <input type="hidden" name="product_id" value="<?= $editProduct['id'] ?? '' ?>">
    <input type="hidden" name="existing_image" value="<?= clean($editProduct['image'] ?? '') ?>">

    <label>Product name
      <input type="text" id="p_name" name="name" class="form-control" required value="<?= clean($editProduct['name'] ?? '') ?>">
    </label>
    <label>Material
      <input type="text" id="p_material" name="material" class="form-control" value="<?= clean($editProduct['material'] ?? '') ?>">
    </label>
    <label>Category
      <select name="category_id" id="p_category" class="form-select">
        <?php foreach ($categories as $cat): ?>
          <option value="<?= $cat['id'] ?>" <?= (($editProduct['category_id'] ?? 0) == $cat['id']) ? 'selected' : '' ?>><?= clean($cat['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Price (&#8377;)
      <input type="number" step="0.01" name="price" class="form-control" required value="<?= clean($editProduct['price'] ?? '') ?>">
    </label>
    <label>Product photo
      <input type="file" name="image" class="form-control" accept="image/*">
    </label>

    <label>Description
      <textarea id="p_description" name="description" class="form-control" rows="4"><?= clean($editProduct['description'] ?? '') ?></textarea>
    </label>
    <div class="ai-buttons mb-3">
      <button type="button" class="btn btn-sm btn-outline-secondary" id="btnGenerateDesc">Generate with AI</button>
      <button type="button" class="btn btn-sm btn-outline-secondary" id="btnTranslateDesc">Translate to Assamese</button>
      <span id="aiStatus" class="text-muted small ms-2"></span>
    </div>

    <label>Keywords (comma separated)
      <input type="text" id="p_keywords" name="keywords" class="form-control" value="<?= clean($editProduct['keywords'] ?? '') ?>">
    </label>
    <div class="ai-buttons mb-3">
      <button type="button" class="btn btn-sm btn-outline-secondary" id="btnSuggestKeywords">Suggest keywords with AI</button>
    </div>

    <button type="submit" class="btn btn-primary">Save product</button>
  </form>

<?php else: ?>

  <div class="row">
    <?php if (!$products): ?>
      <p class="text-muted">You haven't added any products yet. <a href="dashboard.php?action=add_product">Add your first one.</a></p>
    <?php endif; ?>
    <?php foreach ($products as $p): ?>
      <div class="col-sm-6 col-md-4 col-lg-3 mb-4">
        <div class="card product-card">
          <img src="<?= $p['image'] ? 'assets/uploads/' . clean($p['image']) : 'assets/img-placeholder.svg' ?>"
               class="card-img-top" alt="" onerror="this.src='https://placehold.co/300x220?text=No+Image'">
          <div class="card-body">
            <h6 class="card-title"><?= clean($p['name']) ?></h6>
            <p class="small text-muted mb-1"><?= clean($p['category_name'] ?? '') ?></p>
            <p class="fw-bold">&#8377; <?= number_format($p['price'], 2) ?></p>
            <span class="badge bg-<?= $p['status']==='approved'?'success':($p['status']==='rejected'?'danger':'secondary') ?>"><?= ucfirst($p['status']) ?></span>
            <div class="mt-2">
              <a href="dashboard.php?action=edit_product&id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
              <a href="dashboard.php?action=delete_product&id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this product?')">Delete</a>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

<?php endif; ?>

<?php include 'footer.php'; ?>
