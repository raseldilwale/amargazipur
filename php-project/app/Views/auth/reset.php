<div class="row justify-content-center">
  <div class="col-md-5">
    <div class="ag-card p-4">
      <h1 class="h4 fw-bold mb-3">নতুন পাসওয়ার্ড সেট করুন</h1>
      <form method="post" action="<?= e(url('/reset-password')) ?>">
        <?= CSRF::field() ?>
        <input type="hidden" name="token" value="<?= e($token ?? '') ?>">
        <div class="mb-3">
          <label class="form-label">ইমেইল</label>
          <input type="email" name="email" value="<?= e($email ?? '') ?>" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">নতুন পাসওয়ার্ড</label>
          <input type="password" name="password" class="form-control" minlength="8" required>
        </div>
        <div class="mb-3">
          <label class="form-label">পাসওয়ার্ড নিশ্চিত করুন</label>
          <input type="password" name="confirm_password" class="form-control" minlength="8" required>
        </div>
        <button type="submit" class="btn ag-btn-primary w-100">পাসওয়ার্ড হালনাগাদ করুন</button>
      </form>
    </div>
  </div>
</div>
