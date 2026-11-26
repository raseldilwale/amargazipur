<?php
$currentUser = Auth::user();
$siteName = get_setting('site_name', 'AmarGazipur');
$flashSuccess = flash('success');
$flashError = flash('error');
$locale = I18n::locale();
?>
<!doctype html>
<html lang="<?= e($locale) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle ?? $siteName) ?></title>
  <meta name="description" content="<?= e($metaDesc ?? get_setting('seo_meta_description', '')) ?>">
  <meta property="og:title" content="<?= e($pageTitle ?? $siteName) ?>">
  <meta property="og:description" content="<?= e($metaDesc ?? get_setting('seo_meta_description', '')) ?>">
  <link rel="canonical" href="<?= e(url($_SERVER['REQUEST_URI'] ?? '/')) ?>">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700&display=swap" rel="stylesheet">
  <link href="<?= e(asset('css/style.css')) ?>" rel="stylesheet">
</head>
<body>
<header class="ag-navbar sticky-top">
  <div class="container py-2">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
      <a href="<?= e(url('/')) ?>" class="text-decoration-none fw-bold fs-4 text-dark"><?= e($siteName) ?></a>
      <nav class="d-flex flex-wrap align-items-center gap-3 small fw-medium">
        <a href="<?= e(url('/')) ?>" class="text-decoration-none text-secondary"><?= e(__('home')) ?></a>
        <a href="<?= e(url('/news')) ?>" class="text-decoration-none text-secondary"><?= e(__('news')) ?></a>
        <a href="<?= e(url('/community')) ?>" class="text-decoration-none text-secondary"><?= e(__('community')) ?></a>
        <a href="<?= e(url('/marketplace')) ?>" class="text-decoration-none text-secondary"><?= e(__('marketplace')) ?></a>
        <a href="<?= e(url('/rentals')) ?>" class="text-decoration-none text-secondary"><?= e(__('rentals')) ?></a>
        <a href="<?= e(url('/jobs')) ?>" class="text-decoration-none text-secondary"><?= e(__('jobs')) ?></a>
        <a href="<?= e(url('/education')) ?>" class="text-decoration-none text-secondary"><?= e(__('education')) ?></a>
        <a href="<?= e(url('/blood-donors')) ?>" class="text-decoration-none text-secondary"><?= e(__('blood')) ?></a>
        <a href="<?= e(url('/businesses')) ?>" class="text-decoration-none text-secondary"><?= e(__('businesses')) ?></a>
      </nav>
      <div class="d-flex align-items-center gap-2">
        <a href="?lang=<?= $locale === 'bn' ? 'en' : 'bn' ?>" class="btn btn-sm btn-outline-secondary">
          <?= $locale === 'bn' ? 'EN' : 'বাংলা' ?>
        </a>
        <?php if ($currentUser): ?>
          <a href="<?= e(url('/dashboard')) ?>" class="btn btn-sm ag-btn-primary"><?= e(__('dashboard')) ?></a>
          <?php if (Auth::canAccessAdmin()): ?>
            <a href="<?= e(url('/admin')) ?>" class="btn btn-sm btn-dark"><?= e(__('admin_panel')) ?></a>
          <?php endif; ?>
        <?php else: ?>
          <a href="<?= e(url('/login')) ?>" class="btn btn-sm btn-outline-dark"><?= e(__('login')) ?></a>
          <a href="<?= e(url('/register')) ?>" class="btn btn-sm ag-btn-primary"><?= e(__('register')) ?></a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</header>

<main class="container py-4">
  <?php if ($flashSuccess): ?>
    <div class="alert alert-success alert-dismissible fade show"><?= e($flashSuccess) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
  <?php endif; ?>
  <?php if ($flashError): ?>
    <div class="alert alert-danger alert-dismissible fade show"><?= e($flashError) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
  <?php endif; ?>

  <?= $content ?>
</main>

<footer class="border-top bg-white mt-5 py-4">
  <div class="container d-flex flex-wrap justify-content-between align-items-center gap-3 small text-secondary">
    <div><?= e(get_setting('footer_text', '© AmarGazipur — Gazipur District Community Platform')) ?></div>
    <div class="d-flex gap-3">
      <a href="<?= e(url('/news')) ?>" class="text-secondary text-decoration-none"><?= e(__('news')) ?></a>
      <a href="<?= e(url('/rentals')) ?>" class="text-secondary text-decoration-none"><?= e(__('rentals')) ?></a>
      <a href="<?= e(url('/jobs')) ?>" class="text-secondary text-decoration-none"><?= e(__('jobs')) ?></a>
      <a href="<?= e(url('/blood-donors')) ?>" class="text-secondary text-decoration-none"><?= e(__('blood')) ?></a>
      <a href="<?= e(url('/sitemap.xml')) ?>" class="text-secondary text-decoration-none">Sitemap</a>
    </div>
  </div>
</footer>

<?php require APP_PATH . '/Views/partials/auth_modal.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
