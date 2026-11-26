<div class="row justify-content-center">
  <div class="col-md-5">
    <div class="ag-card p-4">
      <h1 class="h4 fw-bold mb-2">প্রথম অ্যাডমিন অ্যাকাউন্ট তৈরি করুন</h1>
      <p class="small text-secondary mb-4">সিস্টেমে এখনো কোনো অ্যাডমিন অ্যাকাউন্ট তৈরি হয়নি। নিচের ফর্মে আপনার ইমেইল ও পাসওয়ার্ড দিয়ে প্রধান অ্যাডমিন তৈরি করুন।</p>
      <form method="post" action="<?= e(url('/admin/setup')) ?>">
        <?= CSRF::field() ?>
        <div class="mb-3">
          <label class="form-label">অ্যাডমিন নাম *</label>
          <input type="text" name="full_name" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">অ্যাডমিন ইমেইল *</label>
          <input type="email" name="email" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">পাসওয়ার্ড (কমপক্ষে ৮ অক্ষর) *</label>
          <input type="password" name="password" class="form-control" minlength="8" required>
        </div>
        <button type="submit" class="btn ag-btn-primary w-100">অ্যাডমিন তৈরি ও ড্যাশবোর্ডে প্রবেশ করুন</button>
      </form>
    </div>
  </div>
</div>
