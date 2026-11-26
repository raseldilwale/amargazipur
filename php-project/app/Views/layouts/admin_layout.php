<?php
$adminUser = Auth::user();
$flashSuccess = flash('success');
$flashError = flash('error');
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/admin', PHP_URL_PATH);
$locale = I18n::locale();
$switchLangUrl = '?lang=' . ($locale === 'bn' ? 'en' : 'bn');
?>
<!doctype html>
<html lang="<?= e($locale) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle ?? 'Admin — AmarGazipur') ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700&display=swap" rel="stylesheet">
  <link href="<?= e(asset('css/style.css')) ?>" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container-fluid">
  <div class="row">
    <aside class="col-md-3 col-lg-2 p-3 ag-admin-sidebar">
      <div class="fw-bold fs-5 mb-1">AmarGazipur</div>
      <div class="small text-secondary mb-4"><?= e(tr('অ্যাডমিন ও মডারেশন কনসোল', 'Admin & Moderation Console')) ?></div>
      <nav>
        <a href="<?= e(url('/admin')) ?>" class="<?= str_ends_with($currentPath, '/admin') ? 'active' : '' ?>"><?= e(tr('ড্যাশবোর্ড ওভারভিউ', 'Dashboard Overview')) ?></a>
        <a href="<?= e(url('/admin/create')) ?>" class="<?= str_contains($currentPath, '/admin/create') ? 'active' : '' ?>">+ <?= e(tr('নতুন পোস্ট / প্রকাশ', 'Create Post +')) ?></a>
        <a href="<?= e(url('/admin/content')) ?>" class="<?= str_contains($currentPath, '/admin/content') ? 'active' : '' ?>"><?= e(tr('সকল কনটেন্ট ম্যানেজার', 'Content Manager')) ?></a>
        <a href="<?= e(url('/admin/users')) ?>" class="<?= str_contains($currentPath, '/admin/users') ? 'active' : '' ?>"><?= e(tr('ব্যবহারকারী ও ক্যাটাগরি', 'Users & Categories')) ?></a>
        <a href="<?= e(url('/admin/news')) ?>" class="<?= str_contains($currentPath, '/admin/news') ? 'active' : '' ?>"><?= e(tr('সংবাদ অনুমোদন কিউ', 'News Approval Queue')) ?></a>
        <a href="<?= e(url('/admin/moderation')) ?>" class="<?= str_contains($currentPath, '/admin/moderation') ? 'active' : '' ?>"><?= e(tr('মডারেশন ও নিষিদ্ধ শব্দ', 'Moderation & Rules')) ?></a>
        <a href="<?= e(url('/admin/ads')) ?>" class="<?= str_contains($currentPath, '/admin/ads') ? 'active' : '' ?>"><?= e(tr('বিজ্ঞাপন ব্যবস্থাপনা', 'Advertisements')) ?></a>
        <a href="<?= e(url('/admin/settings')) ?>" class="<?= str_contains($currentPath, '/admin/settings') ? 'active' : '' ?>"><?= e(tr('ওয়েবসাইট সেটিংস ও SEO', 'Settings & SEO')) ?></a>
        <hr class="border-secondary my-3">
        <a href="<?= e(url('/')) ?>"><?= e(tr('মূল ওয়েবসাইট দেখুন', 'View Public Site')) ?></a>
        <form method="post" action="<?= e(url('/logout')) ?>" class="mt-2">
          <?= CSRF::field() ?>
          <button class="btn btn-sm btn-outline-light w-100"><?= e(tr('লগআউট', 'Logout')) ?></button>
        </form>
      </nav>
    </aside>
    <main class="col-md-9 col-lg-10 p-4">
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4 pb-3 border-bottom">
        <h1 class="h4 mb-0 fw-bold"><?= e($pageTitle ?? 'Admin') ?></h1>
        <div class="d-flex flex-wrap align-items-center gap-2">
          <a href="<?= e(url('/admin/create')) ?>" class="btn btn-sm ag-btn-primary">
            + <?= e(tr('নতুন পোস্ট', 'Post +')) ?>
          </a>
          <a href="<?= e($switchLangUrl) ?>" class="btn btn-sm btn-outline-secondary">
            <?= $locale === 'bn' ? 'English' : 'বাংলা' ?>
          </a>
          <span class="small text-secondary ms-1"><?= e($adminUser['full_name'] ?? '') ?> (<?= e($adminUser['role_name'] ?? '') ?>)</span>
        </div>
      </div>
      <?php if ($flashSuccess): ?>
        <div class="alert alert-success alert-dismissible fade show"><?= e($flashSuccess) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
      <?php endif; ?>
      <?php if ($flashError): ?>
        <div class="alert alert-danger alert-dismissible fade show"><?= e($flashError) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
      <?php endif; ?>
      <?= $content ?>
    </main>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
