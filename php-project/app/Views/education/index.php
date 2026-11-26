<?php $user = Auth::user(); ?>
<div class="ag-card p-4 mb-4">
  <h1 class="h4 fw-bold mb-3">গাজীপুর শিক্ষা প্রতিষ্ঠান ডিরেক্টরি</h1>
  <form method="get" action="<?= e(url('/education')) ?>" class="row g-2 mb-3">
    <div class="col-md-5">
      <select name="type" class="form-select">
        <option value="">সকল প্রতিষ্ঠান</option>
        <option value="university" <?= $type === 'university' ? 'selected' : '' ?>>বিশ্ববিদ্যালয়</option>
        <option value="college" <?= $type === 'college' ? 'selected' : '' ?>>কলেজ</option>
        <option value="school" <?= $type === 'school' ? 'selected' : '' ?>>স্কুল</option>
        <option value="madrasa" <?= $type === 'madrasa' ? 'selected' : '' ?>>মাদ্রাসা</option>
        <option value="technical" <?= $type === 'technical' ? 'selected' : '' ?>>টেকনিক্যাল ইনস্টিটিউট</option>
        <option value="coaching" <?= $type === 'coaching' ? 'selected' : '' ?>>কোচিং ও প্রশিক্ষণ</option>
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

  <?php if ($user && (Auth::hasApprovedCategory('educational_institution') || Auth::hasRole('admin'))): ?>
  <form method="post" action="<?= e(url('/education/store')) ?>" enctype="multipart/form-data" class="border-top pt-3">
    <?= CSRF::field() ?>
    <h2 class="h6 fw-bold mb-2">শিক্ষা প্রতিষ্ঠানের তথ্য যুক্ত করুন</h2>
    <div class="row g-2 mb-2">
      <div class="col-md-5"><input type="text" name="name" class="form-control" placeholder="প্রতিষ্ঠানের নাম" required></div>
      <div class="col-md-3">
        <select name="type" class="form-select">
          <option value="school">স্কুল</option>
          <option value="college">কলেজ</option>
          <option value="university">বিশ্ববিদ্যালয়</option>
          <option value="madrasa">মাদ্রাসা</option>
          <option value="technical">টেকনিক্যাল</option>
          <option value="coaching">কোচিং</option>
        </select>
      </div>
      <div class="col-md-4"><input type="text" name="address" class="form-control" placeholder="ঠিকানা" required></div>
    </div>
    <textarea name="description" rows="2" class="form-control mb-2" placeholder="বিবরণ ও কোর্সসমূহ..." required></textarea>
    <button type="submit" class="btn ag-btn-primary">প্রতিষ্ঠান যুক্ত করুন</button>
  </form>
  <?php endif; ?>
</div>

<div class="row g-3">
  <?php foreach ($institutions as $inst): ?>
    <div class="col-md-6">
      <div class="ag-card p-3 h-100">
        <div class="ag-meta mb-1"><?= e($inst['type']) ?> · <?= e($inst['upazila']) ?></div>
        <h2 class="h6 fw-bold"><?= e($inst['name']) ?></h2>
        <div class="small text-secondary mb-2"><?= e($inst['address']) ?></div>
        <p class="small mb-2"><?= e($inst['description']) ?></p>
        <?php if ($inst['courses']): ?>
          <div class="small"><strong>বিভাগ/কোর্স:</strong> <?= e($inst['courses']) ?></div>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>
