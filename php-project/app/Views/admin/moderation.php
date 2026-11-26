<div class="row g-4">
  <div class="col-lg-6">
    <div class="ag-card p-4 mb-4">
      <h2 class="h6 fw-bold mb-3"><?= e(tr('নিষিদ্ধ শব্দ ও মডারেশন নিয়ম', 'Prohibited Keywords & Moderation Rules')) ?></h2>
      <form method="post" action="<?= e(url('/admin/moderation/rule-add')) ?>" class="row g-2 mb-3">
        <?= CSRF::field() ?>
        <div class="col-5"><input type="text" name="keyword" class="form-control form-control-sm" placeholder="<?= e(tr('শব্দ বা বাক্যাংশ', 'Word or phrase')) ?>" required></div>
        <div class="col-4">
          <select name="match_type" class="form-select form-select-sm">
            <option value="word"><?= e(tr('Word (শব্দ)', 'Word')) ?></option>
            <option value="phrase"><?= e(tr('Phrase (বাক্যাংশ)', 'Phrase')) ?></option>
          </select>
        </div>
        <div class="col-3"><button class="btn btn-sm btn-dark w-100"><?= e(tr('যুক্ত করুন', 'Add Rule')) ?></button></div>
      </form>
      <?php foreach ($rules as $rule): ?>
        <div class="d-flex justify-content-between align-items-center border-bottom py-1 small">
          <span><?= e($rule['keyword']) ?> (<?= e($rule['match_type']) ?>)</span>
          <form method="post" action="<?= e(url('/admin/moderation/rule-delete')) ?>">
            <?= CSRF::field() ?>
            <input type="hidden" name="rule_id" value="<?= (int)$rule['id'] ?>">
            <button class="btn btn-sm text-danger p-0"><?= e(tr('মুছুন', 'Delete')) ?></button>
          </form>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="ag-card p-4 mb-4">
      <h2 class="h6 fw-bold mb-3"><?= e(tr('ফ্ল্যাগকৃত পোস্ট পর্যালোচনা', 'Flagged Posts Queue')) ?></h2>
      <?php if (empty($flaggedPosts)): ?>
        <div class="small text-secondary"><?= e(tr('কোনো ফ্ল্যাগকৃত পোস্ট নেই।', 'No flagged posts in queue.')) ?></div>
      <?php else: ?>
        <?php foreach ($flaggedPosts as $fp): ?>
          <div class="border-bottom pb-2 mb-2">
            <div class="fw-bold small"><?= e($fp['title']) ?> (<?= e($fp['author_name']) ?>)</div>
            <div class="small text-danger mb-1"><?= e($fp['moderation_reason']) ?></div>
            <form method="post" action="<?= e(url('/admin/moderation/post')) ?>" class="d-flex gap-2">
              <?= CSRF::field() ?>
              <input type="hidden" name="post_id" value="<?= (int)$fp['id'] ?>">
              <button name="status" value="published" class="btn btn-sm btn-success"><?= e(tr('অনুমোদন', 'Approve')) ?></button>
              <button name="status" value="rejected" class="btn btn-sm btn-outline-danger"><?= e(tr('বাতিল', 'Reject')) ?></button>
            </form>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <div class="ag-card p-4">
      <h2 class="h6 fw-bold mb-3"><?= e(tr('ব্যবহারকারীর রিপোর্টসমূহ', 'User Reports')) ?></h2>
      <?php if (empty($reports)): ?>
        <div class="small text-secondary"><?= e(tr('কোনো রিপোর্ট নেই।', 'No open reports.')) ?></div>
      <?php else: ?>
        <?php foreach ($reports as $rep): ?>
          <div class="border-bottom pb-2 mb-2 small">
            <div><strong><?= e($rep['target_type']) ?> #<?= (int)$rep['target_id'] ?></strong> — <?= e($rep['reason']) ?> (<?= e($rep['status']) ?>)</div>
            <form method="post" action="<?= e(url('/admin/moderation/report')) ?>" class="d-flex gap-2 mt-1">
              <?= CSRF::field() ?>
              <input type="hidden" name="report_id" value="<?= (int)$rep['id'] ?>">
              <button name="status" value="resolved" class="btn btn-sm btn-dark"><?= e(tr('সমাধান', 'Resolve')) ?></button>
              <button name="status" value="dismissed" class="btn btn-sm btn-outline-secondary"><?= e(tr('বাতিল', 'Dismiss')) ?></button>
            </form>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>
