<div class="row justify-content-center">
  <div class="col-md-7">
    <div class="ag-card p-4">
      <h1 class="h4 fw-bold mb-2">আমার গাজীপুর — নতুন অ্যাকাউন্ট নিবন্ধন</h1>
      <p class="small text-secondary mb-4">আপনার নির্বাচিত অ্যাকাউন্ট ক্যাটাগরিগুলো অ্যাডমিন অনুমোদনের পর সক্রিয় হবে।</p>
      <form method="post" action="<?= e(url('/register')) ?>">
        <?= CSRF::field() ?>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">পূর্ণ নাম *</label>
            <input type="text" name="full_name" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">ইউজারনেম (ঐচ্ছিক)</label>
            <input type="text" name="username" class="form-control" placeholder="যেমন: rahim_gazipur">
          </div>
          <div class="col-md-12">
            <label class="form-label">ইমেইল ঠিকানা *</label>
            <input type="email" name="email" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">পাসওয়ার্ড (কমপক্ষে ৮ অক্ষর) *</label>
            <input type="password" name="password" class="form-control" minlength="8" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">পাসওয়ার্ড নিশ্চিত করুন *</label>
            <input type="password" name="confirm_password" class="form-control" minlength="8" required>
          </div>
          <div class="col-12">
            <label class="form-label">উপজেলা / এলাকা</label>
            <select name="upazila" class="form-select">
              <?php foreach (gazipur_upazilas() as $key => $labels): ?>
                <option value="<?= e($key) ?>"><?= e($labels['bn']) ?> (<?= e($labels['en']) ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label d-block mb-2">অ্যাকাউন্ট ক্যাটাগরি নির্বাচন করুন (একাধিক নির্বাচন করা যাবে)</label>
            <div class="row g-2">
              <?php foreach (account_categories() as $slug => $labels): ?>
                <div class="col-md-6">
                  <label class="border rounded p-2 d-flex align-items-center gap-2 bg-light w-100">
                    <input type="checkbox" name="categories[]" value="<?= e($slug) ?>" class="form-check-input mt-0" <?= $slug === 'general_user' ? 'checked' : '' ?>>
                    <span class="small"><?= e($labels['bn']) ?> <span class="text-secondary">(<?= e($labels['en']) ?>)</span></span>
                  </label>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
          <div class="col-12">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="terms_accepted" id="termsCheck" required>
              <label class="form-check-label small" for="termsCheck">
                আমি AmarGazipur-এর শর্তাবলী ও গোপনীয়তা নীতিতে সম্মতি প্রদান করছি।
              </label>
            </div>
          </div>
          <div class="col-12">
            <button type="submit" class="btn ag-btn-primary w-100">অ্যাকাউন্ট তৈরি করুন</button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>
