<?php
$user = Auth::user();
$canPostJob = $user && (Auth::hasApprovedCategory('job_poster') || Auth::hasRole('admin'));
$jobTypes = [
    'full_time'  => tr('ফুল-টাইম', 'Full-Time'),
    'part_time'  => tr('পার্ট-টাইম', 'Part-Time'),
    'contract'   => tr('চুক্তিভিত্তিক', 'Contract'),
    'internship' => tr('ইন্টার্নশিপ', 'Internship'),
    'remote'     => tr('রিমোট / বাসা থেকে', 'Remote'),
];
?>
<div class="ag-card p-4 mb-4">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
      <h1 class="h4 fw-bold mb-1"><?= e(tr('গাজীপুর চাকরি ও নিয়োগ বিজ্ঞপ্তি', 'Gazipur Jobs & Recruitment')) ?></h1>
      <p class="small text-secondary mb-0"><?= e(tr('স্থানীয় কোম্পানি, কারখানা, অফিস ও প্রতিষ্ঠানের চাকরির সুযোগ খুঁজুন বা প্রকাশ করুন।', 'Browse or publish local job openings across Gazipur District.')) ?></p>
    </div>
  </div>

  <form method="get" action="<?= e(url('/jobs')) ?>" class="row g-2 mb-3">
    <div class="col-md-4">
      <input type="text" name="q" value="<?= e($q) ?>" class="form-control" placeholder="<?= e(tr('পদবি, কোম্পানি বা এলাকা খুঁজুন...', 'Search position, company or area...')) ?>">
    </div>
    <div class="col-md-3">
      <select name="job_type" class="form-select">
        <option value=""><?= e(tr('সকল ধরন', 'All Job Types')) ?></option>
        <?php foreach ($jobTypes as $tk => $tl): ?>
          <option value="<?= e($tk) ?>" <?= $jobType === $tk ? 'selected' : '' ?>><?= e($tl) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <select name="upazila" class="form-select">
        <option value=""><?= e(tr('সকল উপজেলা', 'All Upazilas')) ?></option>
        <?php foreach (gazipur_upazilas() as $k => $v): ?>
          <option value="<?= e($k) ?>" <?= $upazila === $k ? 'selected' : '' ?>><?= e(I18n::locale() === 'en' ? $v['en'] : $v['bn']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2"><button class="btn btn-dark w-100"><?= e(tr('খুঁজুন', 'Search')) ?></button></div>
  </form>

  <?php if ($canPostJob): ?>
  <form method="post" action="<?= e(url('/jobs/store')) ?>" enctype="multipart/form-data" class="border-top pt-3">
    <?= CSRF::field() ?>
    <h2 class="h6 fw-bold mb-2"><?= e(tr('নতুন চাকরির বিজ্ঞপ্তি পোস্ট করুন', 'Post a New Job Circular')) ?></h2>
    <div class="row g-2 mb-2">
      <div class="col-md-4">
        <label class="form-label small fw-semibold mb-1"><?= e(tr('কোম্পানির নাম *', 'Company Name *')) ?></label>
        <input type="text" name="company_name" class="form-control" placeholder="<?= e(tr('যেমন: গাজীপুর টেক লিঃ', 'e.g. Gazipur Tech Ltd.')) ?>" required>
      </div>
      <div class="col-md-4">
        <label class="form-label small fw-semibold mb-1"><?= e(tr('পদবি (পদের নাম) *', 'Position / Job Title *')) ?></label>
        <input type="text" name="job_title" class="form-control" placeholder="<?= e(tr('যেমন: সেলস এক্সিকিউটিভ / ইঞ্জিনিয়ার', 'e.g. Sales Executive / Engineer')) ?>" required>
      </div>
      <div class="col-md-4">
        <label class="form-label small fw-semibold mb-1"><?= e(tr('বেতন (Salary) *', 'Salary *')) ?></label>
        <input type="text" name="salary" class="form-control" placeholder="<?= e(tr('যেমন: ২০,০০০ - ২৫,০০০ টাকা / আলোচনা সাপেক্ষে', 'e.g. 20,000 - 25,000 BDT')) ?>" required>
      </div>
    </div>
    <div class="row g-2 mb-2">
      <div class="col-md-3">
        <label class="form-label small fw-semibold mb-1"><?= e(tr('কর্মস্থল / লোকেশন *', 'Location *')) ?></label>
        <input type="text" name="location" class="form-control" placeholder="<?= e(tr('যেমন: চান্দনা চৌরাস্তা, গাজীপুর', 'e.g. Chandna Chowrasta')) ?>" required>
      </div>
      <div class="col-md-3">
        <label class="form-label small fw-semibold mb-1"><?= e(tr('উপজেলা', 'Upazila')) ?></label>
        <select name="upazila" class="form-select">
          <?php foreach (gazipur_upazilas() as $k => $v): ?>
            <option value="<?= e($k) ?>"><?= e(I18n::locale() === 'en' ? $v['en'] : $v['bn']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label small fw-semibold mb-1"><?= e(tr('যোগাযোগ নম্বর বা ইমেইল *', 'Contact Phone or Email *')) ?></label>
        <input type="text" name="contact_info" class="form-control" placeholder="017XXXXXXXX / hr@company.com" required>
      </div>
      <div class="col-md-3">
        <label class="form-label small fw-semibold mb-1"><?= e(tr('চাকরির ধরন', 'Job Type')) ?></label>
        <select name="job_type" class="form-select">
          <?php foreach ($jobTypes as $tk => $tl): ?>
            <option value="<?= e($tk) ?>"><?= e($tl) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="row g-2 mb-2">
      <div class="col-md-3">
        <label class="form-label small mb-1"><?= e(tr('আবেদনের শেষ তারিখ (ঐচ্ছিক)', 'Deadline (Optional)')) ?></label>
        <input type="date" name="deadline" class="form-control">
      </div>
      <div class="col-md-9">
        <label class="form-label small mb-1"><?= e(tr('কাজের বিবরণ ও যোগ্যতা', 'Job Description & Requirements')) ?></label>
        <textarea name="description" rows="2" class="form-control" placeholder="<?= e(tr('শিক্ষাগত যোগ্যতা, অভিজ্ঞতা ও কাজের বিবরণ লিখুন...', 'Education, experience and job responsibilities...')) ?>"></textarea>
      </div>
    </div>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
      <input type="file" name="image" accept="image/jpeg,image/png,image/webp" class="form-control form-control-sm w-auto">
      <button type="submit" class="btn ag-btn-primary"><?= e(tr('চাকরি প্রকাশ করুন', 'Publish Job')) ?></button>
    </div>
  </form>
  <?php elseif ($user): ?>
    <div class="small text-secondary border-top pt-2">
      <?= e(tr('চাকরির বিজ্ঞপ্তি পোস্ট করতে ড্যাশবোর্ড থেকে অ্যাডমিন অনুমোদিত ', 'To post jobs, you need an Admin-approved ')) ?>
      <strong><?= e(tr('চাকরিদাতা / নিয়োগদাতা (Job Poster)', 'Job Poster / Employer')) ?></strong>
      <?= e(tr(' রোল/ক্যাটাগরি প্রয়োজন।', ' account role/category.')) ?>
    </div>
  <?php else: ?>
    <div class="border-top pt-2 d-flex flex-wrap align-items-center justify-content-between gap-2">
      <span class="small text-secondary"><?= e(tr('চাকরির বিজ্ঞাপন পোস্ট করতে চাকরিদাতা (Job Poster) হিসেবে নিবন্ধন ও লগইন করুন।', 'Sign in or register as a Job Poster to publish job openings.')) ?></span>
      <button type="button" class="btn ag-btn-primary btn-sm" data-require-auth="true"><?= e(tr('চাকরি পোস্ট করতে লগইন করুন', 'Sign in to Post Job')) ?></button>
    </div>
  <?php endif; ?>
</div>

<?php if (empty($jobs)): ?>
  <div class="ag-card p-4 text-center text-secondary small">
    <?= e(tr('এই মুহূর্তে কোনো চাকরির বিজ্ঞপ্তি পাওয়া যায়নি।', 'No job listings found matching your search.')) ?>
  </div>
<?php else: ?>
<div class="row g-3">
  <?php foreach ($jobs as $j): ?>
    <div class="col-md-6">
      <div class="ag-card p-4 h-100 d-flex flex-column">
        <div class="d-flex justify-content-between align-items-start gap-3 mb-2">
          <div>
            <div class="ag-meta mb-1">
              <?= e($jobTypes[$j['job_type']] ?? $j['job_type']) ?> · <?= e($j['upazila']) ?>
              <?php if (!empty($j['deadline'])): ?>
                · <?= e(tr('শেষ তারিখ:', 'Deadline:')) ?> <?= e($j['deadline']) ?>
              <?php endif; ?>
            </div>
            <h2 class="h6 fw-bold mb-1"><?= e($j['job_title']) ?></h2>
            <div class="small fw-semibold text-dark"><?= e($j['company_name']) ?></div>
          </div>
          <?php if (!empty($j['image_path'])): ?>
            <img src="<?= e(url('/' . $j['image_path'])) ?>" alt="<?= e($j['company_name']) ?>" class="rounded border" style="width:56px;height:56px;object-fit:cover;">
          <?php endif; ?>
        </div>

        <div class="fw-bold text-success mb-2"><?= e(tr('বেতন:', 'Salary:')) ?> <?= e($j['salary']) ?></div>
        <div class="small text-secondary mb-2"><strong><?= e(tr('লোকেশন:', 'Location:')) ?></strong> <?= e($j['location']) ?></div>
        <p class="small text-secondary flex-grow-1 mb-3"><?= e($j['description']) ?></p>

        <div class="border-top pt-2 small d-flex flex-wrap justify-content-between align-items-center gap-2">
          <div>
            <strong><?= e(tr('যোগাযোগ:', 'Contact:')) ?></strong>
            <span class="fw-semibold text-dark"><?= e($j['contact_info']) ?></span>
          </div>
          <span class="text-secondary"><?= e($j['poster_name']) ?></span>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
