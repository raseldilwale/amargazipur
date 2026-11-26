<div class="row justify-content-center">
  <div class="col-md-5">
    <div class="ag-card p-4">
      <h1 class="h4 fw-bold mb-3"><?= e(__('login')) ?></h1>
      <form method="post" action="<?= e(url('/login')) ?>">
        <?= CSRF::field() ?>
        <div class="mb-3">
          <label class="form-label">ইউজারনেম অথবা ইমেইল</label>
          <input type="text" name="email" class="form-control" placeholder="যেমন: admin অথবা your@email.com" required>
        </div>
        <div class="mb-3">
          <label class="form-label">পাসওয়ার্ড</label>
          <input type="password" name="password" class="form-control" required>
        </div>
        <div class="d-flex justify-content-between align-items-center mb-3 small">
          <a href="<?= e(url('/forgot-password')) ?>" class="text-decoration-none">পাসওয়ার্ড ভুলে গেছেন?</a>
          <a href="<?= e(url('/register')) ?>" class="text-decoration-none">নতুন অ্যাকাউন্ট খুলুন</a>
        </div>
        <button type="submit" class="btn ag-btn-primary w-100"><?= e(__('login')) ?></button>
      </form>
    </div>
  </div>
</div>
