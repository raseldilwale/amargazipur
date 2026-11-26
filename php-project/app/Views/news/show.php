<div class="row g-4">
  <div class="col-lg-8">
    <article class="ag-card p-4">
      <div class="ag-meta mb-2"><?= e($article['category_bn']) ?> · <?= e($article['upazila']) ?> · <?= e(date('d M Y', strtotime($article['created_at']))) ?></div>
      <h1 class="h3 fw-bold mb-3"><?= e($article['title']) ?></h1>
      <?php if ($article['featured_image']): ?>
        <img src="<?= e(url('/' . $article['featured_image'])) ?>" alt="<?= e($article['title']) ?>" class="img-fluid rounded mb-3 w-100" style="max-height:420px;object-fit:cover;">
      <?php endif; ?>
      <p class="lead fs-6 text-secondary mb-3"><?= e($article['summary']) ?></p>
      <div class="mb-4"><?= nl2br(e($article['content'])) ?></div>
      <div class="border-top pt-3 bg-light p-3 rounded">
        <div class="fw-bold small">প্রতিবেদক: <?= e($article['author_name']) ?></div>
        <div class="small text-secondary"><?= e($article['author_bio'] ?: 'গাজীপুর জেলা প্রতিনিধি, AmarGazipur') ?></div>
      </div>
    </article>
  </div>
  <div class="col-lg-4">
    <div class="ag-card p-3">
      <h2 class="h6 fw-bold mb-3">সম্পর্কিত সংবাদ</h2>
      <?php foreach ($related as $rel): ?>
        <div class="border-bottom pb-2 mb-2">
          <a href="<?= e(url('/news/' . $rel['slug'])) ?>" class="text-decoration-none text-dark small fw-semibold"><?= e($rel['title']) ?></a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
