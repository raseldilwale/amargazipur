<div class="row g-4">
  <div class="col-lg-4">
    <div class="ag-card p-4 mb-3">
      <h1 class="h5 fw-bold mb-1"><?= e($user['full_name']) ?></h1>
      <div class="small text-secondary mb-3"><?= e($user['email']) ?> · <?= e($user['upazila']) ?></div>
      <h2 class="h6 fw-bold mb-2">অ্যাকাউন্ট ক্যাটাগরি অনুমোদনের অবস্থা</h2>
      <?php $catLabels = account_categories(); foreach ($user['categories'] as $c): ?>
        <div class="d-flex justify-content-between align-items-center border-bottom py-2 small">
          <span><?= e($catLabels[$c['category_slug']]['bn'] ?? $c['category_slug']) ?></span>
          <span class="fw-semibold <?= $c['status'] === 'approved' ? 'text-success' : ($c['status'] === 'rejected' ? 'text-danger' : 'text-warning') ?>">
            <?= $c['status'] === 'approved' ? 'অনুমোদিত' : ($c['status'] === 'rejected' ? 'প্রত্যাখ্যাত' : 'অপেক্ষমাণ') ?>
          </span>
        </div>
      <?php endforeach; ?>
      <form method="post" action="<?= e(url('/logout')) ?>" class="mt-3">
        <?= CSRF::field() ?>
        <button class="btn btn-sm btn-outline-danger w-100">লগআউট</button>
      </form>
    </div>

    <div class="ag-card p-4">
      <h2 class="h6 fw-bold mb-3">প্রোফাইল সম্পাদনা</h2>
      <form method="post" action="<?= e(url('/dashboard/profile')) ?>">
        <?= CSRF::field() ?>
        <div class="mb-2"><label class="form-label small">নাম</label><input type="text" name="full_name" value="<?= e($user['full_name']) ?>" class="form-control form-control-sm" required></div>
        <div class="mb-2"><label class="form-label small">ফোন</label><input type="text" name="phone" value="<?= e($user['phone']) ?>" class="form-control form-control-sm"></div>
        <div class="mb-2"><label class="form-label small">উপজেলা</label>
          <select name="upazila" class="form-select form-select-sm">
            <?php foreach (gazipur_upazilas() as $k => $v): ?>
              <option value="<?= e($k) ?>" <?= $user['upazila'] === $k ? 'selected' : '' ?>><?= e($v['bn']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3"><label class="form-label small">পরিচিতি</label><textarea name="bio" rows="2" class="form-control form-control-sm"><?= e($user['bio']) ?></textarea></div>
        <button class="btn ag-btn-primary btn-sm w-100">তথ্য সংরক্ষণ করুন</button>
      </form>
    </div>

    <div class="ag-card p-4 mt-3">
      <h2 class="h6 fw-bold mb-3">পাসওয়ার্ড পরিবর্তন</h2>
      <form method="post" action="<?= e(url('/dashboard/password')) ?>">
        <?= CSRF::field() ?>
        <div class="mb-2"><label class="form-label small">বর্তমান পাসওয়ার্ড</label><input type="password" name="current_password" class="form-control form-control-sm" required></div>
        <div class="mb-2"><label class="form-label small">নতুন পাসওয়ার্ড (কমপক্ষে ৬ অক্ষর)</label><input type="password" name="new_password" minlength="6" class="form-control form-control-sm" required></div>
        <div class="mb-3"><label class="form-label small">নতুন পাসওয়ার্ড নিশ্চিত করুন</label><input type="password" name="confirm_password" minlength="6" class="form-control form-control-sm" required></div>
        <button class="btn btn-outline-dark btn-sm w-100">পাসওয়ার্ড পরিবর্তন করুন</button>
      </form>
    </div>
  </div>

  <div class="col-lg-8">
    <div class="ag-card p-4 mb-3">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h2 class="h6 fw-bold mb-0">নোটিফিকেশন (<?= count($notifications) ?>)</h2>
        <form method="post" action="<?= e(url('/dashboard/notifications/read')) ?>">
          <?= CSRF::field() ?>
          <button class="btn btn-sm btn-outline-secondary">সব পঠিত</button>
        </form>
      </div>
      <?php foreach ($notifications as $n): ?>
        <div class="border-bottom py-2 small">
          <div class="fw-semibold"><?= e($n['title']) ?></div>
          <div class="text-secondary"><?= e($n['message']) ?></div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="ag-card p-4 mb-3">
      <h2 class="h6 fw-bold mb-2">আমার পোস্টসমূহ (<?= count($myPosts) ?>)</h2>
      <?php foreach ($myPosts as $p): ?>
        <div class="border-bottom py-2 small d-flex justify-content-between">
          <span><?= e($p['title']) ?></span>
          <span class="text-secondary"><?= e($p['status']) ?></span>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="row g-3">
      <div class="col-md-6">
        <div class="ag-card p-3">
          <h3 class="h6 fw-bold">আমার মার্কেটপ্লেস (<?= count($myMarket) ?>)</h3>
          <?php foreach ($myMarket as $m): ?>
            <div class="small border-bottom py-1"><?= e($m['title']) ?> — <?= format_bdt($m['price']) ?></div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="col-md-6">
        <div class="ag-card p-3">
          <h3 class="h6 fw-bold">আমার টু-লেট (<?= count($myRentals) ?>)</h3>
          <?php foreach ($myRentals as $r): ?>
            <div class="small border-bottom py-1"><?= e($r['title']) ?> — <?= format_bdt($r['monthly_rent']) ?></div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="col-12">
        <div class="ag-card p-3">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h3 class="h6 fw-bold mb-0">আমার চাকরির বিজ্ঞপ্তি (<?= count($myJobs ?? []) ?>)</h3>
            <a href="<?= e(url('/jobs')) ?>" class="small text-success text-decoration-none">চাকরি পাতা &rarr;</a>
          </div>
          <?php if (empty($myJobs)): ?>
            <div class="small text-secondary">এখনো কোনো চাকরির বিজ্ঞপ্তি পোস্ট করা হয়নি।</div>
          <?php else: ?>
            <?php foreach ($myJobs as $j): ?>
              <div class="small border-bottom py-1 d-flex justify-content-between">
                <span><strong><?= e($j['job_title']) ?></strong> — <?= e($j['company_name']) ?></span>
                <span class="text-success fw-semibold"><?= e($j['salary']) ?></span>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>
