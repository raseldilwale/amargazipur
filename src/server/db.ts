import Database from 'better-sqlite3';
import crypto from 'node:crypto';
import fs from 'node:fs';
import path from 'node:path';

const dataDir = path.resolve(process.cwd(), 'data');
if (!fs.existsSync(dataDir)) {
  fs.mkdirSync(dataDir, { recursive: true });
}

export const db = new Database(path.join(dataDir, 'amargazipur.db'));
db.pragma('journal_mode = WAL');
db.pragma('foreign_keys = ON');

export function hashPassword(password: string): string {
  const salt = crypto.randomBytes(16).toString('hex');
  const derived = crypto.scryptSync(password, salt, 64).toString('hex');
  return `${salt}:${derived}`;
}

export function verifyPassword(password: string, stored: string): boolean {
  const [salt, keyHex] = stored.split(':');
  if (!salt || !keyHex) return false;
  const keyBuf = Buffer.from(keyHex, 'hex');
  const derivedBuf = crypto.scryptSync(password, salt, 64);
  if (keyBuf.length !== derivedBuf.length) return false;
  return crypto.timingSafeEqual(keyBuf, derivedBuf);
}

export function initDatabase(): void {
  db.exec(`
    CREATE TABLE IF NOT EXISTS users (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      role TEXT NOT NULL DEFAULT 'member',
      full_name TEXT NOT NULL,
      email TEXT NOT NULL UNIQUE,
      password_hash TEXT NOT NULL,
      phone TEXT DEFAULT '',
      upazila TEXT NOT NULL DEFAULT 'Gazipur Sadar',
      bio TEXT DEFAULT '',
      status TEXT NOT NULL DEFAULT 'active',
      email_verified INTEGER NOT NULL DEFAULT 1,
      created_at TEXT NOT NULL DEFAULT (datetime('now'))
    );

    CREATE TABLE IF NOT EXISTS user_categories (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      user_id INTEGER NOT NULL,
      category_slug TEXT NOT NULL,
      status TEXT NOT NULL DEFAULT 'pending',
      reviewed_by INTEGER DEFAULT NULL,
      reviewed_at TEXT DEFAULT NULL,
      created_at TEXT NOT NULL DEFAULT (datetime('now')),
      UNIQUE(user_id, category_slug),
      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS posts (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      user_id INTEGER NOT NULL,
      category TEXT NOT NULL DEFAULT 'সাধারণ আলোচনা',
      upazila TEXT NOT NULL DEFAULT 'Gazipur Sadar',
      title TEXT NOT NULL,
      content TEXT NOT NULL,
      image_path TEXT DEFAULT NULL,
      status TEXT NOT NULL DEFAULT 'published',
      moderation_reason TEXT DEFAULT NULL,
      created_at TEXT NOT NULL DEFAULT (datetime('now')),
      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS likes (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      post_id INTEGER NOT NULL,
      user_id INTEGER NOT NULL,
      created_at TEXT NOT NULL DEFAULT (datetime('now')),
      UNIQUE(post_id, user_id),
      FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS comments (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      post_id INTEGER NOT NULL,
      user_id INTEGER NOT NULL,
      body TEXT NOT NULL,
      status TEXT NOT NULL DEFAULT 'published',
      created_at TEXT NOT NULL DEFAULT (datetime('now')),
      FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS comment_replies (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      comment_id INTEGER NOT NULL,
      user_id INTEGER NOT NULL,
      body TEXT NOT NULL,
      created_at TEXT NOT NULL DEFAULT (datetime('now')),
      FOREIGN KEY (comment_id) REFERENCES comments(id) ON DELETE CASCADE,
      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS news_categories (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      slug TEXT NOT NULL UNIQUE,
      name_bn TEXT NOT NULL,
      name_en TEXT NOT NULL
    );

    CREATE TABLE IF NOT EXISTS news (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      category_id INTEGER NOT NULL,
      author_id INTEGER NOT NULL,
      slug TEXT NOT NULL UNIQUE,
      title TEXT NOT NULL,
      summary TEXT NOT NULL,
      content TEXT NOT NULL,
      featured_image TEXT DEFAULT NULL,
      upazila TEXT NOT NULL DEFAULT 'Gazipur Sadar',
      is_featured INTEGER NOT NULL DEFAULT 0,
      views_count INTEGER NOT NULL DEFAULT 0,
      status TEXT NOT NULL DEFAULT 'pending',
      approved_by INTEGER DEFAULT NULL,
      created_at TEXT NOT NULL DEFAULT (datetime('now')),
      FOREIGN KEY (category_id) REFERENCES news_categories(id),
      FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS marketplace_categories (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      slug TEXT NOT NULL UNIQUE,
      name_bn TEXT NOT NULL,
      name_en TEXT NOT NULL
    );

    CREATE TABLE IF NOT EXISTS marketplace_listings (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      user_id INTEGER NOT NULL,
      category_id INTEGER NOT NULL,
      title TEXT NOT NULL,
      description TEXT NOT NULL,
      price REAL NOT NULL DEFAULT 0,
      item_condition TEXT NOT NULL DEFAULT 'used',
      location TEXT NOT NULL,
      upazila TEXT NOT NULL DEFAULT 'Gazipur Sadar',
      contact_phone TEXT NOT NULL,
      image_path TEXT DEFAULT NULL,
      status TEXT NOT NULL DEFAULT 'published',
      created_at TEXT NOT NULL DEFAULT (datetime('now')),
      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
      FOREIGN KEY (category_id) REFERENCES marketplace_categories(id)
    );

    CREATE TABLE IF NOT EXISTS rental_listings (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      user_id INTEGER NOT NULL,
      category TEXT NOT NULL DEFAULT 'family_apartment',
      title TEXT NOT NULL,
      description TEXT NOT NULL,
      monthly_rent REAL NOT NULL,
      advance_amount REAL NOT NULL DEFAULT 0,
      location TEXT NOT NULL,
      upazila TEXT NOT NULL DEFAULT 'Gazipur Sadar',
      bedrooms INTEGER NOT NULL DEFAULT 2,
      bathrooms INTEGER NOT NULL DEFAULT 2,
      floor_number TEXT DEFAULT '3rd Floor',
      facilities TEXT DEFAULT '',
      available_from TEXT DEFAULT '',
      contact_phone TEXT NOT NULL,
      hide_contact_public INTEGER NOT NULL DEFAULT 1,
      image_path TEXT DEFAULT NULL,
      status TEXT NOT NULL DEFAULT 'published',
      created_at TEXT NOT NULL DEFAULT (datetime('now')),
      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS job_listings (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      user_id INTEGER NOT NULL,
      company_name TEXT NOT NULL,
      job_title TEXT NOT NULL,
      job_type TEXT NOT NULL DEFAULT 'full_time',
      salary TEXT NOT NULL,
      location TEXT NOT NULL,
      upazila TEXT NOT NULL DEFAULT 'Gazipur Sadar',
      contact_info TEXT NOT NULL,
      deadline TEXT DEFAULT '',
      description TEXT NOT NULL,
      image_path TEXT DEFAULT NULL,
      status TEXT NOT NULL DEFAULT 'published',
      created_at TEXT NOT NULL DEFAULT (datetime('now')),
      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS education_institutions (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      user_id INTEGER DEFAULT NULL,
      type TEXT NOT NULL DEFAULT 'university',
      name TEXT NOT NULL,
      upazila TEXT NOT NULL DEFAULT 'Gazipur Sadar',
      address TEXT NOT NULL,
      phone TEXT DEFAULT '',
      website TEXT DEFAULT '',
      courses TEXT DEFAULT '',
      description TEXT NOT NULL,
      cover_image TEXT DEFAULT NULL,
      status TEXT NOT NULL DEFAULT 'published',
      created_at TEXT NOT NULL DEFAULT (datetime('now'))
    );

    CREATE TABLE IF NOT EXISTS blood_donors (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      user_id INTEGER NOT NULL,
      donor_name TEXT NOT NULL,
      blood_group TEXT NOT NULL,
      area TEXT NOT NULL,
      upazila TEXT NOT NULL,
      phone TEXT NOT NULL,
      contact_preference TEXT NOT NULL DEFAULT 'call',
      consent_public_contact INTEGER NOT NULL DEFAULT 0,
      is_available INTEGER NOT NULL DEFAULT 1,
      last_donation_date TEXT DEFAULT '',
      created_at TEXT NOT NULL DEFAULT (datetime('now')),
      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS blood_requests (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      user_id INTEGER NOT NULL,
      patient_name TEXT NOT NULL,
      blood_group TEXT NOT NULL,
      units_needed INTEGER NOT NULL DEFAULT 1,
      hospital_name TEXT NOT NULL,
      upazila TEXT NOT NULL,
      needed_date TEXT NOT NULL,
      contact_phone TEXT NOT NULL,
      notes TEXT DEFAULT '',
      status TEXT NOT NULL DEFAULT 'open',
      created_at TEXT NOT NULL DEFAULT (datetime('now')),
      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS businesses (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      user_id INTEGER DEFAULT NULL,
      category TEXT NOT NULL DEFAULT 'services',
      name TEXT NOT NULL,
      address TEXT NOT NULL,
      upazila TEXT NOT NULL DEFAULT 'Gazipur Sadar',
      phone TEXT NOT NULL,
      hours TEXT DEFAULT '',
      description TEXT NOT NULL,
      map_coords TEXT DEFAULT '',
      is_featured INTEGER NOT NULL DEFAULT 0,
      status TEXT NOT NULL DEFAULT 'published',
      created_at TEXT NOT NULL DEFAULT (datetime('now'))
    );

    CREATE TABLE IF NOT EXISTS advertisements (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      title TEXT NOT NULL,
      placement TEXT NOT NULL DEFAULT 'home_banner',
      banner_image TEXT DEFAULT '',
      target_url TEXT NOT NULL DEFAULT '#',
      start_date TEXT NOT NULL,
      end_date TEXT NOT NULL,
      status TEXT NOT NULL DEFAULT 'active',
      created_at TEXT NOT NULL DEFAULT (datetime('now'))
    );

    CREATE TABLE IF NOT EXISTS notifications (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      user_id INTEGER NOT NULL,
      title TEXT NOT NULL,
      message TEXT NOT NULL,
      is_read INTEGER NOT NULL DEFAULT 0,
      created_at TEXT NOT NULL DEFAULT (datetime('now')),
      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS reports (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      reporter_id INTEGER NOT NULL,
      target_type TEXT NOT NULL,
      target_id INTEGER NOT NULL,
      reason TEXT NOT NULL,
      details TEXT DEFAULT '',
      status TEXT NOT NULL DEFAULT 'open',
      created_at TEXT NOT NULL DEFAULT (datetime('now')),
      FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS moderation_rules (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      keyword TEXT NOT NULL UNIQUE,
      match_type TEXT NOT NULL DEFAULT 'word',
      severity TEXT NOT NULL DEFAULT 'flag',
      is_active INTEGER NOT NULL DEFAULT 1
    );

    CREATE TABLE IF NOT EXISTS moderation_logs (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      target_type TEXT NOT NULL,
      target_id INTEGER NOT NULL,
      user_id INTEGER DEFAULT NULL,
      matched_rule TEXT DEFAULT NULL,
      action_taken TEXT NOT NULL,
      created_at TEXT NOT NULL DEFAULT (datetime('now'))
    );

    CREATE TABLE IF NOT EXISTS admin_logs (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      admin_id INTEGER NOT NULL,
      action TEXT NOT NULL,
      target_type TEXT DEFAULT '',
      target_id INTEGER DEFAULT 0,
      details TEXT DEFAULT '',
      created_at TEXT NOT NULL DEFAULT (datetime('now'))
    );

    CREATE TABLE IF NOT EXISTS settings (
      setting_key TEXT PRIMARY KEY,
      setting_value TEXT NOT NULL
    );
  `);

  const newsCatCount = (db.prepare('SELECT COUNT(*) as c FROM news_categories').get() as { c: number }).c;
  if (newsCatCount === 0) {
    const insNC = db.prepare('INSERT INTO news_categories (slug, name_bn, name_en) VALUES (?, ?, ?)');
    insNC.run('gazipur-city', 'গাজীপুর সিটি', 'Gazipur City');
    insNC.run('development', 'উন্নয়ন ও অবকাঠামো', 'Development');
    insNC.run('education', 'শিক্ষা ও ক্যাম্পাস', 'Education');
    insNC.run('industry', 'শিল্প ও কর্মসংস্থান', 'Industry & Jobs');
    insNC.run('environment', 'ভাওয়াল ও পরিবেশ', 'Environment');

    const insMC = db.prepare('INSERT INTO marketplace_categories (slug, name_bn, name_en) VALUES (?, ?, ?)');
    [
      ['electronics', 'ইলেকট্রনিক্স', 'Electronics'],
      ['mobile', 'মোবাইল ফোন', 'Mobile Phones'],
      ['computers', 'কম্পিউটার', 'Computers'],
      ['motorcycles', 'মোটরসাইকেল', 'Motorcycles'],
      ['cars', 'গাড়ি', 'Cars'],
      ['furniture', 'আসবাবপত্র', 'Furniture'],
      ['appliances', 'গৃহস্থালি যন্ত্রপাতি', 'Home Appliances'],
      ['clothing', 'পোশাক', 'Clothing'],
      ['property', 'জমি ও প্রপার্টি', 'Property'],
      ['other', 'অন্যান্য পণ্য', 'Other Products'],
    ].forEach(([s, bn, en]) => insMC.run(s, bn, en));

    const insRule = db.prepare('INSERT OR IGNORE INTO moderation_rules (keyword, match_type, severity) VALUES (?, ?, ?)');
    ['জুয়া', 'ক্যাসিনো', 'বেটিং', 'প্রতারণা', 'scam', 'casino', 'betting'].forEach((kw) => insRule.run(kw, 'word', 'flag'));

    const insSet = db.prepare('INSERT OR IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?)');
    insSet.run('site_name', 'AmarGazipur');
    insSet.run('site_tagline_bn', 'গাজীপুর জেলার পূর্ণাঙ্গ ডিজিটাল কমিউনিটি প্ল্যাটফর্ম');
    insSet.run('site_tagline_en', 'Complete Digital Community Platform for Gazipur District');
    insSet.run('contact_email', 'support@amargazipur.local');
    insSet.run('seo_meta_title', 'AmarGazipur — গাজীপুর সংবাদ, কমিউনিটি, বাসা ভাড়া ও মার্কেটপ্লেস');
    insSet.run('footer_text', '© ২০২৬ AmarGazipur — গাজীপুর জেলা ডিজিটাল কমিউনিটি প্ল্যাটফর্ম।');
  }

  // Seed public showcase residents (non-admin accounts for sample Gazipur community content)
  const userCount = (db.prepare('SELECT COUNT(*) as c FROM users').get() as { c: number }).c;
  if (userCount === 0) {
    const demoHash = hashPassword(crypto.randomBytes(24).toString('hex'));
    const insUser = db.prepare('INSERT INTO users (role, full_name, email, password_hash, phone, upazila, bio, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    const u1 = Number(insUser.run('member', 'তানভীর আহমেদ', 'tanvir.gazipur@example.local', demoHash, '01711-000101', 'Gazipur Sadar', 'গাজীপুর জেলা প্রতিনিধি ও নাগরিক সাংবাদিক', 'active').lastInsertRowid);
    const u2 = Number(insUser.run('member', 'মাহমুদুল হাসান', 'mahmud.tongi@example.local', demoHash, '01812-000202', 'Tongi', 'টঙ্গী ও চেরাগআলী এলাকার আবাসন উদ্যোক্তা', 'active').lastInsertRowid);
    const u3 = Number(insUser.run('member', 'নুসরাত জাহান', 'nusrat.sreepur@example.local', demoHash, '01913-000303', 'Sreepur', 'স্বেচ্ছাসেবী রক্তদাতা সমন্বয়ক, শ্রীপুর', 'active').lastInsertRowid);

    const insCat = db.prepare('INSERT INTO user_categories (user_id, category_slug, status) VALUES (?, ?, ?)');
    insCat.run(u1, 'journalist', 'approved');
    insCat.run(u2, 'landlord', 'approved');
    insCat.run(u3, 'blood_donor', 'approved');

    // Sample News (Published + 1 Pending Journalist submission)
    const insNews = db.prepare('INSERT INTO news (category_id, author_id, slug, title, summary, content, featured_image, upazila, is_featured, views_count, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    insNews.run(
      2, u1, 'chandna-chowrasta-flyover-traffic-2026',
      'চান্দনা চৌরাস্তা ফ্লাইওভারে স্বস্তি ফিরছে গাজীপুরের যাতায়াতে',
      'ঢাকা-ময়মনসিংহ মহাসড়কের গাজীপুর অংশে উড়ালসড়ক ও উন্নত ট্রাফিক ব্যবস্থাপনায় কমেছে দীর্ঘ যানজট।',
      'গাজীপুর জেলার ব্যস্ততম কেন্দ্র চান্দনা চৌরাস্তা ও টঙ্গী বাজার এলাকায় নিয়মিত যানজট নিরসনে নতুন ট্রাফিক লেন ও ফ্লাইওভার সংযোগ সড়ক কার্যকর ভূমিকা রাখছে। সকাল ও বিকেলের অফিস সময়ে এখন গাজীপুর সদর থেকে উত্তরা ও বিমানবন্দর অভিমুখে যাতায়াত অনেকটাই স্বচ্ছন্দ হয়েছে।',
      '/src/assets/images/news_gazipur_infrastructure_1795729664313.jpg',
      'Gazipur Sadar', 1, 1420, 'published'
    );
    insNews.run(
      3, u1, 'duet-bsmrau-research-symposium-gazipur',
      'ডুয়েট ও গাজীপুর কৃষি বিশ্ববিদ্যালয়ে যৌথ প্রযুক্তি ও কৃষি গবেষণা সম্মেলন',
      'গাজীপুরের দুটি শীর্ষ বিশ্ববিদ্যালয়ের গবেষকরা পরিবেশবান্ধব শিল্প ও স্মার্ট কৃষি উদ্ভাবন উপস্থাপন করেছেন।',
      'শিববাড়ী ও সালনা ক্যাম্পাসের শিক্ষক-শিক্ষার্থীদের অংশগ্রহণে দিনব্যাপী গবেষণা প্রদর্শনী অনুষ্ঠিত হয়েছে। গাজীপুরের স্থানীয় শিল্পকারখানার বর্জ্য পরিশোধন ও টেকসই উৎপাদন বিষয়ে নতুন প্রস্তাবনা তুলে ধরা হয়।',
      '/src/assets/images/campus_gazipur_education_1795729664228.jpg',
      'Gazipur Sadar', 1, 890, 'published'
    );
    insNews.run(
      5, u1, 'bhawal-national-park-eco-tourism-drive',
      'ভাওয়াল জাতীয় উদ্যানে শালবন সংরক্ষণে নতুন ইকো-ট্যুরিজম উদ্যোগ',
      'গাজীপুরের ঐতিহ্যবাহী শালবন রক্ষায় প্লাস্টিকমুক্ত পরিবেশ ও সবুজ পর্যটন নিশ্চিত করতে বিশেষ কর্মসূচি গ্রহণ।',
      'রাজেন্দ্রপুর ও ভাওয়াল উদ্যানের জীববৈচিত্র্য সংরক্ষণে স্থানীয় স্বেচ্ছাসেবী ও বন বিভাগ একযোগে কাজ শুরু করেছে।',
      '/src/assets/images/hero_gazipur_community_1795729662480.jpg',
      'Gazipur Sadar', 0, 540, 'published'
    );
    insNews.run(
      4, u1, 'kaliakoir-hi-tech-park-new-investment',
      'কালিয়াকৈর হাই-টেক সিটিতে নতুন আইটি প্রশিক্ষণ ও সফটওয়্যার হাব (অপেক্ষমাণ)',
      'গাজীপুরের তরুণদের জন্য নতুন প্রযুক্তি কর্মসংস্থানের সুযোগ তৈরি হচ্ছে।',
      'কালিয়াকৈরে অবস্থিত হাই-টেক পার্কে স্থানীয় তরুণ প্রোগ্রামার ও ইঞ্জিনিয়ারদের জন্য নতুন প্রশিক্ষণ কেন্দ্র স্থাপনের প্রস্তাব জমা পড়েছে। (সাংবাদিক কর্তৃক জমাকৃত — অ্যাডমিন অনুমোদনের অপেক্ষায়)',
      '/src/assets/images/market_electronics_showcase_1795729663897.jpg',
      'Kaliakoir', 0, 0, 'pending'
    );

    // Sample Community Posts
    const insPost = db.prepare('INSERT INTO posts (user_id, category, upazila, title, content, image_path, status) VALUES (?, ?, ?, ?, ?, ?, ?)');
    insPost.run(u1, 'ট্রাফিক ও যাতায়াত', 'Gazipur Sadar', 'সকাল ৮টায় জয়দেবপুর থেকে টঙ্গী সড়কের বর্তমান অবস্থা', 'আজ সকালে জয়দেবপুর বাসস্ট্যান্ড থেকে ভোগড়া বাইপাস পর্যন্ত যান চলাচল বেশ স্বাভাবিক রয়েছে। জরুরি প্রয়োজনে যারা ঢাকা যাচ্ছেন তারা নির্বিঘ্নে বের হতে পারেন।', '/src/assets/images/news_gazipur_infrastructure_1795729664313.jpg', 'published');
    insPost.run(u3, 'হেল্প ডেস্ক', 'Sreepur', 'শ্রীপুর ও মাওনা এলাকায় জরুরি রক্তদাতা নেটওয়ার্কে স্বেচ্ছাসেবক আহ্বান', 'শ্রীপুর উপজেলা স্বাস্থ্য কমপ্লেক্সের রোগীদের সহায়তায় আমরা একটি ২৪ ঘণ্টার স্বেচ্ছাসেবী রক্তদাতা তালিকা আপডেট করছি। আগ্রহীরা রক্তদাতা সেকশনে নিবন্ধন করুন।', null, 'published');

    // Sample Marketplace Listings
    const insMkt = db.prepare('INSERT INTO marketplace_listings (user_id, category_id, title, description, price, item_condition, location, upazila, contact_phone, image_path, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    insMkt.run(u1, 3, 'MacBook Air M1 (8GB/256GB) — চমৎকার অবস্থায়', 'মাত্র ৮ মাস ব্যবহৃত, ব্যাটারি হেলথ ৯৪%। বক্স ও অরিজিনাল চার্জারসহ জয়দেবপুর থেকে সরাসরি দেখে নিতে পারবেন।', 76500, 'like_new', 'শিববাড়ী মোড়, জয়দেবপুর', 'Gazipur Sadar', '01711-000101', '/src/assets/images/market_electronics_showcase_1795729663897.jpg', 'published');
    insMkt.run(u2, 6, 'সেগুন কাঠের ৬ সিটের ডাইনিং টেবিল', 'বাসা পরিবর্তনের কারণে প্রায় নতুন সেগুন কাঠের ডাইনিং সেট বিক্রি করা হবে।', 24000, 'used', 'চেরাগআলী মার্কেট, টঙ্গী', 'Tongi', '01812-000202', null, 'published');

    // Sample Rentals
    const insRent = db.prepare('INSERT INTO rental_listings (user_id, category, title, description, monthly_rent, advance_amount, location, upazila, bedrooms, bathrooms, floor_number, facilities, available_from, contact_phone, hide_contact_public, image_path, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    insRent.run(u2, 'family_apartment', '৩ বেডরুমের আধুনিক ফ্যামিলি ফ্ল্যাট ভাড়া হবে — চেরাগআলী', 'দক্ষিণমুখী খোলামেলা ৩ বেড, ৩ বাথ, ২ বারান্দা, ২৪ ঘণ্টা গ্যাস ও লিফট সুবিধা রয়েছে।', 18500, 35000, 'চেরাগআলী, টঙ্গী', 'Tongi', 3, 3, '৫ম তলা', 'লিফট, সিসিটিভি, গ্যাস, জেনারেটর', '২০২৬-১২-০১', '01812-000202', 1, '/src/assets/images/property_gazipur_apartment_1795729663309.jpg', 'published');
    insRent.run(u1, 'bachelor_room', 'ডুয়েট ক্যাম্পাসের পাশে শিক্ষার্থী/ব্যাচেলর সিট ভাড়া', 'নিরিবিলি পরিবেশে ২ সিটের রুম, ওয়াইফাই ও খাবারের সুব্যবস্থা।', 4500, 4500, 'শিববাড়ী, গাজীপুর সদর', 'Gazipur Sadar', 1, 1, '৩য় তলা', 'ওয়াইফাই, ফ্রিজ, নিরাপত্তা', '২০২৬-১২-০১', '01711-000101', 1, null, 'published');

    // Sample Education Institutions
    const insEdu = db.prepare('INSERT INTO education_institutions (user_id, type, name, upazila, address, phone, website, courses, description, cover_image, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    insEdu.run(u1, 'university', 'ঢাকা প্রকৌশল ও প্রযুক্তি বিশ্ববিদ্যালয় (ডুয়েট)', 'Gazipur Sadar', 'শিববাড়ী, গাজীপুর সদর-১৭০৭', '02-49274003', 'https://www.duet.ac.bd', 'CSE, EEE, Civil, Mechanical, Textile, Architecture', 'ডিপ্লোমা প্রকৌশলীদের উচ্চশিক্ষার জন্য দেশের অন্যতম প্রধান সরকারি প্রকৌশল বিশ্ববিদ্যালয়।', '/src/assets/images/campus_gazipur_education_1795729664228.jpg', 'published');
    insEdu.run(u1, 'college', 'ভাওয়াল বদরে আলম সরকারি কলেজ', 'Gazipur Sadar', 'চান্দনা চৌরাস্তা, গাজীপুর', '02-49261200', 'https://bbagc.edu.bd', 'এইচএসসি, স্নাতক (সম্মান) ও স্নাতকোত্তর', 'গাজীপুরের ঐতিহ্যবাহী উচ্চশিক্ষা ও ঐতিহাসিক কলেজ।', null, 'published');
    insEdu.run(u3, 'university', 'গাজীপুর কৃষি বিশ্ববিদ্যালয় (বশেমুরকৃবি)', 'Gazipur Sadar', 'সালনা, গাজীপুর-১৭০৬', '02-49270101', 'https://bsmrau.edu.bd', 'Agriculture, Fisheries, Veterinary, Agribusiness', 'কৃষি গবেষণা ও উদ্ভাবনে অগ্রণী সরকারি বিশ্ববিদ্যালয়।', null, 'published');

    // Sample Blood Donors & Requests
    const insDonor = db.prepare('INSERT INTO blood_donors (user_id, donor_name, blood_group, area, upazila, phone, contact_preference, consent_public_contact, is_available, last_donation_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    insDonor.run(u3, 'নুসরাত জাহান', 'O+', 'মাওনা চৌরাস্তা', 'Sreepur', '01913-000303', 'call', 1, 1, '2026-06-15');
    insDonor.run(u1, 'রাফিউজ্জামান', 'B+', 'জয়দেবপুর বাজার', 'Gazipur Sadar', '01711-998877', 'message', 0, 1, '2026-07-02');
    insDonor.run(u2, 'সাব্বির হোসেন', 'A+', 'বোর্ড বাজার', 'Tongi', '01812-445566', 'call', 1, 1, '2026-05-20');

    const insBReq = db.prepare('INSERT INTO blood_requests (user_id, patient_name, blood_group, units_needed, hospital_name, upazila, needed_date, contact_phone, notes, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    insBReq.run(u3, 'আব্দুর রহমান (সার্জারি)', 'AB+', 2, 'শহীদ তাজউদ্দীন আহমদ মেডিকেল কলেজ হাসপাতাল', 'Gazipur Sadar', '2026-11-28', '01913-000303', 'জরুরি ভিত্তিতে আগামীকাল সকালের মধ্যে প্রয়োজন', 'open');

    // Sample Businesses & Ads
    const insBus = db.prepare('INSERT INTO businesses (user_id, category, name, address, upazila, phone, hours, description, map_coords, is_featured, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    insBus.run(u2, 'hospitals', 'শহীদ তাজউদ্দীন আহমদ মেডিকেল কলেজ হাসপাতাল', 'জয়দেবপুর, গাজীপুর সদর', 'Gazipur Sadar', '02-49263311', '২৪ ঘণ্টা খোলা', 'গাজীপুর জেলার প্রধান সরকারি মেডিকেল কলেজ ও জরুরি চিকিৎসা কেন্দ্র।', '23.9999, 90.4203', 1, 'published');
    insBus.run(u2, 'restaurants', 'ভাওয়াল হেরিটেজ রেস্তোরাঁ', 'রাজবাড়ী রোড, গাজীপুর সদর', 'Gazipur Sadar', '01755-221100', 'সকাল ১০টা - রাত ১১টা', 'পরিবার ও বন্ধুদের জন্য ঐতিহ্যবাহী বাংলা ও গ্রিল খাবার।', '24.0012, 90.4241', 1, 'published');

    const insAd = db.prepare('INSERT INTO advertisements (title, placement, banner_image, target_url, start_date, end_date, status) VALUES (?, ?, ?, ?, ?, ?, ?)');
    insAd.run('গাজীপুর টেক ফেয়ার ২০২৬ — তরুণ উদ্যোক্তা ও শিক্ষার্থীদের নিবন্ধন চলছে', 'home_banner', '/src/assets/images/hero_gazipur_community_1795729662480.jpg', '#education', '2026-01-01', '2027-12-31', 'active');
  }

  const jobCount = (db.prepare('SELECT COUNT(*) as c FROM job_listings').get() as { c: number }).c;
  if (jobCount === 0) {
    const firstUser = db.prepare('SELECT id FROM users ORDER BY id ASC LIMIT 1').get() as { id: number } | undefined;
    if (firstUser) {
      db.prepare("INSERT OR IGNORE INTO user_categories (user_id, category_slug, status) VALUES (?, 'job_poster', 'approved')").run(firstUser.id);
      const insJob = db.prepare('INSERT INTO job_listings (user_id, company_name, job_title, job_type, salary, location, upazila, contact_info, deadline, description, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
      insJob.run(firstUser.id, 'গাজীপুর টেক সলিউশনস লিঃ', 'জুনিয়র সফটওয়্যার সাপোর্ট ইঞ্জিনিয়ার', 'full_time', '২৫,০০০ - ৩৫,০০০ টাকা', 'চান্দনা চৌরাস্তা, গাজীপুর সদর', 'Gazipur Sadar', '01711-000101 / hr@gazipurtech.local', '2026-12-31', 'স্নাতক/ডিপ্লোমা ইন কম্পিউটার সায়েন্স ও নেটওয়ার্কিং জ্ঞানসম্পন্ন প্রার্থীদের অগ্রাধিকার দেওয়া হবে।', 'published');
      insJob.run(firstUser.id, 'ভাওয়াল অ্যাপারেলস ইন্ডাস্ট্রিজ', 'কোয়ালিটি কন্ট্রোল (QC) অফিসার', 'full_time', '১৮,০০০ - ২২,০০০ টাকা', 'মাওনা চৌরাস্তা, শ্রীপুর', 'Sreepur', '01812-000202', '2026-12-25', 'গার্মেন্টস কোয়ালিটি সেকশনে কমপক্ষে ১ বছরের অভিজ্ঞতাসম্পন্ন প্রার্থী প্রয়োজন।', 'published');
    }
  }
}
