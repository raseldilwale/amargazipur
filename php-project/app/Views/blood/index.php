<?php $user = Auth::user(); ?>
<div class="row g-4">
  <div class="col-lg-8">
    <div class="ag-card p-4 mb-4">
      <h1 class="h4 fw-bold mb-3">গাজীপুর রক্তদাতা ডিরেক্টরি</h1>
      <form method="get" action="<?= e(url('/blood-donors')) ?>" class="row g-2">
        <div class="col-md-5">
          <select name="group" class="form-select">
            <option value="">সকল রক্তের গ্রুপ</option>
            <?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $g): ?>
              <option value="<?= $g ?>" <?= $group === $g ? 'selected' : '' ?>><?= $g ?></option>
            <?php endforeach; ?>
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
        <div class="col-md-2"><button class="btn btn-danger w-100">খুঁজুন</button></div>
      </form>
    </div>

    <div class="row g-3 mb-4">
      <?php foreach ($donors as $d): ?>
        <div class="col-md-6">
          <div class="ag-card p-3 h-100">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <span class="fw-bold text-danger fs-5"><?= e($d['blood_group']) ?></span>
              <span class="small <?= $d['is_available'] ? 'text-success' : 'text-secondary' ?>"><?= $d['is_available'] ? 'রক্তদানে প্রস্তুত' : 'বর্তমানে অনুপলব্ধ' ?></span>
            </div>
            <div class="fw-semibold"><?= e($d['donor_name']) ?></div>
            <div class="ag-meta mb-2"><?= e($d['area']) ?> · <?= e($d['upazila']) ?></div>
            <?php if ($d['consent_public_contact'] && $user): ?>
              <div class="small fw-semibold">ফোন: <?= e($d['phone']) ?> (<?= e($d['contact_preference']) ?>)</div>
            <?php elseif (!$user): ?>
              <button type="button" class="btn btn-sm btn-outline-danger" data-require-auth="true">যোগাযোগ তথ্য দেখতে লগইন করুন</button>
            <?php else: ?>
              <div class="small text-secondary">দাতার গোপনীয়তা নীতিতে সরাসরি নম্বর গোপন রাখা হয়েছে।</div>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="ag-card p-3 mb-3">
      <h2 class="h6 fw-bold text-danger mb-3">জরুরি রক্তের অনুরোধ</h2>
      <?php foreach ($requests as $req): ?>
        <div class="border-bottom pb-2 mb-2 small">
          <div class="fw-bold"><?= e($req['blood_group']) ?> (<?= (int)$req['units_needed'] ?> ব্যাগ) — <?= e($req['patient_name']) ?></div>
          <div class="text-secondary"><?= e($req['hospital_name']) ?>, <?= e($req['upazila']) ?></div>
          <div>তারিখ: <?= e($req['needed_date']) ?></div>
          <?php if ($user): ?>
            <div class="fw-semibold">যোগাযোগ: <?= e($req['contact_phone']) ?></div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>

      <?php if ($user): ?>
      <form method="post" action="<?= e(url('/blood-donors/request')) ?>" class="mt-3 pt-2 border-top">
        <?= CSRF::field() ?>
        <div class="fw-bold small mb-2">নতুন রক্তের অনুরোধ পোস্ট করুন</div>
        <input type="text" name="patient_name" class="form-control form-control-sm mb-2" placeholder="রোগীর নাম" required>
        <div class="row g-1 mb-2">
          <div class="col-6">
            <select name="blood_group" class="form-select form-select-sm">
              <?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $g): ?>
                <option value="<?= $g ?>"><?= $g ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-6"><input type="number" name="units_needed" class="form-control form-control-sm" value="1" min="1"></div>
        </div>
        <input type="text" name="hospital_name" class="form-control form-control-sm mb-2" placeholder="হাসপাতালের নাম" required>
        <input type="text" name="contact_phone" class="form-control form-control-sm mb-2" placeholder="যোগাযোগ নম্বর" required>
        <button class="btn btn-danger btn-sm w-100">অনুরোধ পোস্ট করুন</button>
      </form>
      <?php else: ?>
        <button type="button" class="btn btn-outline-danger btn-sm w-100 mt-2" data-require-auth="true">রক্তের অনুরোধ বা দাতা নিবন্ধন</button>
      <?php endif; ?>
    </div>
  </div>
</div>
