<?php $user = Auth::user(); ?>
<div class="ag-card p-4 mb-4">
  <h1 class="h4 fw-bold mb-3">গাজীপুর মার্কেটপ্লেস</h1>
  <form method="get" action="<?= e(url('/marketplace')) ?>" class="row g-2 mb-3">
    <div class="col-md-4"><input type="text" name="q" value="<?= e($q) ?>" class="form-control" placeholder="পণ্য খুঁজুন..."></div>
    <div class="col-md-3">
      <select name="cat" class="form-select">
        <option value="">সকল ক্যাটাগরি</option>
        <?php foreach ($categories as $c): ?>
          <option value="<?= e($c['slug']) ?>" <?= $catSlug === $c['slug'] ? 'selected' : '' ?>><?= e($c['name_bn']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <select name="upazila" class="form-select">
        <option value="">সকল উপজেলা</option>
        <?php foreach (gazipur_upazilas() as $k => $v): ?>
          <option value="<?= e($k) ?>" <?= $upazila === $k ? 'selected' : '' ?>><?= e($v['bn']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2"><button class="btn btn-dark w-100">খুঁজুন</button></div>
  </form>

  <?php if ($user): ?>
  <form method="post" action="<?= e(url('/marketplace/store')) ?>" enctype="multipart/form-data" class="border-top pt-3">
    <?= CSRF::field() ?>
    <h2 class="h6 fw-bold mb-2">নতুন পণ্য বিক্রি করুন</h2>
    <div class="row g-2 mb-2">
      <div class="col-md-4"><input type="text" name="title" class="form-control" placeholder="পণ্যের নাম" required></div>
      <div class="col-md-2"><input type="number" name="price" class="form-control" placeholder="মূল্য (৳)" required></div>
      <div class="col-md-3">
        <select name="category_id" class="form-select">
          <?php foreach ($categories as $c): ?>
            <option value="<?= (int)$c['id'] ?>"><?= e($c['name_bn']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <select name="item_condition" class="form-select">
          <option value="new">নতুন (New)</option>
          <option value="like_new">প্রায় নতুন</option>
          <option value="used">ব্যবহৃত</option>
        </select>
      </div>
    </div>
    <div class="row g-2 mb-2">
      <div class="col-md-4"><input type="text" name="location" class="form-control" placeholder="বিস্তারিত এলাকা (যেমন: জয়দেবপুর)" required></div>
      <div class="col-md-4">
        <select name="upazila" class="form-select">
          <?php foreach (gazipur_upazilas() as $k => $v): ?>
            <option value="<?= e($k) ?>"><?= e($v['bn']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4"><input type="text" name="contact_phone" class="form-control" placeholder="যোগাযোগের মোবাইল নম্বর" required></div>
    </div>
    <textarea name="description" rows="2" class="form-control mb-2" placeholder="পণ্যের বিবরণ লিখুন..." required></textarea>
    <div class="d-flex justify-content-between align-items-center">
      <input type="file" name="images[]" multiple accept="image/jpeg,image/png,image/webp" class="form-control form-control-sm w-auto">
      <button type="submit" class="btn ag-btn-primary">বিজ্ঞাপন দিন</button>
    </div>
  </form>
  <?php else: ?>
    <button type="button" class="btn ag-btn-primary btn-sm" data-require-auth="true">পণ্য বিক্রি করতে লগইন করুন</button>
  <?php endif; ?>
</div>

<div class="row g-3">
  <?php foreach ($listings as $item): $imgs = json_decode($item['images_json'] ?? '[]', true) ?: []; ?>
    <div class="col-md-6 col-lg-4">
      <div class="ag-card p-3 h-100 d-flex flex-column">
        <?php if (!empty($imgs[0])): ?>
          <img src="<?= e(url('/' . $imgs[0])) ?>" alt="<?= e($item['title']) ?>" class="img-fluid rounded mb-2" style="height:180px;object-fit:cover;">
        <?php endif; ?>
        <div class="ag-meta mb-1"><?= e($item['category_bn']) ?> · <?= e($item['upazila']) ?></div>
        <h2 class="h6 fw-bold mb-1"><?= e($item['title']) ?></h2>
        <div class="fw-bold text-success mb-2"><?= format_bdt($item['price']) ?></div>
        <p class="small text-secondary flex-grow-1"><?= e($item['description']) ?></p>
        <div class="border-top pt-2 small">
          <div>বিক্রেতা: <?= e($item['seller_name']) ?> (<?= e($item['location']) ?>)</div>
          <?php if ($user): ?>
            <div class="fw-semibold">ফোন: <?= e($item['contact_phone']) ?></div>
          <?php else: ?>
            <button type="button" class="btn btn-sm btn-outline-dark mt-1" data-require-auth="true">যোগাযোগ নম্বর দেখুন</button>
          <?php endif; ?>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
