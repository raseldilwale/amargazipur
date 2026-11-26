<?php
$tabs = [
    'posts'       => tr('কমিউনিটি পোস্ট', 'Community Posts'),
    'marketplace' => tr('মার্কেটপ্লেস', 'Marketplace'),
    'rentals'     => tr('বাসা ভাড়া / টু-লেট', 'House Rent'),
    'jobs'        => tr('চাকরি ও নিয়োগ', 'Jobs'),
    'blood'       => tr('রক্তদাতা ও অনুরোধ', 'Blood Service'),
    'education'   => tr('শিক্ষা প্রতিষ্ঠান', 'Education'),
    'businesses'  => tr('ব্যবসা ডিরেক্টরি', 'Businesses'),
];
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
  <div class="d-flex flex-wrap gap-2">
    <?php foreach ($tabs as $key => $label): ?>
      <a href="<?= e(url('/admin/content?tab=' . $key)) ?>" class="btn btn-sm <?= $tab === $key ? 'btn-dark' : 'btn-outline-secondary' ?>">
        <?= e($label) ?>
      </a>
    <?php endforeach; ?>
  </div>
  <a href="<?= e(url('/admin/create?type=' . ($tab === 'blood' ? 'blood_request' : ($tab === 'rentals' ? 'rental' : ($tab === 'jobs' ? 'job' : ($tab === 'businesses' ? 'business' : ($tab === 'marketplace' ? 'marketplace' : ($tab === 'education' ? 'education' : 'post')))))))) ?>" class="btn btn-sm ag-btn-primary">
    + <?= e(tr('নতুন যোগ করুন', 'Add New')) ?>
  </a>
</div>

<div class="ag-card p-4">
  <div class="table-responsive">
    <table class="table align-middle">
      <thead>
        <tr>
          <th>ID</th>
          <th><?= e(tr('শিরোনাম / নাম', 'Title / Name')) ?></th>
          <th><?= e(tr('বিভাগ / তথ্য', 'Category / Info')) ?></th>
          <th><?= e(tr('অবস্থা', 'Status')) ?></th>
          <th class="text-end"><?= e(tr('পদক্ষেপ', 'Action')) ?></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($items)): ?>
          <tr><td colspan="5" class="text-center text-secondary py-4"><?= e(tr('কোনো তথ্য পাওয়া যায়নি।', 'No items found in this section.')) ?></td></tr>
        <?php else: ?>
          <?php foreach ($items as $row): ?>
            <tr>
              <td class="small text-secondary">#<?= (int)$row['id'] ?></td>
              <td>
                <div class="fw-bold"><?= e($row['title']) ?></div>
                <div class="small text-secondary"><?= e($row['subtitle'] ?? '') ?></div>
              </td>
              <td class="small"><?= e($row['meta'] ?? '') ?></td>
              <td class="small fw-semibold"><?= e($row['status'] ?? 'active') ?></td>
              <td class="text-end">
                <form method="post" action="<?= e(url('/admin/content/delete')) ?>" class="d-inline" onsubmit="return confirm('<?= e(tr('আপনি কি এটি মুছে ফেলতে চান?', 'Delete this item?')) ?>');">
                  <?= CSRF::field() ?>
                  <input type="hidden" name="table" value="<?= e($row['table_key']) ?>">
                  <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                  <input type="hidden" name="tab" value="<?= e($tab) ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger"><?= e(tr('মুছুন', 'Delete')) ?></button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
