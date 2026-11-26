<?php $user = Auth::user(); ?>
<div class="row g-4">
  <div class="col-lg-8">
    <div class="ag-card p-4 mb-4">
      <h1 class="h4 fw-bold mb-3">গাজীপুর কমিউনিটি ফোরাম</h1>
      <form method="get" action="<?= e(url('/community')) ?>" class="row g-2 mb-3">
        <div class="col-md-6">
          <input type="text" name="q" value="<?= e($q) ?>" class="form-control" placeholder="পোস্ট খুঁজুন...">
        </div>
        <div class="col-md-4">
          <select name="category" class="form-select">
            <option value="">সকল ক্যাটাগরি</option>
            <?php foreach (['সাধারণ আলোচনা', 'চাকরি ও সুযোগ', 'শিক্ষার্থী কর্নার', 'নাগরিক সমস্যা', 'ট্রাফিক ও যাতায়াত', 'জরুরি ঘোষণা', 'হেল্প ডেস্ক'] as $cat): ?>
              <option value="<?= e($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <button class="btn btn-dark w-100">খুঁজুন</button>
        </div>
      </form>

      <?php if ($user): ?>
      <form method="post" action="<?= e(url('/community/store')) ?>" enctype="multipart/form-data" class="border-top pt-3">
        <?= CSRF::field() ?>
        <div class="row g-2 mb-2">
          <div class="col-md-6">
            <input type="text" name="title" class="form-control" placeholder="পোস্টের শিরোনাম লিখুন" required>
          </div>
          <div class="col-md-3">
            <select name="category" class="form-select">
              <option value="সাধারণ আলোচনা">সাধারণ আলোচনা</option>
              <option value="চাকরি ও সুযোগ">চাকরি ও সুযোগ</option>
              <option value="শিক্ষার্থী কর্নার">শিক্ষার্থী কর্নার</option>
              <option value="নাগরিক সমস্যা">নাগরিক সমস্যা</option>
              <option value="ট্রাফিক ও যাতায়াত">ট্রাফিক ও যাতায়াত</option>
              <option value="জরুরি ঘোষণা">জরুরি ঘোষণা</option>
              <option value="হেল্প ডেস্ক">হেল্প ডেস্ক</option>
            </select>
          </div>
          <div class="col-md-3">
            <select name="upazila" class="form-select">
              <?php foreach (gazipur_upazilas() as $k => $v): ?>
                <option value="<?= e($k) ?>"><?= e($v['bn']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <textarea name="content" rows="3" class="form-control mb-2" placeholder="গাজীপুরবাসীর সাথে আপনার মতামত বা তথ্য শেয়ার করুন..." required></textarea>
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
          <input type="file" name="images[]" multiple accept="image/jpeg,image/png,image/webp" class="form-control form-control-sm w-auto">
          <button type="submit" class="btn ag-btn-primary">পোস্ট প্রকাশ করুন</button>
        </div>
      </form>
      <?php else: ?>
      <div class="border-top pt-3 d-flex justify-content-between align-items-center">
        <span class="small text-secondary">পোস্ট করতে বা মতামত জানাতে সদস্য অ্যাকাউন্টে লগইন করুন।</span>
        <button type="button" class="btn ag-btn-primary btn-sm" data-require-auth="true">নতুন পোস্ট লিখুন</button>
      </div>
      <?php endif; ?>
    </div>

    <?php foreach ($posts as $post): ?>
      <article class="ag-card p-4 mb-3">
        <div class="d-flex justify-content-between align-items-start mb-2">
          <div>
            <div class="ag-meta"><?= e($post['category']) ?> · <?= e($post['upazila']) ?> · <?= e($post['author_name']) ?> · <?= e(date('d M Y', strtotime($post['created_at']))) ?></div>
            <h2 class="h5 fw-bold mt-1 mb-0"><?= e($post['title']) ?></h2>
          </div>
          <?php if ($user && ((int)$user['id'] === (int)$post['user_id'] || Auth::canAccessAdmin())): ?>
            <form method="post" action="<?= e(url('/community/delete')) ?>">
              <?= CSRF::field() ?>
              <input type="hidden" name="post_id" value="<?= (int)$post['id'] ?>">
              <button class="btn btn-sm btn-outline-danger">মুছুন</button>
            </form>
          <?php endif; ?>
        </div>
        <p class="mb-3"><?= nl2br(e($post['content'])) ?></p>
        <?php if (!empty($post['images'])): ?>
          <div class="row g-2 mb-3">
            <?php foreach ($post['images'] as $img): ?>
              <div class="col-md-6"><img src="<?= e(url('/' . $img)) ?>" alt="Post image" class="img-fluid rounded border" loading="lazy"></div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
        <div class="d-flex flex-wrap gap-2 border-top pt-2">
          <?php if ($user): ?>
            <form method="post" action="<?= e(url('/community/like')) ?>" data-like-form>
              <?= CSRF::field() ?>
              <input type="hidden" name="post_id" value="<?= (int)$post['id'] ?>">
              <button type="submit" class="btn btn-sm <?= $post['liked_by_me'] ? 'btn-success' : 'btn-outline-secondary' ?>">
                পছন্দ (<span data-like-count><?= (int)$post['likes_count'] ?></span>)
              </button>
            </form>
            <form method="post" action="<?= e(url('/report')) ?>">
              <?= CSRF::field() ?>
              <input type="hidden" name="target_type" value="post">
              <input type="hidden" name="target_id" value="<?= (int)$post['id'] ?>">
              <input type="hidden" name="reason" value="অনুপযুক্ত পোস্ট">
              <button type="submit" class="btn btn-sm btn-outline-warning text-dark">রিপোর্ট</button>
            </form>
          <?php else: ?>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-require-auth="true">পছন্দ (<?= (int)$post['likes_count'] ?>)</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-require-auth="true">মন্তব্য (<?= (int)$post['comments_count'] ?>)</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-require-auth="true">রিপোর্ট</button>
          <?php endif; ?>
        </div>

        <div class="mt-3 pt-2 border-top">
          <?php foreach ($post['comments'] as $cmt): ?>
            <div class="bg-light rounded p-2 mb-2">
              <div class="small fw-semibold"><?= e($cmt['author_name']) ?></div>
              <div class="small"><?= e($cmt['body']) ?></div>
              <?php foreach ($cmt['replies'] as $rep): ?>
                <div class="ms-3 mt-1 ps-2 border-start small text-secondary">
                  <strong><?= e($rep['author_name']) ?>:</strong> <?= e($rep['body']) ?>
                </div>
              <?php endforeach; ?>
              <?php if ($user): ?>
                <form method="post" action="<?= e(url('/community/reply')) ?>" class="d-flex gap-1 mt-1">
                  <?= CSRF::field() ?>
                  <input type="hidden" name="comment_id" value="<?= (int)$cmt['id'] ?>">
                  <input type="text" name="body" class="form-control form-control-sm" placeholder="উত্তর লিখুন..." required>
                  <button class="btn btn-sm btn-outline-dark">উত্তর</button>
                </form>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>

          <?php if ($user): ?>
            <form method="post" action="<?= e(url('/community/comment')) ?>" class="d-flex gap-2 mt-2">
              <?= CSRF::field() ?>
              <input type="hidden" name="post_id" value="<?= (int)$post['id'] ?>">
              <input type="text" name="body" class="form-control form-control-sm" placeholder="মন্তব্য লিখুন..." required>
              <button class="btn btn-sm ag-btn-primary">মন্তব্য</button>
            </form>
          <?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>

    <nav class="mt-4">
      <ul class="pagination">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
          <li class="page-item <?= $i === $page ? 'active' : '' ?>">
            <a class="page-link" href="?page=<?= $i ?>&q=<?= urlencode($q) ?>&category=<?= urlencode($category) ?>"><?= $i ?></a>
          </li>
        <?php endfor; ?>
      </ul>
    </nav>
  </div>

  <div class="col-lg-4">
    <div class="ag-card p-3">
      <h3 class="h6 fw-bold mb-2">কমিউনিটি নির্দেশিকা</h3>
      <p class="small text-secondary mb-0">গাজীপুর জেলার নাগরিকদের সম্মান ও নিরাপত্তা রক্ষায় যেকোনো প্রতারণামূলক বা নিষিদ্ধ পোস্ট স্বয়ংক্রিয়ভাবে পর্যালোচনা কিউতে পাঠানো হয়।</p>
    </div>
  </div>
</div>
