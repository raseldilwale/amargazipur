<div class="ag-card p-4">
  <h2 class="h6 fw-bold mb-3"><?= e(tr('ওয়েবসাইট ও SEO সেটিংস', 'Website & SEO Settings')) ?></h2>
  <form method="post" action="<?= e(url('/admin/settings')) ?>" class="row g-3">
    <?= CSRF::field() ?>
    <div class="col-md-6"><label class="form-label small"><?= e(tr('সাইটের নাম', 'Site Name')) ?></label><input type="text" name="site_name" value="<?= e($settings['site_name'] ?? 'AmarGazipur') ?>" class="form-control"></div>
    <div class="col-md-6"><label class="form-label small"><?= e(tr('যোগাযোগ ইমেইল', 'Contact Email')) ?></label><input type="email" name="contact_email" value="<?= e($settings['contact_email'] ?? '') ?>" class="form-control"></div>
    <div class="col-md-6"><label class="form-label small"><?= e(tr('বাংলা ট্যাগলাইন', 'Bangla Tagline')) ?></label><input type="text" name="site_tagline_bn" value="<?= e($settings['site_tagline_bn'] ?? '') ?>" class="form-control"></div>
    <div class="col-md-6"><label class="form-label small"><?= e(tr('ইংরেজি ট্যাগলাইন', 'English Tagline')) ?></label><input type="text" name="site_tagline_en" value="<?= e($settings['site_tagline_en'] ?? '') ?>" class="form-control"></div>
    <div class="col-12"><label class="form-label small">SEO Meta Title</label><input type="text" name="seo_meta_title" value="<?= e($settings['seo_meta_title'] ?? '') ?>" class="form-control"></div>
    <div class="col-12"><label class="form-label small">SEO Meta Description</label><textarea name="seo_meta_description" rows="2" class="form-control"><?= e($settings['seo_meta_description'] ?? '') ?></textarea></div>
    <div class="col-12"><label class="form-label small"><?= e(tr('ফুটার টেক্সট', 'Footer Text')) ?></label><input type="text" name="footer_text" value="<?= e($settings['footer_text'] ?? '') ?>" class="form-control"></div>
    <div class="col-12"><button class="btn btn-dark"><?= e(tr('সেটিংস সংরক্ষণ করুন', 'Save Settings')) ?></button></div>
  </form>
</div>
