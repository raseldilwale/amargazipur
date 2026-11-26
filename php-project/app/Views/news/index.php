<?php $user = Auth::user(); ?>
<div class="row g-4">
  <div class="col-lg-8">
    <div class="ag-card p-4 mb-4">
      <h1 class="h4 fw-bold mb-3">গাজীপুর সংবাদ পোর্টাল</h1>
      <form method="get" action="<?= e(url('/news')) ?>" class="row g-2 mb-3">
        <div class="col-md-5"><input type="text" name="q" value="<?= e($q) ?>" class="form-control" placeholder="সংবাদ খুঁজুন..."></div>
        <div class="col-md-5">
          <select name="cat" class="form-select">
            <option value="">সকল সংবাদ বিভাগ</option>
            <?php foreach ($categories as $c): ?>
              <option value="<?= e($c['slug']) ?>" <?= $catSlug === $c['slug'] ? 'selected' : '' ?>><?= e($c['name_bn']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2"><button class="btn btn-dark w-100">খুঁজুন</button></div>
      </form>

      <?php if ($user && (Auth::hasApprovedCategory('journalist') || Auth::hasRole(['admin', 'editor']))): ?>
      <form method="post" action="<?= e(url('/news/store')) ?>" enctype="multipart/form-data" class="border-top pt-3">
        <?= CSRF::field() ?>
        <h2 class="h6 fw-bold mb-2">সাংবাদিক সংবাদ জমা (অ্যাডমিন অনুমোদনের পর প্রকাশিত হবে)</h2>
        <div class="row g-2 mb-2">
          <div class="col-md-6"><input type="text" name="title" class="form-control" placeholder="সংবাদের শিরোনাম" required></div>
          <div class="col-md-3">
            <select name="category_id" class="form-select">
              <?php foreach ($categories as $c): ?>
                <option value="<?= (int)$c['id'] ?>"><?= e($c['name_bn']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <select name="upazila" class="form-select">
              <?php foreach (gazipur_upazilas() as $k => $v): ?>
                <option value="<?= e($k) ?>"><?= e($v['bn']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <input type="text" name="summary" class="form-control mb-2" placeholder="সংক্ষিপ্ত সারাংশ" required>
        <textarea name="content" rows="4" class="form-control mb-2" placeholder="সম্পূর্ণ প্রতিবেদন লিখুন..." required></textarea>
        <div class="d-flex justify-content-between align-items-center">
          <input type="file" name="featured_image" accept="image/jpeg,image/png,image/webp" class="form-control form-control-sm w-auto">
          <button type="submit" class="btn ag-btn-primary">অনুমোদনের জন্য জমা দিন</button>
        </div>
      </form>
      <?php endif; ?>
    </div>

    <div class="row g-3">
      <?php foreach ($articles as $art): ?>
        <div class="col-md-6">
          <article class="ag-card h-100 p-3 d-flex flex-column">
            <?php if ($art['featured_image']): ?>
              <img src="<?= e(url('/' . $art['featured_image'])) ?>" alt="<?= e($art['title']) ?>" class="img-fluid rounded mb-2" style="height:180px;object-fit:cover;" loading="lazy">
            <?php endif; ?>
            <div class="ag-meta mb-1"><?= e($art['category_bn']) ?> · <?= e($art['upazila']) ?></div>
            <h2 class="h6 fw-bold mb-2"><a href="<?= e(url('/news/' . $art['slug'])) ?>" class="text-decoration-none text-dark"><?= e($art['title']) ?></a></h2>
            <p class="small text-secondary flex-grow-1"><?= e($art['summary']) ?></p>
            <div class="ag-meta"><?= e($art['author_name']) ?> · পঠিত: <?= (int)$art['views_count'] ?></div>
          </article>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="ag-card p-3">
      <h3 class="h6 fw-bold mb-3">জনপ্রিয় সংবাদ</h3>
      <?php foreach ($popular as $pop): ?>
        <div class="border-bottom pb-2 mb-2">
          <a href="<?= e(url('/news/' . $pop['slug'])) ?>" class="text-decoration-none text-dark fw-medium small d-block"><?= e($pop['title']) ?></a>
          <span class="ag-meta">পঠিত: <?= (int)$pop['views_count'] ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
