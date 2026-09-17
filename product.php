<?php
require 'config.php';
require 'functions.php';

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    "SELECT p.*, c.name AS category_name,
            a.id AS artisan_id, a.craft_type, a.location, a.experience_years, a.story, a.photo,
            u.name AS artisan_name
     FROM products p
     JOIN artisans a ON a.id = p.artisan_id
     JOIN users u ON u.id = a.user_id
     LEFT JOIN categories c ON c.id = p.category_id
     WHERE p.id = ? AND p.status = 'approved'"
);
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    flash('error', 'That product could not be found.');
    redirect('index.php');
}

// Handle enquiry submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = clean($_POST['customer_name']);
    $contact = clean($_POST['customer_contact']);
    $message = clean($_POST['message']);

    if ($name && $contact) {
        $pdo->prepare(
            "INSERT INTO enquiries (product_id, customer_name, customer_contact, message) VALUES (?, ?, ?, ?)"
        )->execute([$id, $name, $contact, $message]);
        flash('success', 'Your enquiry has been sent to the artisan.');
        redirect('product.php?id=' . $id);
    }
    flash('error', 'Please fill in your name and contact details.');
}

// Other products from the same artisan
$more = $pdo->prepare(
    "SELECT id, name, price, image FROM products
     WHERE artisan_id = ? AND status = 'approved' AND id != ? LIMIT 4"
);
$more->execute([$product['artisan_id'], $id]);
$moreProducts = $more->fetchAll();

$pageTitle = $product['name'];
include 'header.php';
?>

<div class="row">
  <div class="col-md-6">
    <img src="<?= $product['image'] ? 'assets/uploads/' . clean($product['image']) : 'assets/img-placeholder.svg' ?>"
         class="img-fluid rounded product-image" alt="<?= clean($product['name']) ?>"
         onerror="this.src='https://placehold.co/600x450?text=No+Image'">
  </div>
  <div class="col-md-6">
    <h1><?= clean($product['name']) ?></h1>
    <p class="text-muted"><?= clean($product['category_name'] ?? '') ?></p>
    <p class="fs-3 fw-bold">&#8377; <?= number_format($product['price'], 2) ?></p>
    <p><strong>Material:</strong> <?= clean($product['material']) ?></p>
    <p><?= nl2br(clean($product['description'])) ?></p>

    <hr>
    <h5>About the artisan</h5>
    <p><strong><?= clean($product['artisan_name']) ?></strong> &mdash; <?= clean($product['craft_type']) ?>,
       <?= clean($product['location']) ?>
       <?php if ($product['experience_years']): ?> &middot; <?= (int)$product['experience_years'] ?> years of experience<?php endif; ?>
    </p>
    <?php if ($product['story']): ?><p class="artisan-story"><?= nl2br(clean($product['story'])) ?></p><?php endif; ?>

    <hr>
    <h5>Send an enquiry</h5>
    <form method="post" class="enquiry-form">
      <div class="mb-2"><input type="text" name="customer_name" class="form-control" placeholder="Your name" required></div>
      <div class="mb-2"><input type="text" name="customer_contact" class="form-control" placeholder="Phone or email" required></div>
      <div class="mb-2"><textarea name="message" class="form-control" placeholder="Message (optional)" rows="3"></textarea></div>
      <button type="submit" class="btn btn-primary">Send enquiry</button>
    </form>
  </div>
</div>

<?php if ($moreProducts): ?>
<h5 class="mt-5">More from this artisan</h5>
<div class="row">
  <?php foreach ($moreProducts as $mp): ?>
    <div class="col-sm-6 col-md-3 mb-3">
      <a href="product.php?id=<?= $mp['id'] ?>" class="card product-card text-decoration-none">
        <img src="<?= $mp['image'] ? 'assets/uploads/' . clean($mp['image']) : 'assets/img-placeholder.svg' ?>"
             class="card-img-top" alt="<?= clean($mp['name']) ?>"
             onerror="this.src='https://placehold.co/300x220?text=No+Image'">
        <div class="card-body">
          <h6 class="card-title"><?= clean($mp['name']) ?></h6>
          <p class="fw-bold small">&#8377; <?= number_format($mp['price'], 2) ?></p>
        </div>
      </a>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php include 'footer.php'; ?>
