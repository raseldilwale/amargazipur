<section class="row g-4 align-items-center mb-5">
  <div class="col-lg-6">
    <p class="small text-uppercase text-success fw-semibold mb-2">গাজীপুর জেলা · বাংলাদেশ</p>
    <h1 class="display-6 fw-bold mb-3"><?= e(__('hero_title')) ?></h1>
    <p class="text-secondary fs-6 mb-4"><?= e(__('hero_sub')) ?></p>
    <div class="d-flex flex-wrap gap-2">
      <a href="<?= e(url('/news')) ?>" class="btn ag-btn-primary"><?= e(__('browse_news')) ?></a>
      <a href="<?= e(url('/register')) ?>" class="btn btn-outline-dark"><?= e(__('join_now')) ?></a>
    </div>
  </div>
  <div class="col-lg-6">
    <img src="<?= e(asset('images/hero_gazipur_community.jpg')) ?>" alt="গাজীপুর শহর ও ভাওয়াল" class="ag-hero-img shadow-sm" loading="lazy">
  </div>
</section>

<?php if (!empty($activeAds)): ?>
<section class="mb-5">
  <?php foreach ($activeAds as $ad): ?>
    <a href="<?= e($ad['target_url']) ?>" class="d-block ag-card p-3 text-decoration-none text-dark mb-2">
      <div class="small text-secondary mb-1">বিজ্ঞাপন</div>
      <div class="fw-semibold"><?= e($ad['title']) ?></div>
    </a>
  <?php endforeach; ?>
</section>
<?php endif; ?>

<section class="mb-5">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h4 fw-bold mb-0">গাজীপুর স্থানীয় সংবাদ</h2>
    <a href="<?= e(url('/news')) ?>" class="small text-success fw-semibold text-decoration-none">সকল সংবাদ &rarr;</a>
  </div>
  <div class="row g-3">
    <?php foreach ($featuredNews as $item): ?>
      <div class="col-md-6 col-lg-3">
        <div class="ag-card h-100 p-3 d-flex flex-column">
          <div class="ag-meta mb-1"><?= e($item['category_bn']) ?> · <?= e($item['upazila']) ?></div>
          <h3 class="h6 fw-bold mb-2">
            <a href="<?= e(url('/news/' . $item['slug'])) ?>" class="text-decoration-none text-dark"><?= e($item['title']) ?></a>
          </h3>
          <p class="small text-secondary mb-3 flex-grow-1"><?= e(mb_substr($item['summary'], 0, 100, 'UTF-8')) ?>...</p>
          <div class="ag-meta"><?= e($item['author_name']) ?></div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<section class="mb-5">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h4 fw-bold mb-0">সাম্প্রতিক কমিউনিটি পোস্ট</h2>
    <a href="<?= e(url('/community')) ?>" class="small text-success fw-semibold text-decoration-none">কমিউনিটি ফোরাম &rarr;</a>
  </div>
  <div class="row g-3">
    <?php foreach ($latestPosts as $post): ?>
      <div class="col-md-6">
        <div class="ag-card p-3 h-100">
          <div class="ag-meta mb-1"><?= e($post['category']) ?> · <?= e($post['upazila']) ?> · <?= e($post['author_name']) ?></div>
          <h3 class="h6 fw-bold mb-2"><?= e($post['title']) ?></h3>
          <p class="small text-secondary mb-2"><?= e(mb_substr($post['content'], 0, 140, 'UTF-8')) ?>...</p>
          <div class="ag-meta">পছন্দ: <?= (int)$post['likes_count'] ?> · মন্তব্য: <?= (int)$post['comments_count'] ?></div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<section class="row g-4">
  <div class="col-lg-6">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2 class="h5 fw-bold mb-0">বাসা ভাড়া ও টু-লেট</h2>
      <a href="<?= e(url('/rentals')) ?>" class="small text-success text-decoration-none">সব দেখুন &rarr;</a>
    </div>
    <?php foreach ($latestRentals as $r): ?>
      <div class="ag-card p-3 mb-2">
        <div class="fw-bold"><?= e($r['title']) ?></div>
        <div class="ag-meta"><?= e($r['location']) ?> · <?= e($r['upazila']) ?> · <?= format_bdt($r['monthly_rent']) ?>/মাস</div>
      </div>
    <?php endforeach; ?>
  </div>
  <div class="col-lg-6">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2 class="h5 fw-bold mb-0">জরুরি রক্তের প্রয়োজন</h2>
      <a href="<?= e(url('/blood-donors')) ?>" class="small text-danger text-decoration-none">রক্তদাতা খুঁজুন &rarr;</a>
    </div>
    <?php foreach ($openBloodRequests as $br): ?>
      <div class="ag-card p-3 mb-2">
        <div class="fw-bold text-danger"><?= e($br['blood_group']) ?> রক্ত প্রয়োজন — <?= e($br['patient_name']) ?></div>
        <div class="ag-meta"><?= e($br['hospital_name']) ?> · <?= e($br['upazila']) ?> · তারিখ: <?= e($br['needed_date']) ?></div>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<?php if (!empty($latestJobs)): ?>
<section class="mt-5">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h4 fw-bold mb-0"><?= e(tr('সাম্প্রতিক চাকরি ও নিয়োগ', 'Recent Gazipur Jobs')) ?></h2>
    <a href="<?= e(url('/jobs')) ?>" class="small text-success fw-semibold text-decoration-none"><?= e(tr('সকল চাকরি দেখুন', 'All Jobs')) ?> &rarr;</a>
  </div>
  <div class="row g-3">
    <?php foreach ($latestJobs as $job): ?>
      <div class="col-md-6">
        <div class="ag-card p-3 h-100">
          <div class="ag-meta mb-1"><?= e($job['company_name']) ?> · <?= e($job['upazila']) ?></div>
          <h3 class="h6 fw-bold mb-1"><?= e($job['job_title']) ?></h3>
          <div class="small fw-semibold text-success mb-1"><?= e(tr('বেতন:', 'Salary:')) ?> <?= e($job['salary']) ?></div>
          <div class="small text-secondary"><?= e($job['location']) ?> · <?= e(tr('যোগাযোগ:', 'Contact:')) ?> <?= e($job['contact_info']) ?></div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>
