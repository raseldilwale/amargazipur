<?php
$selectedType = $defaultType ?? 'post';
$isEn = I18n::locale() === 'en';
?>
<div class="ag-card p-4" style="max-width: 880px;">
  <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
    <div>
      <h2 class="h5 fw-bold mb-0"><?= e(tr('নতুন কনটেন্ট প্রকাশ করুন (অ্যাডমিন)', 'Create & Publish Content (Admin)')) ?></h2>
      <div class="small text-secondary"><?= e(tr('অ্যাডমিন সরাসরি পোস্ট, সংবাদ, চাকরি, শিক্ষার্থী নোটিশ, মার্কেটপ্লেস, বাসা ভাড়া, রক্তদান, শিক্ষা প্রতিষ্ঠান বা বিজ্ঞাপন প্রকাশ করতে পারবেন।', 'Publish directly to any section of AmarGazipur including photo uploads.')) ?></div>
    </div>
    <a href="<?= e(url('/admin/content')) ?>" class="btn btn-sm btn-outline-secondary"><?= e(tr('সকল কনটেন্ট দেখুন', 'Manage Content')) ?></a>
  </div>

  <form method="post" action="<?= e(url('/admin/create')) ?>" enctype="multipart/form-data" class="row g-3">
    <?= CSRF::field() ?>

    <div class="col-md-6">
      <label class="form-label fw-semibold"><?= e(tr('কোন সেকশনে প্রকাশ করবেন? *', 'Publish Category / Section *')) ?></label>
      <select name="content_type" id="agAdminContentType" class="form-select" required>
        <option value="post" <?= $selectedType === 'post' ? 'selected' : '' ?>><?= e(tr('কমিউনিটি পোস্ট / চাকরি / শিক্ষার্থী', 'Community Post / Jobs / Student')) ?></option>
        <option value="news" <?= $selectedType === 'news' ? 'selected' : '' ?>><?= e(tr('স্থানীয় সংবাদ (Local News)', 'Local News Article')) ?></option>
        <option value="marketplace" <?= $selectedType === 'marketplace' ? 'selected' : '' ?>><?= e(tr('মার্কেটপ্লেস (কেনাবেচা)', 'Marketplace Listing')) ?></option>
        <option value="rental" <?= $selectedType === 'rental' ? 'selected' : '' ?>><?= e(tr('বাসা ভাড়া / টু-লেট', 'House Rent / To-Let')) ?></option>
        <option value="job" <?= $selectedType === 'job' ? 'selected' : '' ?>><?= e(tr('চাকরি ও নিয়োগ বিজ্ঞপ্তি (Jobs)', 'Job Circular / Hiring')) ?></option>
        <option value="blood_request" <?= $selectedType === 'blood_request' ? 'selected' : '' ?>><?= e(tr('জরুরি রক্তের অনুরোধ', 'Urgent Blood Request')) ?></option>
        <option value="blood_donor" <?= $selectedType === 'blood_donor' ? 'selected' : '' ?>><?= e(tr('রক্তদাতা যুক্ত করুন', 'Add Blood Donor')) ?></option>
        <option value="education" <?= $selectedType === 'education' ? 'selected' : '' ?>><?= e(tr('শিক্ষা প্রতিষ্ঠান ও ভর্তি তথ্য', 'Education Institution')) ?></option>
        <option value="business" <?= $selectedType === 'business' ? 'selected' : '' ?>><?= e(tr('ব্যবসা প্রতিষ্ঠান ডিরেক্টরি', 'Business Directory')) ?></option>
        <option value="advertisement" <?= $selectedType === 'advertisement' ? 'selected' : '' ?>><?= e(tr('বিজ্ঞাপন (Advertisement)', 'Advertisement Banner')) ?></option>
      </select>
    </div>

    <div class="col-md-6">
      <label class="form-label fw-semibold"><?= e(tr('উপজেলা / এলাকা', 'Upazila / Area')) ?></label>
      <select name="upazila" class="form-select">
        <?php foreach (gazipur_upazilas() as $key => $labels): ?>
          <option value="<?= e($key) ?>"><?= e($isEn ? $labels['en'] : $labels['bn']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col-12">
      <label class="form-label fw-semibold"><?= e(tr('শিরোনাম / নাম *', 'Title / Name *')) ?></label>
      <input type="text" name="title" class="form-control" placeholder="<?= e(tr('এখানে শিরোনাম বা নাম লিখুন...', 'Enter title or name...')) ?>" required>
    </div>

    <!-- Community Post Sub-category -->
    <div class="col-md-6 ag-type-field ag-type-post">
      <label class="form-label"><?= e(tr('পোস্টের ধরন', 'Post Category')) ?></label>
      <select name="post_category" class="form-select">
        <option value="সাধারণ আলোচনা"><?= e(tr('সাধারণ আলোচনা', 'General Discussion')) ?></option>
        <option value="চাকরি ও সুযোগ"><?= e(tr('চাকরি ও সুযোগ (Job Circular)', 'Job Circular / Recruitment')) ?></option>
        <option value="শিক্ষার্থী কর্নার"><?= e(tr('শিক্ষার্থী কর্নার (Student Notice)', 'Student Corner / Notice')) ?></option>
        <option value="নাগরিক সমস্যা"><?= e(tr('নাগরিক সমস্যা', 'Civic Issue')) ?></option>
        <option value="জরুরি ঘোষণা"><?= e(tr('জরুরি ঘোষণা', 'Official Announcement')) ?></option>
      </select>
    </div>

    <!-- News fields -->
    <div class="col-md-6 ag-type-field ag-type-news d-none">
      <label class="form-label"><?= e(tr('সংবাদ বিভাগ', 'News Category')) ?></label>
      <select name="news_category_id" class="form-select">
        <?php foreach ($newsCategories as $nc): ?>
          <option value="<?= (int)$nc['id'] ?>"><?= e($isEn ? $nc['name_en'] : $nc['name_bn']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-6 ag-type-field ag-type-news d-none d-flex align-items-end">
      <label class="form-check mb-2">
        <input type="checkbox" name="is_featured" value="1" class="form-check-input">
        <span class="form-check-label small fw-semibold"><?= e(tr('হোমপেজে ফিচার্ড সংবাদ হিসেবে দেখান', 'Show as Featured News')) ?></span>
      </label>
    </div>
    <div class="col-12 ag-type-field ag-type-news d-none">
      <label class="form-label"><?= e(tr('সংক্ষিপ্ত সারাংশ', 'News Summary')) ?></label>
      <input type="text" name="summary" class="form-control" placeholder="<?= e(tr('১-২ লাইনে সংবাদের মূল কথা', 'Brief 1-2 line summary')) ?>">
    </div>

    <!-- Marketplace fields -->
    <div class="col-md-4 ag-type-field ag-type-marketplace d-none">
      <label class="form-label"><?= e(tr('পণ্যের ক্যাটাগরি', 'Product Category')) ?></label>
      <select name="market_category_id" class="form-select">
        <?php foreach ($marketCategories as $mc): ?>
          <option value="<?= (int)$mc['id'] ?>"><?= e($isEn ? $mc['name_en'] : $mc['name_bn']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-4 ag-type-field ag-type-marketplace d-none">
      <label class="form-label"><?= e(tr('মূল্য (৳)', 'Price (BDT)')) ?></label>
      <input type="number" name="price" min="0" value="0" class="form-control">
    </div>
    <div class="col-md-4 ag-type-field ag-type-marketplace d-none">
      <label class="form-label"><?= e(tr('অবস্থা', 'Condition')) ?></label>
      <select name="item_condition" class="form-select">
        <option value="new"><?= e(tr('নতুন', 'New')) ?></option>
        <option value="like_new"><?= e(tr('নতুনের মতো', 'Like New')) ?></option>
        <option value="used"><?= e(tr('ব্যবহৃত', 'Used')) ?></option>
      </select>
    </div>

    <!-- Rental fields -->
    <div class="col-md-4 ag-type-field ag-type-rental d-none">
      <label class="form-label"><?= e(tr('বাসার ধরন', 'Property Type')) ?></label>
      <select name="rental_category" class="form-select">
        <option value="family_apartment"><?= e(tr('ফ্যামিলি ফ্ল্যাট', 'Family Apartment')) ?></option>
        <option value="bachelor_room"><?= e(tr('ব্যাচেলর / সিঙ্গেল রুম', 'Bachelor Room')) ?></option>
        <option value="sublet"><?= e(tr('সাবলেট', 'Sublet')) ?></option>
        <option value="hostel"><?= e(tr('ছাত্রাবাস / হোস্টেল', 'Student Hostel')) ?></option>
        <option value="mess"><?= e(tr('মেস', 'Mess')) ?></option>
        <option value="shop"><?= e(tr('দোকান', 'Shop')) ?></option>
        <option value="office"><?= e(tr('অফিস / কমার্শিয়াল', 'Office / Commercial')) ?></option>
      </select>
    </div>
    <div class="col-md-4 ag-type-field ag-type-rental d-none">
      <label class="form-label"><?= e(tr('মাসিক ভাড়া (৳)', 'Monthly Rent (BDT)')) ?></label>
      <input type="number" name="monthly_rent" min="0" value="12000" class="form-control">
    </div>
    <div class="col-md-4 ag-type-field ag-type-rental d-none">
      <label class="form-label"><?= e(tr('অগ্রিম (৳)', 'Advance (BDT)')) ?></label>
      <input type="number" name="advance_amount" min="0" value="0" class="form-control">
    </div>
    <div class="col-md-3 ag-type-field ag-type-rental d-none">
      <label class="form-label"><?= e(tr('বেডরুম', 'Bedrooms')) ?></label>
      <input type="number" name="bedrooms" min="1" value="2" class="form-control">
    </div>
    <div class="col-md-3 ag-type-field ag-type-rental d-none">
      <label class="form-label"><?= e(tr('বাথরুম', 'Bathrooms')) ?></label>
      <input type="number" name="bathrooms" min="1" value="2" class="form-control">
    </div>
    <div class="col-md-6 ag-type-field ag-type-rental d-none">
      <label class="form-label"><?= e(tr('সুবিধাসমূহ / তলা', 'Facilities & Floor')) ?></label>
      <input type="text" name="facilities" class="form-control" placeholder="<?= e(tr('যেমন: ৩য় তলা, গ্যাস, লিফট, সিসিটিভি', 'e.g. 3rd floor, gas, lift')) ?>">
    </div>

    <!-- Job fields -->
    <div class="col-md-6 ag-type-field ag-type-job d-none">
      <label class="form-label"><?= e(tr('কোম্পানির নাম *', 'Company Name *')) ?></label>
      <input type="text" name="company_name" class="form-control" placeholder="<?= e(tr('যেমন: গাজীপুর টেক লিঃ', 'e.g. Gazipur Tech Ltd.')) ?>">
    </div>
    <div class="col-md-6 ag-type-field ag-type-job d-none">
      <label class="form-label"><?= e(tr('বেতন (Salary) *', 'Salary *')) ?></label>
      <input type="text" name="salary" class="form-control" placeholder="<?= e(tr('যেমন: ২০,০০০ - ৩০,০০০ টাকা', 'e.g. 20,000 - 30,000 BDT')) ?>">
    </div>
    <div class="col-md-4 ag-type-field ag-type-job d-none">
      <label class="form-label"><?= e(tr('চাকরির ধরন', 'Job Type')) ?></label>
      <select name="job_type" class="form-select">
        <option value="full_time"><?= e(tr('ফুল-টাইম', 'Full-Time')) ?></option>
        <option value="part_time"><?= e(tr('পার্ট-টাইম', 'Part-Time')) ?></option>
        <option value="contract"><?= e(tr('চুক্তিভিত্তিক', 'Contract')) ?></option>
        <option value="internship"><?= e(tr('ইন্টার্নশিপ', 'Internship')) ?></option>
        <option value="remote"><?= e(tr('রিমোট', 'Remote')) ?></option>
      </select>
    </div>
    <div class="col-md-4 ag-type-field ag-type-job d-none">
      <label class="form-label"><?= e(tr('যোগাযোগ নম্বর বা ইমেইল *', 'Contact Phone or Email *')) ?></label>
      <input type="text" name="contact_info" class="form-control" placeholder="017XXXXXXXX / hr@company.com">
    </div>
    <div class="col-md-4 ag-type-field ag-type-job d-none">
      <label class="form-label"><?= e(tr('আবেদনের শেষ তারিখ', 'Deadline')) ?></label>
      <input type="date" name="deadline" class="form-control">
    </div>

    <!-- Blood fields -->
    <div class="col-md-4 ag-type-field ag-type-blood_request ag-type-blood_donor d-none">
      <label class="form-label"><?= e(tr('রক্তের গ্রুপ', 'Blood Group')) ?></label>
      <select name="blood_group" class="form-select">
        <?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bg): ?>
          <option value="<?= $bg ?>"><?= $bg ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-4 ag-type-field ag-type-blood_request d-none">
      <label class="form-label"><?= e(tr('কত ব্যাগ প্রয়োজন', 'Units Needed')) ?></label>
      <input type="number" name="units_needed" min="1" value="1" class="form-control">
    </div>
    <div class="col-md-4 ag-type-field ag-type-blood_request d-none">
      <label class="form-label"><?= e(tr('প্রয়োজনের তারিখ', 'Needed Date')) ?></label>
      <input type="date" name="needed_date" value="<?= date('Y-m-d') ?>" class="form-control">
    </div>

    <!-- Education fields -->
    <div class="col-md-6 ag-type-field ag-type-education d-none">
      <label class="form-label"><?= e(tr('প্রতিষ্ঠানের ধরন', 'Institution Type')) ?></label>
      <select name="edu_type" class="form-select">
        <option value="school"><?= e(tr('স্কুল', 'School')) ?></option>
        <option value="college"><?= e(tr('কলেজ', 'College')) ?></option>
        <option value="university"><?= e(tr('বিশ্ববিদ্যালয়', 'University')) ?></option>
        <option value="madrasa"><?= e(tr('মাদ্রাসা', 'Madrasa')) ?></option>
        <option value="coaching"><?= e(tr('কোচিং / ভর্তি সহায়তা', 'Coaching')) ?></option>
        <option value="technical"><?= e(tr('কারিগরি / ট্রেনিং', 'Technical / Training')) ?></option>
      </select>
    </div>
    <div class="col-md-6 ag-type-field ag-type-education d-none">
      <label class="form-label"><?= e(tr('কোর্স / বিভাগসমূহ', 'Courses / Departments')) ?></label>
      <input type="text" name="courses" class="form-control" placeholder="Science, Commerce, CSE, Diploma...">
    </div>

    <!-- Business fields -->
    <div class="col-md-6 ag-type-field ag-type-business d-none">
      <label class="form-label"><?= e(tr('ব্যবসার ক্যাটাগরি', 'Business Category')) ?></label>
      <select name="business_category" class="form-select">
        <option value="restaurants">Restaurants</option>
        <option value="shopping">Shopping</option>
        <option value="hospitals">Hospitals</option>
        <option value="clinics">Clinics</option>
        <option value="technology">Technology</option>
        <option value="transport">Transport</option>
        <option value="services">Services</option>
        <option value="other">Other</option>
      </select>
    </div>
    <div class="col-md-6 ag-type-field ag-type-business d-none">
      <label class="form-label"><?= e(tr('খোলা থাকার সময়', 'Opening Hours')) ?></label>
      <input type="text" name="hours" class="form-control" placeholder="9:00 AM - 10:00 PM">
    </div>

    <!-- Advertisement fields -->
    <div class="col-md-4 ag-type-field ag-type-advertisement d-none">
      <label class="form-label"><?= e(tr('বিজ্ঞাপনের স্থান', 'Placement')) ?></label>
      <select name="placement" class="form-select">
        <option value="home_banner"><?= e(tr('হোম ব্যানার', 'Home Banner')) ?></option>
        <option value="sidebar"><?= e(tr('সাইডবার', 'Sidebar')) ?></option>
        <option value="news_inline"><?= e(tr('সংবাদ ইনলাইন', 'News Inline')) ?></option>
        <option value="marketplace_top"><?= e(tr('মার্কেটপ্লেস শীর্ষ', 'Marketplace Top')) ?></option>
      </select>
    </div>
    <div class="col-md-4 ag-type-field ag-type-advertisement d-none">
      <label class="form-label"><?= e(tr('শুরুর তারিখ', 'Start Date')) ?></label>
      <input type="date" name="start_date" value="<?= date('Y-m-d') ?>" class="form-control">
    </div>
    <div class="col-md-4 ag-type-field ag-type-advertisement d-none">
      <label class="form-label"><?= e(tr('শেষ তারিখ', 'End Date')) ?></label>
      <input type="date" name="end_date" value="<?= date('Y-m-d', strtotime('+30 days')) ?>" class="form-control">
    </div>
    <div class="col-12 ag-type-field ag-type-advertisement d-none">
      <label class="form-label"><?= e(tr('টার্গেট লিংক URL', 'Target URL')) ?></label>
      <input type="url" name="target_url" class="form-control" placeholder="https://...">
    </div>

    <!-- Location / Hospital / Address & Phone (shared across marketplace, rental, job, blood, education, business) -->
    <div class="col-md-6 ag-type-field ag-type-marketplace ag-type-rental ag-type-job ag-type-blood_request ag-type-blood_donor ag-type-education ag-type-business d-none">
      <label class="form-label"><?= e(tr('ঠিকানা / এলাকা / কর্মস্থল', 'Address / Area / Location')) ?></label>
      <input type="text" name="location" class="form-control" placeholder="<?= e(tr('যেমন: জয়দেবপুর, চান্দনা চৌরাস্তা বা হাসপাতালের নাম', 'e.g. Chandna Chowrasta or Hospital name')) ?>">
    </div>
    <div class="col-md-6 ag-type-field ag-type-marketplace ag-type-rental ag-type-blood_request ag-type-blood_donor ag-type-education ag-type-business d-none">
      <label class="form-label"><?= e(tr('যোগাযোগের ফোন নম্বর', 'Contact Phone')) ?></label>
      <input type="text" name="contact_phone" class="form-control" placeholder="017XXXXXXXX">
    </div>

    <!-- Details Text -->
    <div class="col-12">
      <label class="form-label fw-semibold"><?= e(tr('বিস্তারিত বিবরণ / টেক্সট', 'Details / Description Text')) ?></label>
      <textarea name="content" rows="5" class="form-control" placeholder="<?= e(tr('বিস্তারিত তথ্য এখানে লিখুন...', 'Write full details here...')) ?>"></textarea>
    </div>

    <!-- Image Upload -->
    <div class="col-12">
      <label class="form-label fw-semibold"><?= e(tr('ছবি আপলোড করুন (ঐচ্ছিক — JPG, PNG, WEBP)', 'Upload Photo (Optional — JPG, PNG, WEBP)')) ?></label>
      <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp">
    </div>

    <div class="col-12 pt-2">
      <button type="submit" class="btn ag-btn-primary px-4">
        + <?= e(tr('এখনই প্রকাশ করুন', 'Publish Now')) ?>
      </button>
    </div>
  </form>
</div>

<script>
(function () {
  var sel = document.getElementById('agAdminContentType');
  if (!sel) return;
  function syncFields() {
    var val = sel.value;
    document.querySelectorAll('.ag-type-field').forEach(function (el) {
      if (el.classList.contains('ag-type-' + val)) {
        el.classList.remove('d-none');
      } else {
        el.classList.add('d-none');
      }
    });
  }
  sel.addEventListener('change', syncFields);
  syncFields();
})();
</script>
