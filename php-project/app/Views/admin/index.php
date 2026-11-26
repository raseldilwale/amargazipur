<?php
$cards = [
    [
        'label' => tr('মোট ব্যবহারকারী', 'Total Users'),
        'value' => (int)$stats['total_users'],
        'color' => 'text-dark',
        'href'  => url('/admin/users'),
    ],
    [
        'label' => tr('অপেক্ষমাণ ক্যাটাগরি', 'Pending Categories'),
        'value' => (int)$stats['pending_cats'],
        'color' => 'text-warning',
        'href'  => url('/admin/users?filter=pending'),
    ],
    [
        'label' => tr('অপেক্ষমাণ সংবাদ', 'Pending News'),
        'value' => (int)$stats['pending_news'],
        'color' => 'text-danger',
        'href'  => url('/admin/news'),
    ],
    [
        'label' => tr('মডারেশন কিউ পোস্ট', 'Moderation Queue Posts'),
        'value' => (int)$stats['pending_posts'],
        'color' => 'text-danger',
        'href'  => url('/admin/moderation'),
    ],
    [
        'label' => tr('মোট পোস্ট', 'Total Posts'),
        'value' => (int)$stats['total_posts'],
        'color' => 'text-dark',
        'href'  => url('/admin/content?tab=posts'),
    ],
    [
        'label' => tr('মার্কেটপ্লেস', 'Marketplace Listings'),
        'value' => (int)$stats['total_market'],
        'color' => 'text-dark',
        'href'  => url('/admin/content?tab=marketplace'),
    ],
    [
        'label' => tr('বাসা ভাড়া / টু-লেট', 'House Rent / To-Let'),
        'value' => (int)$stats['total_rentals'],
        'color' => 'text-dark',
        'href'  => url('/admin/content?tab=rentals'),
    ],
    [
        'label' => tr('চাকরি ও নিয়োগ', 'Jobs & Hiring'),
        'value' => (int)($stats['total_jobs'] ?? 0),
        'color' => 'text-success',
        'href'  => url('/admin/content?tab=jobs'),
    ],
    [
        'label' => tr('খোলা রিপোর্ট', 'Open Reports'),
        'value' => (int)$stats['open_reports'],
        'color' => 'text-dark',
        'href'  => url('/admin/moderation'),
    ],
    [
        'label' => tr('রক্তদাতা ও অনুরোধ', 'Blood Donors & Requests'),
        'value' => (int)($stats['total_blood'] ?? 0),
        'color' => 'text-danger',
        'href'  => url('/admin/content?tab=blood'),
    ],
    [
        'label' => tr('শিক্ষা প্রতিষ্ঠান', 'Education Directory'),
        'value' => (int)($stats['total_education'] ?? 0),
        'color' => 'text-dark',
        'href'  => url('/admin/content?tab=education'),
    ],
    [
        'label' => tr('ব্যবসা ডিরেক্টরি', 'Businesses'),
        'value' => (int)($stats['total_businesses'] ?? 0),
        'color' => 'text-dark',
        'href'  => url('/admin/content?tab=businesses'),
    ],
    [
        'label' => tr('সক্রিয় বিজ্ঞাপন', 'Active Ads'),
        'value' => (int)$stats['active_ads'],
        'color' => 'text-success',
        'href'  => url('/admin/ads'),
    ],
];
?>

<div class="row g-3 mb-4">
  <?php foreach ($cards as $card): ?>
    <div class="col-6 col-md-3">
      <a href="<?= e($card['href']) ?>" class="text-decoration-none d-block h-100">
        <div class="ag-card ag-stat-card p-3 h-100 d-flex flex-column justify-content-between">
          <div class="small text-secondary mb-1"><?= e($card['label']) ?></div>
          <div class="d-flex align-items-baseline justify-content-between">
            <div class="fs-4 fw-bold <?= e($card['color']) ?>"><?= $card['value'] ?></div>
            <span class="small text-success fw-medium"><?= e(tr('দেখুন →', 'View →')) ?></span>
          </div>
        </div>
      </a>
    </div>
  <?php endforeach; ?>
</div>

<div class="ag-card p-4">
  <h2 class="h6 fw-bold mb-3"><?= e(tr('সাম্প্রতিক অ্যাডমিন অ্যাক্টিভিটি লগ', 'Recent Admin Activity Log')) ?></h2>
  <div class="table-responsive">
    <table class="table table-sm align-middle">
      <thead>
        <tr>
          <th><?= e(tr('সময়', 'Time')) ?></th>
          <th><?= e(tr('অ্যাডমিন', 'Admin')) ?></th>
          <th><?= e(tr('কার্যক্রম', 'Action')) ?></th>
          <th><?= e(tr('টার্গেট', 'Target')) ?></th>
          <th><?= e(tr('বিস্তারিত', 'Details')) ?></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($recentLogs)): ?>
          <tr><td colspan="5" class="text-center text-secondary small py-3"><?= e(tr('কোনো লগ পাওয়া যায়নি।', 'No activity logs yet.')) ?></td></tr>
        <?php else: ?>
          <?php foreach ($recentLogs as $log): ?>
            <tr>
              <td class="small"><?= e($log['created_at']) ?></td>
              <td class="small"><?= e($log['admin_name']) ?></td>
              <td class="small fw-semibold"><?= e($log['action']) ?></td>
              <td class="small"><?= e($log['target_type']) ?> #<?= (int)$log['target_id'] ?></td>
              <td class="small text-secondary"><?= e($log['details']) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
