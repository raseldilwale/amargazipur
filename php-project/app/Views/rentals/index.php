<?php $user = Auth::user(); ?>
<div class="ag-card p-4 mb-4">
  <h1 class="h4 fw-bold mb-3">গাজীপুর বাসা ভাড়া ও টু-লেট</h1>
  <form method="get" action="<?= e(url('/rentals')) ?>" class="row g-2 mb-3">
    <div class="col-md-4">
      <select name="cat" class="form-select">
        <option value="">সকল ধরন</option>
        <option value="family_apartment" <?= $cat === 'family_apartment' ? 'selected' : '' ?>>ফ্যামিলি বাসা</option>
        <option value="bachelor_room" <?= $cat === 'bachelor_room' ? 'selected' : '' ?>>ব্যাচেলর রুম</option>
        <option value="sublet" <?= $cat === 'sublet' ? 'selected' : '' ?>>সাবলেট</option>
        <option value="hostel" <?= $cat === 'hostel' ? 'selected' : '' ?>>হোস্টেল</option>
        <option value="mess" <?= $cat === 'mess' ? 'selected' : '' ?>>মেস</option>
        <option value="shop" <?= $cat === 'shop' ? 'selected' : '' ?>>দোকান</option>
        <option value="office" <?= $cat === 'office' ? 'selected' : '' ?>>অফিস</option>
      </select>
    </div>
    <div class="col-md-4">
      <select name="upazila" class="form-select">
        <option value="">সকল উপজেলা</option>
        <?php foreach (gazipur_upazilas() as $k => $v): ?>
          <option value="<?= e($k) ?>" <?= $upazila === $k ? 'selected' : '' ?>><?= e($v['bn']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2"><input type="number" name="max_rent" value="<?= $maxRent ?: '' ?>" class="form-control" placeholder="সর্বোচ্চ ভাড়া"></div>
    <div class="col-md-2"><button class="btn btn-dark w-100">ফিল্টার</button></div>
  </form>

  <?php if ($user && (Auth::hasApprovedCategory('landlord') || Auth::hasRole('admin'))): ?>
  <form method="post" action="<?= e(url('/rentals/store')) ?>" enctype="multipart/form-data" class="border-top pt-3">
    <?= CSRF::field() ?>
    <h2 class="h6 fw-bold mb-2">নতুন টু-লেট বিজ্ঞাপন দিন</h2>
    <div class="row g-2 mb-2">
      <div class="col-md-4"><input type="text" name="title" class="form-control" placeholder="শিরোনাম (যেমন: ৩ বেডের ফ্যামিলি ফ্ল্যাট)" required></div>
      <div class="col-md-2"><input type="number" name="monthly_rent" class="form-control" placeholder="মাসিক ভাড়া (৳)" required></div>
      <div class="col-md-2"><input type="number" name="advance_amount" class="form-control" placeholder="অগ্রিম (৳)"></div>
      <div class="col-md-2"><input type="number" name="bedrooms" class="form-control" placeholder="বেডরুম" value="2"></div>
      <div class="col-md-2"><input type="number" name="bathrooms" class="form-control" placeholder="বাথরুম" value="2"></div>
    </div>
    <div class="row g-2 mb-2">
      <div class="col-md-3">
        <select name="category" class="form-select">
          <option value="family_apartment">ফ্যামিলি বাসা</option>
          <option value="bachelor_room">ব্যাচেলর রুম</option>
          <option value="sublet">সাবলেট</option>
          <option value="hostel">হোস্টেল</option>
          <option value="mess">মেস</option>
          <option value="shop">দোকান</option>
          <option value="office">অফিস</option>
        </select>
      </div>
      <div class="col-md-3"><input type="text" name="location" class="form-control" placeholder="এলাকা (যেমন: চান্দনা চৌরাস্তা)" required></div>
      <div class="col-md-3">
        <select name="upazila" class="form-select">
          <?php foreach (gazipur_upazilas() as $k => $v): ?>
            <option value="<?= e($k) ?>"><?= e($v['bn']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3"><input type="text" name="contact_phone" class="form-control" placeholder="যোগাযোগ নম্বর" required></div>
    </div>
    <textarea name="description" rows="2" class="form-control mb-2" placeholder="সুবিধাসমূহ ও বিস্তারিত..."></textarea>
    <div class="form-check mb-2">
      <input class="form-check-input" type="checkbox" name="hide_contact_public" id="hideContact" checked>
      <label class="form-check-label small" for="hideContact">লগইন ছাড়া ভিজিটরদের কাছ থেকে ফোন নম্বর গোপন রাখুন</label>
    </div>
    <button type="submit" class="btn ag-btn-primary">টু-লেট প্রকাশ করুন</button>
  </form>
  <?php elseif ($user): ?>
    <div class="small text-secondary border-top pt-2">টু-লেট প্রকাশ করতে ড্যাশবোর্ড থেকে অনুমোদিত <strong>বাড়িওয়ালা (Landlord)</strong> ক্যাটাগরি প্রয়োজন।</div>
  <?php else: ?>
    <button type="button" class="btn ag-btn-primary btn-sm" data-require-auth="true">টু-লেট পোস্ট করতে লগইন করুন</button>
  <?php endif; ?>
</div>

<div class="row g-3">
  <?php foreach ($rentals as $r): ?>
    <div class="col-md-6">
      <div class="ag-card p-3 h-100">
        <div class="ag-meta mb-1"><?= e($r['category']) ?> · <?= e($r['upazila']) ?> · <?= e($r['location']) ?></div>
        <h2 class="h6 fw-bold"><?= e($r['title']) ?></h2>
        <div class="fw-bold text-success mb-2"><?= format_bdt($r['monthly_rent']) ?> / মাস (অগ্রিম: <?= format_bdt($r['advance_amount']) ?>)</div>
        <div class="small mb-2">বেড: <?= (int)$r['bedrooms'] ?> · বাথ: <?= (int)$r['bathrooms'] ?> · তলা: <?= e($r['floor_number'] ?: 'তথ্য নেই') ?></div>
        <p class="small text-secondary mb-2"><?= e($r['description']) ?></p>
        <?php if (!$r['hide_contact_public'] || $user): ?>
          <div class="small fw-semibold">যোগাযোগ: <?= e($r['contact_phone']) ?> (<?= e($r['landlord_name']) ?>)</div>
        <?php else: ?>
          <button type="button" class="btn btn-sm btn-outline-dark" data-require-auth="true">যোগাযোগ নম্বর দেখতে লগইন করুন</button>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>
