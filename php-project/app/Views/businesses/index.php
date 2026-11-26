<?php $user = Auth::user(); ?>
<div class="ag-card p-4 mb-4">
  <h1 class="h4 fw-bold mb-3">গাজীপুর স্থানীয় ব্যবসা ও সেবা ডিরেক্টরি</h1>
  <form method="get" action="<?= e(url('/businesses')) ?>" class="row g-2">
    <div class="col-md-5">
      <select name="cat" class="form-select">
        <option value="">সকল ক্যাটাগরি</option>
        <option value="restaurants" <?= $cat === 'restaurants' ? 'selected' : '' ?>>রেস্তোরাঁ</option>
        <option value="shopping" <?= $cat === 'shopping' ? 'selected' : '' ?>>শপিং</option>
        <option value="hospitals" <?= $cat === 'hospitals' ? 'selected' : '' ?>>হাসপাতাল</option>
        <option value="clinics" <?= $cat === 'clinics' ? 'selected' : '' ?>>ক্লিনিক</option>
        <option value="technology" <?= $cat === 'technology' ? 'selected' : '' ?>>টেকনোলজি</option>
        <option value="transport" <?= $cat === 'transport' ? 'selected' : '' ?>>পরিবহন</option>
        <option value="services" <?= $cat === 'services' ? 'selected' : '' ?>>সেবা</option>
      </select>
    </div>
    <div class="col-md-5">
      <select name="upazila" class="form-select">
        <option value="">সকল উপজেলা</option>
        <?php foreach (gazipur_upazilas() as $k => $v): ?>
          <option value="<?= e($k) ?>" <?= $upazila === $k ? 'selected' : '' ?>><?= e($v['bn']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2"><button class="btn btn-dark w-100">খুঁজুন</button></div>
  </form>
</div>

<div class="row g-3">
  <?php foreach ($businesses as $b): ?>
    <div class="col-md-6">
      <div class="ag-card p-3 h-100">
        <div class="ag-meta mb-1"><?= e($b['category']) ?> · <?= e($b['upazila']) ?> <?= $b['is_featured'] ? '· ফিচার্ড' : '' ?></div>
        <h2 class="h6 fw-bold"><?= e($b['name']) ?></h2>
        <div class="small text-secondary mb-2"><?= e($b['address']) ?> · সময়: <?= e($b['hours'] ?: 'প্রতিদিন খোলা') ?></div>
        <p class="small mb-2"><?= e($b['description']) ?></p>
        <div class="small fw-semibold">ফোন: <?= e($b['phone']) ?></div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
