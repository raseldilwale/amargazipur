<div class="row justify-content-center">
  <div class="col-md-5">
    <div class="ag-card p-4">
      <h1 class="h4 fw-bold mb-3">পাসওয়ার্ড পুনরুদ্ধার</h1>
      <form method="post" action="<?= e(url('/forgot-password')) ?>">
        <?= CSRF::field() ?>
        <div class="mb-3">
          <label class="form-label">নিবন্ধিত ইমেইল ঠিকানা</label>
          <input type="email" name="email" class="form-control" required>
        </div>
        <button type="submit" class="btn ag-btn-primary w-100">রিসেট লিংক পাঠান</button>
      </form>
      <?php if (!empty($_SESSION['_demo_reset_link'])): ?>
        <div class="alert alert-info mt-3 small mb-0">
          রিসেট লিংক: <a href="<?= e($_SESSION['_demo_reset_link']) ?>"><?= e($_SESSION['_demo_reset_link']) ?></a>
        </div>
        <?php unset($_SESSION['_demo_reset_link']); ?>
      <?php endif; ?>
    </div>
  </div>
</div>
