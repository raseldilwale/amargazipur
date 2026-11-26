<div class="ag-card p-4 mb-4">
  <h2 class="h6 fw-bold mb-3"><?= e(tr('নতুন বিজ্ঞাপন তৈরি করুন', 'Create New Advertisement')) ?></h2>
  <form method="post" action="<?= e(url('/admin/ads/store')) ?>" enctype="multipart/form-data" class="row g-2">
    <?= CSRF::field() ?>
    <div class="col-md-4"><input type="text" name="title" class="form-control" placeholder="<?= e(tr('বিজ্ঞাপনের শিরোনাম', 'Ad title')) ?>" required></div>
    <div class="col-md-3">
      <select name="placement" class="form-select">
        <option value="home_banner"><?= e(tr('হোম ব্যানার', 'Home Banner')) ?></option>
        <option value="sidebar"><?= e(tr('সাইডবার', 'Sidebar')) ?></option>
        <option value="news_inline"><?= e(tr('সংবাদ ইনলাইন', 'News Inline')) ?></option>
        <option value="marketplace_top"><?= e(tr('মার্কেটপ্লেস শীর্ষ', 'Marketplace Top')) ?></option>
      </select>
    </div>
    <div class="col-md-5"><input type="url" name="target_url" class="form-control" placeholder="<?= e(tr('লিংক URL', 'Target URL (https://...)')) ?>" required></div>
    <div class="col-md-3"><input type="date" name="start_date" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
    <div class="col-md-3"><input type="date" name="end_date" class="form-control" value="<?= date('Y-m-d', strtotime('+30 days')) ?>" required></div>
    <div class="col-md-4"><input type="file" name="banner_image" class="form-control" accept="image/jpeg,image/png,image/webp"></div>
    <div class="col-md-2"><button class="btn btn-dark w-100"><?= e(tr('প্রকাশ', 'Publish')) ?></button></div>
  </form>
</div>

<div class="ag-card p-4">
  <h2 class="h6 fw-bold mb-3"><?= e(tr('সকল বিজ্ঞাপন', 'All Advertisements')) ?></h2>
  <div class="table-responsive">
    <table class="table table-sm align-middle">
      <thead>
        <tr>
          <th><?= e(tr('শিরোনাম', 'Title')) ?></th>
          <th><?= e(tr('স্থান', 'Placement')) ?></th>
          <th><?= e(tr('সময়কাল', 'Schedule')) ?></th>
          <th><?= e(tr('অবস্থা', 'Status')) ?></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($ads)): ?>
          <tr><td colspan="4" class="text-center text-secondary py-3"><?= e(tr('কোনো বিজ্ঞাপন নেই।', 'No advertisements found.')) ?></td></tr>
        <?php else: ?>
          <?php foreach ($ads as $ad): ?>
            <tr><td><?= e($ad['title']) ?></td><td><?= e($ad['placement']) ?></td><td><?= e($ad['start_date']) ?> - <?= e($ad['end_date']) ?></td><td><?= e($ad['status']) ?></td></tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
