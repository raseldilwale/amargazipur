<div class="ag-card p-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h6 fw-bold mb-0"><?= e(tr('সাংবাদিকদের জমাকৃত সংবাদ পর্যালোচনা', 'Journalist News Review Queue')) ?></h2>
    <a href="<?= e(url('/admin/create?type=news')) ?>" class="btn btn-sm ag-btn-primary">+ <?= e(tr('সংবাদ প্রকাশ করুন', 'Publish News')) ?></a>
  </div>
  <div class="table-responsive">
    <table class="table align-middle">
      <thead>
        <tr>
          <th><?= e(tr('শিরোনাম ও সাংবাদিক', 'Headline & Author')) ?></th>
          <th><?= e(tr('অবস্থা', 'Status')) ?></th>
          <th><?= e(tr('পদক্ষেপ', 'Action')) ?></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($articles)): ?>
          <tr><td colspan="3" class="text-center text-secondary py-4"><?= e(tr('কোনো সংবাদ পাওয়া যায়নি।', 'No news articles yet.')) ?></td></tr>
        <?php else: ?>
          <?php foreach ($articles as $art): ?>
            <tr>
              <td>
                <div class="fw-bold"><?= e($art['title']) ?></div>
                <div class="small text-secondary"><?= e(tr($art['category_bn'], $art['category_en'] ?? $art['category_bn'])) ?> · <?= e(tr('সাংবাদিক:', 'Reporter:')) ?> <?= e($art['author_name']) ?></div>
              </td>
              <td class="small fw-semibold"><?= e($art['status']) ?></td>
              <td>
                <form method="post" action="<?= e(url('/admin/news/review')) ?>" class="d-flex align-items-center gap-2">
                  <?= CSRF::field() ?>
                  <input type="hidden" name="news_id" value="<?= (int)$art['id'] ?>">
                  <label class="small"><input type="checkbox" name="is_featured" <?= $art['is_featured'] ? 'checked' : '' ?>> <?= e(tr('ফিচার্ড', 'Featured')) ?></label>
                  <button name="decision" value="published" class="btn btn-sm btn-success"><?= e(tr('প্রকাশ করুন', 'Publish')) ?></button>
                  <button name="decision" value="rejected" class="btn btn-sm btn-outline-danger"><?= e(tr('বাতিল', 'Reject')) ?></button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
