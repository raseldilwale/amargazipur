<?php
$allCategories = account_categories();
$isEn = I18n::locale() === 'en';
?>
<div class="ag-card p-4 mb-4">
  <form method="get" action="<?= e(url('/admin/users')) ?>" class="row g-2 align-items-center">
    <div class="col-md-6">
      <input type="text" name="q" value="<?= e($q) ?>" class="form-control" placeholder="<?= e(tr('নাম, ইউজারনেম অথবা ইমেইল দিয়ে খুঁজুন...', 'Search by name, username, or email...')) ?>">
    </div>
    <div class="col-md-3">
      <select name="filter" class="form-select">
        <option value="" <?= empty($filter) ? 'selected' : '' ?>><?= e(tr('সকল ব্যবহারকারী', 'All Users')) ?></option>
        <option value="pending" <?= ($filter ?? '') === 'pending' ? 'selected' : '' ?>><?= e(tr('অপেক্ষমাণ ক্যাটাগরি রয়েছে', 'Pending Category Requests')) ?></option>
      </select>
    </div>
    <div class="col-md-3 d-flex gap-2">
      <button class="btn btn-dark flex-grow-1"><?= e(tr('খুঁজুন', 'Filter')) ?></button>
      <?php if ($q !== '' || !empty($filter)): ?>
        <a href="<?= e(url('/admin/users')) ?>" class="btn btn-outline-secondary"><?= e(tr('রিসেট', 'Reset')) ?></a>
      <?php endif; ?>
    </div>
  </form>
</div>

<div class="ag-card p-4">
  <div class="table-responsive" style="overflow: visible;">
    <table class="table align-middle">
      <thead>
        <tr>
          <th><?= e(tr('নাম ও ইমেইল', 'Name & Contact')) ?></th>
          <th><?= e(tr('রোল ও অবস্থা', 'Role & Status')) ?></th>
          <th><?= e(tr('অ্যাপ্রুভাল লিস্ট (ক্যাটাগরি)', 'Approval List (Categories)')) ?></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($users)): ?>
          <tr><td colspan="3" class="text-center text-secondary py-4"><?= e(tr('কোনো ব্যবহারকারী পাওয়া যায়নি।', 'No matching users found.')) ?></td></tr>
        <?php else: ?>
          <?php foreach ($users as $u): ?>
            <?php
            $catStatusMap = [];
            $approvedCount = 0;
            $pendingCount = 0;
            foreach ($u['categories'] as $c) {
                $catStatusMap[$c['category_slug']] = $c['status'];
                if ($c['status'] === 'approved') {
                    $approvedCount++;
                } elseif ($c['status'] === 'pending') {
                    $pendingCount++;
                }
            }
            $totalCats = count($allCategories);
            ?>
            <tr>
              <td>
                <div class="fw-bold">
                  <?= e($u['full_name']) ?>
                  <?php if (!empty($u['username'])): ?>
                    <span class="small text-muted fw-normal">(@<?= e($u['username']) ?>)</span>
                  <?php endif; ?>
                </div>
                <div class="small text-secondary"><?= e($u['email']) ?> · <?= e($u['upazila']) ?></div>
              </td>
              <td>
                <form method="post" action="<?= e(url('/admin/users/status')) ?>" class="d-flex gap-1">
                  <?= CSRF::field() ?>
                  <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                  <select name="role_id" class="form-select form-select-sm">
                    <?php foreach ($roles as $r): ?>
                      <option value="<?= (int)$r['id'] ?>" <?= (int)$u['role_id'] === (int)$r['id'] ? 'selected' : '' ?>><?= e($r['name']) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <select name="status" class="form-select form-select-sm">
                    <?php foreach (['active','pending','suspended','rejected'] as $st): ?>
                      <option value="<?= $st ?>" <?= $u['status'] === $st ? 'selected' : '' ?>><?= $st ?></option>
                    <?php endforeach; ?>
                  </select>
                  <button class="btn btn-sm btn-dark"><?= e(tr('সংরক্ষণ', 'Save')) ?></button>
                </form>
              </td>
              <td>
                <div class="dropdown">
                  <button
                    class="btn btn-sm dropdown-toggle <?= $pendingCount > 0 ? 'btn-outline-warning text-dark fw-semibold' : 'btn-outline-secondary' ?>"
                    type="button"
                    data-bs-toggle="dropdown"
                    data-bs-auto-close="outside"
                    aria-expanded="false"
                  >
                    <?= e(tr('অ্যাপ্রুভাল লিস্ট', 'Approval List')) ?>
                    (<?= $approvedCount ?>/<?= $totalCats ?> ✓<?= $pendingCount > 0 ? ' · ' . $pendingCount . ' ' . tr('অপেক্ষমাণ', 'pending') : '' ?>)
                  </button>
                  <div class="dropdown-menu dropdown-menu-end p-3 shadow-sm" style="min-width: 310px;">
                    <form method="post" action="<?= e(url('/admin/users/categories-bulk')) ?>">
                      <?= CSRF::field() ?>
                      <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                      <div class="small fw-bold mb-2 pb-1 border-bottom">
                        <?= e(tr('টিক দিয়ে ক্যাটাগরি অনুমোদন করুন:', 'Check categories to approve:')) ?>
                      </div>
                      <div class="d-flex flex-column gap-1 mb-3">
                        <?php foreach ($allCategories as $slug => $labels): ?>
                          <?php
                          $currentState = $catStatusMap[$slug] ?? null;
                          $isChecked = $currentState === 'approved';
                          ?>
                          <label class="d-flex align-items-center justify-content-between gap-2 py-1 px-2 rounded <?= $isChecked ? 'bg-success-subtle' : ($currentState === 'pending' ? 'bg-warning-subtle' : '') ?>" style="cursor:pointer;">
                            <span class="d-flex align-items-center gap-2 small">
                              <input
                                type="checkbox"
                                name="approved_categories[]"
                                value="<?= e($slug) ?>"
                                class="form-check-input mt-0"
                                <?= $isChecked ? 'checked' : '' ?>
                              >
                              <span><?= e($isEn ? $labels['en'] : $labels['bn']) ?></span>
                            </span>
                            <span class="small fw-semibold <?= $isChecked ? 'text-success' : ($currentState === 'pending' ? 'text-warning-emphasis' : ($currentState === 'rejected' ? 'text-danger' : 'text-muted')) ?>">
                              <?php if ($isChecked): ?>
                                ✓ <?= e(tr('অনুমোদিত', 'Approved')) ?>
                              <?php elseif ($currentState === 'pending'): ?>
                                <?= e(tr('অপেক্ষমাণ', 'Pending')) ?>
                              <?php elseif ($currentState === 'rejected'): ?>
                                <?= e(tr('বাতিল', 'Rejected')) ?>
                              <?php else: ?>
                                —
                              <?php endif; ?>
                            </span>
                          </label>
                        <?php endforeach; ?>
                      </div>
                      <button type="submit" class="btn btn-sm ag-btn-primary w-100">
                        <?= e(tr('সংরক্ষণ করুন', 'Save Approval')) ?>
                      </button>
                    </form>
                  </div>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
