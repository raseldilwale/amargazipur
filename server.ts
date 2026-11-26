import express, { Request, Response, NextFunction } from 'express';
import crypto from 'node:crypto';
import fs from 'node:fs';
import path from 'node:path';
import multer from 'multer';
import { createServer as createViteServer } from 'vite';
import { db, hashPassword, initDatabase, verifyPassword } from './src/server/db.js';

initDatabase();

const app = express();
const PORT = 3000;
const SESSION_SECRET = process.env.SESSION_SECRET || crypto.randomBytes(32).toString('hex');

const uploadsDir = path.resolve(process.cwd(), 'public/uploads');
if (!fs.existsSync(uploadsDir)) {
  fs.mkdirSync(uploadsDir, { recursive: true });
}

const upload = multer({
  storage: multer.diskStorage({
    destination: (_req, _file, cb) => cb(null, uploadsDir),
    filename: (_req, file, cb) => {
      const extMap: Record<string, string> = {
        'image/jpeg': '.jpg',
        'image/png': '.png',
        'image/webp': '.webp',
      };
      const ext = extMap[file.mimetype] || '.jpg';
      cb(null, `${Date.now()}_${crypto.randomBytes(6).toString('hex')}${ext}`);
    },
  }),
  limits: { fileSize: 5 * 1024 * 1024 },
  fileFilter: (_req, file, cb) => {
    if (['image/jpeg', 'image/png', 'image/webp'].includes(file.mimetype)) {
      cb(null, true);
    } else {
      cb(new Error('শুধুমাত্র JPG, PNG অথবা WEBP ছবি আপলোড করা যাবে।'));
    }
  },
});

app.use(express.json({ limit: '2mb' }));
app.use('/uploads', express.static(uploadsDir));

// Explicit ZIP & SQL download routes
app.get('/downloads/AmarGazipur_Full_Website.zip', (_req, res) => {
  const zipFile = path.resolve(process.cwd(), 'public/downloads/AmarGazipur_Full_Website.zip');
  if (!fs.existsSync(zipFile)) {
    return res.status(404).json({ error: 'ZIP archive not found' });
  }
  return res.download(zipFile, 'AmarGazipur_Full_Website.zip');
});

app.get('/downloads/amargazipur_database.sql', (_req, res) => {
  const sqlFile = path.resolve(process.cwd(), 'public/downloads/amargazipur_database.sql');
  if (!fs.existsSync(sqlFile)) {
    return res.status(404).json({ error: 'SQL file not found' });
  }
  return res.download(sqlFile, 'amargazipur_database.sql');
});

interface AuthUser {
  id: number;
  role: string;
  full_name: string;
  email: string;
  upazila: string;
  phone: string;
  bio: string;
  status: string;
  categories: { id: number; category_slug: string; status: string }[];
}

function signToken(userId: number): string {
  const payload = Buffer.from(JSON.stringify({ uid: userId, iat: Date.now(), exp: Date.now() + 7 * 86400 * 1000 })).toString('base64url');
  const sig = crypto.createHmac('sha256', SESSION_SECRET).update(payload).digest('base64url');
  return `${payload}.${sig}`;
}

function verifyToken(token: string): number | null {
  const parts = token.split('.');
  if (parts.length !== 2) return null;
  const [payload, sig] = parts;
  const expected = crypto.createHmac('sha256', SESSION_SECRET).update(payload).digest('base64url');
  const sigBuf = Buffer.from(sig);
  const expBuf = Buffer.from(expected);
  if (sigBuf.length !== expBuf.length || !crypto.timingSafeEqual(sigBuf, expBuf)) return null;
  try {
    const data = JSON.parse(Buffer.from(payload, 'base64url').toString('utf8')) as { uid: number; exp: number };
    if (!data.uid || Date.now() > data.exp) return null;
    return Number(data.uid);
  } catch {
    return null;
  }
}

function loadUser(userId: number): AuthUser | null {
  const u = db.prepare('SELECT id, role, full_name, email, upazila, phone, bio, status FROM users WHERE id = ?').get(userId) as Omit<AuthUser, 'categories'> | undefined;
  if (!u || u.status !== 'active') return null;
  const categories = db.prepare('SELECT id, category_slug, status FROM user_categories WHERE user_id = ?').all(userId) as { id: number; category_slug: string; status: string }[];
  return { ...u, categories };
}

function getAuthUser(req: Request): AuthUser | null {
  const header = req.headers.authorization || '';
  if (!header.startsWith('Bearer ')) return null;
  const uid = verifyToken(header.slice(7).trim());
  if (!uid) return null;
  return loadUser(uid);
}

function requireAuth(req: Request, res: Response, next: NextFunction) {
  const user = getAuthUser(req);
  if (!user) {
    return res.status(401).json({ error: 'অনুগ্রহ করে প্রথমে লগইন করুন।', requireAuth: true });
  }
  (req as Request & { authUser: AuthUser }).authUser = user;
  return next();
}

function requireAdmin(req: Request, res: Response, next: NextFunction) {
  const user = getAuthUser(req);
  if (!user) {
    return res.status(401).json({ error: 'অ্যাডমিন লগইন প্রয়োজন।', requireAuth: true });
  }
  if (!['admin', 'editor', 'moderator'].includes(user.role)) {
    return res.status(403).json({ error: 'অ্যাডমিন অনুমতি প্রয়োজন।' });
  }
  (req as Request & { authUser: AuthUser }).authUser = user;
  return next();
}

function hasApprovedCategory(user: AuthUser, slug: string): boolean {
  if (user.role === 'admin') return true;
  return user.categories.some((c) => c.category_slug === slug && c.status === 'approved');
}

function checkModeration(text: string): { flagged: boolean; keyword: string | null } {
  const rules = db.prepare('SELECT keyword, match_type FROM moderation_rules WHERE is_active = 1').all() as { keyword: string; match_type: string }[];
  const lower = text.toLowerCase();
  for (const r of rules) {
    const kw = r.keyword.trim().toLowerCase();
    if (!kw) continue;
    if (r.match_type === 'phrase') {
      if (lower.includes(kw)) return { flagged: true, keyword: r.keyword };
    } else {
      const escaped = kw.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
      const regex = new RegExp(`(^|[\\s,.!?;:'"()\\-])${escaped}($|[\\s,.!?;:'"()\\-])`, 'iu');
      if (regex.test(text)) return { flagged: true, keyword: r.keyword };
    }
  }
  const links = text.match(/https?:\/\/\S+/gi) || [];
  if (links.length > 3) {
    return { flagged: true, keyword: 'excessive_links' };
  }
  return { flagged: false, keyword: null };
}

function slugify(title: string): string {
  const clean = title.toLowerCase().replace(/[^\p{L}\p{N}\s-]/gu, '').trim().replace(/\s+/g, '-');
  return `${clean || 'news'}-${crypto.randomBytes(3).toString('hex')}`;
}

// --- PUBLIC BOOTSTRAP & SETTINGS ---
app.get('/api/bootstrap', (req, res) => {
  const user = getAuthUser(req);
  const adminCount = (db.prepare("SELECT COUNT(*) as c FROM users WHERE role = 'admin'").get() as { c: number }).c;
  const settingsRows = db.prepare('SELECT setting_key, setting_value FROM settings').all() as { setting_key: string; setting_value: string }[];
  const settings: Record<string, string> = {};
  for (const s of settingsRows) settings[s.setting_key] = s.setting_value;

  const newsCategories = db.prepare('SELECT * FROM news_categories ORDER BY id ASC').all();
  const marketCategories = db.prepare('SELECT * FROM marketplace_categories ORDER BY id ASC').all();
  const activeAds = db.prepare("SELECT * FROM advertisements WHERE status = 'active' ORDER BY id DESC").all();

  const zipPath = path.resolve(process.cwd(), 'public/downloads/AmarGazipur_Full_Website.zip');
  const zipSize = fs.existsSync(zipPath) ? fs.statSync(zipPath).size : 0;

  res.json({
    user,
    adminClaimed: adminCount > 0,
    settings,
    newsCategories,
    marketCategories,
    activeAds,
    zipAvailable: zipSize > 0,
    zipSizeBytes: zipSize,
  });
});

// --- AUTHENTICATION ---
app.post('/api/auth/claim-admin', (req, res) => {
  const adminCount = (db.prepare("SELECT COUNT(*) as c FROM users WHERE role = 'admin'").get() as { c: number }).c;
  if (adminCount > 0) {
    return res.status(403).json({ error: 'অ্যাডমিন অ্যাকাউন্ট ইতিমধ্যে তৈরি করা হয়েছে।' });
  }
  const fullName = String(req.body.full_name || '').trim();
  const email = String(req.body.email || '').trim().toLowerCase();
  const password = String(req.body.password || '');
  if (!fullName || !email.includes('@') || password.length < 8) {
    return res.status(400).json({ error: 'সঠিক নাম, ইমেইল ও কমপক্ষে ৮ অক্ষরের পাসওয়ার্ড দিন।' });
  }
  const exists = db.prepare('SELECT id FROM users WHERE email = ?').get(email);
  if (exists) {
    return res.status(400).json({ error: 'এই ইমেইলটি ইতিমধ্যে ব্যবহৃত হয়েছে।' });
  }
  const info = db.prepare("INSERT INTO users (role, full_name, email, password_hash, upazila, bio, status) VALUES ('admin', ?, ?, ?, 'Gazipur Sadar', 'আমার গাজীপুর প্রধান অ্যাডমিনিস্ট্রেটর', 'active')").run(fullName, email, hashPassword(password));
  const uid = Number(info.lastInsertRowid);
  db.prepare("INSERT INTO user_categories (user_id, category_slug, status) VALUES (?, 'general_user', 'approved')").run(uid);
  db.prepare("INSERT INTO admin_logs (admin_id, action, target_type, target_id, details) VALUES (?, 'initial_admin_claimed', 'user', ?, ?)").run(uid, uid, email);
  const user = loadUser(uid);
  return res.json({ token: signToken(uid), user });
});

app.post('/api/auth/register', (req, res) => {
  const fullName = String(req.body.full_name || '').trim();
  const email = String(req.body.email || '').trim().toLowerCase();
  const password = String(req.body.password || '');
  const confirmPassword = String(req.body.confirm_password || '');
  const upazila = String(req.body.upazila || 'Gazipur Sadar').trim();
  const termsAccepted = Boolean(req.body.terms_accepted);
  const categories = Array.isArray(req.body.categories) && req.body.categories.length > 0 ? req.body.categories : ['general_user'];

  if (!fullName || !email.includes('@')) {
    return res.status(400).json({ error: 'সঠিক নাম ও ইমেইল দিন।' });
  }
  if (password.length < 8 || password !== confirmPassword) {
    return res.status(400).json({ error: 'পাসওয়ার্ড কমপক্ষে ৮ অক্ষরের হতে হবে এবং উভয় পাসওয়ার্ড মিলতে হবে।' });
  }
  if (!termsAccepted) {
    return res.status(400).json({ error: 'শর্তাবলী ও গোপনীয়তা নীতিতে সম্মতি দিতে হবে।' });
  }
  const exists = db.prepare('SELECT id FROM users WHERE email = ?').get(email);
  if (exists) {
    return res.status(400).json({ error: 'এই ইমেইল দিয়ে ইতিমধ্যে অ্যাকাউন্ট রয়েছে।' });
  }

  const allowedCats = ['general_user', 'student', 'journalist', 'landlord', 'business_owner', 'educational_institution', 'blood_donor', 'organization', 'job_poster'];
  const validCats = categories.filter((c: unknown) => typeof c === 'string' && allowedCats.includes(c));
  if (validCats.length === 0) validCats.push('general_user');

  const info = db.prepare("INSERT INTO users (role, full_name, email, password_hash, upazila, status) VALUES ('member', ?, ?, ?, ?, 'active')").run(fullName, email, hashPassword(password), upazila);
  const uid = Number(info.lastInsertRowid);
  const insCat = db.prepare("INSERT INTO user_categories (user_id, category_slug, status) VALUES (?, ?, 'pending')");
  for (const c of validCats) {
    insCat.run(uid, c);
  }
  db.prepare('INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)').run(
    uid,
    'স্বাগতম AmarGazipur-এ',
    'আপনার নির্বাচিত অ্যাকাউন্ট ক্যাটাগরিগুলো অ্যাডমিন অনুমোদনের জন্য অপেক্ষমাণ (Pending) রয়েছে।'
  );

  const user = loadUser(uid);
  return res.json({ token: signToken(uid), user });
});

app.post('/api/auth/login', (req, res) => {
  const email = String(req.body.email || '').trim().toLowerCase();
  const password = String(req.body.password || '');
  const row = db.prepare('SELECT * FROM users WHERE email = ?').get(email) as { id: number; password_hash: string; status: string } | undefined;
  if (!row || !verifyPassword(password, row.password_hash)) {
    return res.status(401).json({ error: 'ইমেইল অথবা পাসওয়ার্ড সঠিক নয়।' });
  }
  if (row.status === 'suspended') {
    return res.status(403).json({ error: 'আপনার অ্যাকাউন্টটি স্থগিত করা হয়েছে।' });
  }
  const user = loadUser(row.id);
  return res.json({ token: signToken(row.id), user });
});

app.get('/api/auth/me', requireAuth, (req, res) => {
  const user = (req as Request & { authUser: AuthUser }).authUser;
  res.json({ user });
});

// --- IMAGE UPLOAD ---
app.post('/api/upload', requireAuth, upload.single('image'), (req, res) => {
  if (!req.file) {
    return res.status(400).json({ error: 'কোনো ছবি নির্বাচন করা হয়নি।' });
  }
  return res.json({ url: `/uploads/${req.file.filename}` });
});

// --- COMMUNITY POSTS ---
app.get('/api/posts', (req, res) => {
  const user = getAuthUser(req);
  const q = String(req.query.q || '').trim();
  const category = String(req.query.category || '').trim();

  const clauses = ["p.status = 'published'"];
  const params: unknown[] = [];
  if (q) {
    clauses.push('(p.title LIKE ? OR p.content LIKE ?)');
    params.push(`%${q}%`, `%${q}%`);
  }
  if (category) {
    clauses.push('p.category = ?');
    params.push(category);
  }
  const sql = `
    SELECT p.*, u.full_name AS author_name,
      (SELECT COUNT(*) FROM likes WHERE post_id = p.id) AS likes_count
    FROM posts p
    JOIN users u ON u.id = p.user_id
    WHERE ${clauses.join(' AND ')}
    ORDER BY p.created_at DESC
    LIMIT 40
  `;
  const posts = db.prepare(sql).all(...params) as Record<string, unknown>[];
  for (const p of posts) {
    const cmts = db.prepare(`
      SELECT c.*, u.full_name AS author_name
      FROM comments c JOIN users u ON u.id = c.user_id
      WHERE c.post_id = ? AND c.status = 'published'
      ORDER BY c.created_at ASC
    `).all(p.id) as Record<string, unknown>[];
    for (const c of cmts) {
      c.replies = db.prepare(`
        SELECT cr.*, u.full_name AS author_name
        FROM comment_replies cr JOIN users u ON u.id = cr.user_id
        WHERE cr.comment_id = ? ORDER BY cr.created_at ASC
      `).all(c.id);
    }
    p.comments = cmts;
    p.liked_by_me = user ? Boolean(db.prepare('SELECT 1 FROM likes WHERE post_id = ? AND user_id = ?').get(p.id, user.id)) : false;
  }
  res.json({ posts });
});

app.post('/api/posts', requireAuth, (req, res) => {
  const user = (req as Request & { authUser: AuthUser }).authUser;
  const title = String(req.body.title || '').trim();
  const content = String(req.body.content || '').trim();
  const category = String(req.body.category || 'সাধারণ আলোচনা').trim();
  const upazila = String(req.body.upazila || user.upazila).trim();
  const imagePath = req.body.image_path ? String(req.body.image_path) : null;

  if (!title || !content) {
    return res.status(400).json({ error: 'পোস্টের শিরোনাম ও বিবরণ লিখুন।' });
  }
  const mod = checkModeration(`${title}\n${content}`);
  const status = mod.flagged ? 'pending_moderation' : 'published';
  const reason = mod.flagged ? `মডারেশন নীতিমালা যাচাই (${mod.keyword})` : null;

  const info = db.prepare('INSERT INTO posts (user_id, category, upazila, title, content, image_path, status, moderation_reason) VALUES (?, ?, ?, ?, ?, ?, ?, ?)').run(user.id, category, upazila, title, content, imagePath, status, reason);
  if (mod.flagged) {
    db.prepare("INSERT INTO moderation_logs (target_type, target_id, user_id, matched_rule, action_taken) VALUES ('post', ?, ?, ?, 'flagged_pending')").run(Number(info.lastInsertRowid), user.id, mod.keyword);
  }
  return res.json({
    id: Number(info.lastInsertRowid),
    status,
    message: mod.flagged ? 'সন্দেহজনক শব্দ শনাক্ত হওয়ায় পোস্টটি অ্যাডমিন পর্যালোচনার জন্য অপেক্ষমাণ (Pending Moderation) রয়েছে।' : 'পোস্টটি সফলভাবে প্রকাশিত হয়েছে।',
  });
});

app.put('/api/posts/:id', requireAuth, (req, res) => {
  const user = (req as Request & { authUser: AuthUser }).authUser;
  const postId = Number(req.params.id);
  const existing = db.prepare('SELECT * FROM posts WHERE id = ?').get(postId) as { user_id: number } | undefined;
  if (!existing) return res.status(404).json({ error: 'পোস্ট পাওয়া যায়নি।' });
  if (existing.user_id !== user.id && !['admin', 'moderator'].includes(user.role)) {
    return res.status(403).json({ error: 'অনুমতি নেই।' });
  }
  const title = String(req.body.title || '').trim();
  const content = String(req.body.content || '').trim();
  if (!title || !content) return res.status(400).json({ error: 'শিরোনাম ও বিবরণ আবশ্যক।' });
  db.prepare('UPDATE posts SET title = ?, content = ? WHERE id = ?').run(title, content, postId);
  return res.json({ ok: true });
});

app.delete('/api/posts/:id', requireAuth, (req, res) => {
  const user = (req as Request & { authUser: AuthUser }).authUser;
  const postId = Number(req.params.id);
  const existing = db.prepare('SELECT user_id FROM posts WHERE id = ?').get(postId) as { user_id: number } | undefined;
  if (!existing) return res.status(404).json({ error: 'পোস্ট পাওয়া যায়নি।' });
  if (existing.user_id !== user.id && !['admin', 'moderator'].includes(user.role)) {
    return res.status(403).json({ error: 'অনুমতি নেই।' });
  }
  db.prepare('DELETE FROM posts WHERE id = ?').run(postId);
  return res.json({ ok: true });
});

app.post('/api/posts/:id/like', requireAuth, (req, res) => {
  const user = (req as Request & { authUser: AuthUser }).authUser;
  const postId = Number(req.params.id);
  const existing = db.prepare('SELECT id FROM likes WHERE post_id = ? AND user_id = ?').get(postId, user.id) as { id: number } | undefined;
  let liked = false;
  if (existing) {
    db.prepare('DELETE FROM likes WHERE id = ?').run(existing.id);
  } else {
    db.prepare('INSERT INTO likes (post_id, user_id) VALUES (?, ?)').run(postId, user.id);
    liked = true;
  }
  const count = (db.prepare('SELECT COUNT(*) as c FROM likes WHERE post_id = ?').get(postId) as { c: number }).c;
  res.json({ liked, count });
});

app.post('/api/posts/:id/comments', requireAuth, (req, res) => {
  const user = (req as Request & { authUser: AuthUser }).authUser;
  const postId = Number(req.params.id);
  const body = String(req.body.body || '').trim();
  if (!body) return res.status(400).json({ error: 'মন্তব্য লিখুন।' });
  const mod = checkModeration(body);
  const status = mod.flagged ? 'pending_moderation' : 'published';
  db.prepare('INSERT INTO comments (post_id, user_id, body, status) VALUES (?, ?, ?, ?)').run(postId, user.id, body, status);
  res.json({ status, message: mod.flagged ? 'মন্তব্যটি পর্যালোচনার জন্য অপেক্ষমাণ।' : 'মন্তব্য যুক্ত হয়েছে।' });
});

app.post('/api/comments/:id/replies', requireAuth, (req, res) => {
  const user = (req as Request & { authUser: AuthUser }).authUser;
  const commentId = Number(req.params.id);
  const body = String(req.body.body || '').trim();
  if (!body) return res.status(400).json({ error: 'উত্তর লিখুন।' });
  db.prepare('INSERT INTO comment_replies (comment_id, user_id, body) VALUES (?, ?, ?)').run(commentId, user.id, body);
  res.json({ ok: true });
});

app.post('/api/reports', requireAuth, (req, res) => {
  const user = (req as Request & { authUser: AuthUser }).authUser;
  const targetType = String(req.body.target_type || 'post');
  const targetId = Number(req.body.target_id || 0);
  const reason = String(req.body.reason || 'অনুপযুক্ত কনটেন্ট').trim();
  db.prepare('INSERT INTO reports (reporter_id, target_type, target_id, reason) VALUES (?, ?, ?, ?)').run(user.id, targetType, targetId, reason);
  res.json({ ok: true, message: 'রিপোর্টটি মডারেশন টিমের কাছে পাঠানো হয়েছে।' });
});

// --- NEWS PORTAL ---
app.get('/api/news', (req, res) => {
  const cat = String(req.query.cat || '').trim();
  const q = String(req.query.q || '').trim();
  const clauses = ["n.status = 'published'"];
  const params: unknown[] = [];
  if (cat) {
    clauses.push('c.slug = ?');
    params.push(cat);
  }
  if (q) {
    clauses.push('(n.title LIKE ? OR n.summary LIKE ?)');
    params.push(`%${q}%`, `%${q}%`);
  }
  const articles = db.prepare(`
    SELECT n.*, c.name_bn AS category_bn, c.name_en AS category_en, c.slug AS category_slug, u.full_name AS author_name, u.bio AS author_bio
    FROM news n
    JOIN news_categories c ON c.id = n.category_id
    JOIN users u ON u.id = n.author_id
    WHERE ${clauses.join(' AND ')}
    ORDER BY n.is_featured DESC, n.created_at DESC
  `).all(...params);
  res.json({ articles });
});

app.post('/api/news', requireAuth, (req, res) => {
  const user = (req as Request & { authUser: AuthUser }).authUser;
  if (!hasApprovedCategory(user, 'journalist') && !['admin', 'editor'].includes(user.role)) {
    return res.status(403).json({ error: 'সংবাদ জমা দিতে অনুমোদিত সাংবাদিক (Journalist) ক্যাটাগরি প্রয়োজন।' });
  }
  const title = String(req.body.title || '').trim();
  const summary = String(req.body.summary || '').trim();
  const content = String(req.body.content || '').trim();
  const categoryId = Number(req.body.category_id || 1);
  const upazila = String(req.body.upazila || 'Gazipur Sadar').trim();
  const featuredImage = req.body.featured_image ? String(req.body.featured_image) : null;

  if (!title || !summary || !content) {
    return res.status(400).json({ error: 'শিরোনাম, সারাংশ ও বিস্তারিত সংবাদ লিখুন।' });
  }
  // Journalist submissions ALWAYS stay 'pending' until Admin/Editor approves
  const info = db.prepare("INSERT INTO news (category_id, author_id, slug, title, summary, content, featured_image, upazila, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')").run(categoryId, user.id, slugify(title), title, summary, content, featuredImage, upazila);
  res.json({
    id: Number(info.lastInsertRowid),
    status: 'pending',
    message: 'সংবাদটি জমা হয়েছে। অ্যাডমিন বা সম্পাদক অনুমোদন করলে প্রকাশিত হবে (Pending Approval)।',
  });
});

// --- MARKETPLACE ---
app.get('/api/marketplace', (req, res) => {
  const user = getAuthUser(req);
  const cat = String(req.query.cat || '').trim();
  const upazila = String(req.query.upazila || '').trim();
  const q = String(req.query.q || '').trim();
  const clauses = ["m.status = 'published'"];
  const params: unknown[] = [];
  if (cat) { clauses.push('c.slug = ?'); params.push(cat); }
  if (upazila) { clauses.push('m.upazila = ?'); params.push(upazila); }
  if (q) { clauses.push('(m.title LIKE ? OR m.description LIKE ?)'); params.push(`%${q}%`, `%${q}%`); }

  const rows = db.prepare(`
    SELECT m.*, c.name_bn AS category_bn, c.name_en AS category_en, u.full_name AS seller_name
    FROM marketplace_listings m
    JOIN marketplace_categories c ON c.id = m.category_id
    JOIN users u ON u.id = m.user_id
    WHERE ${clauses.join(' AND ')}
    ORDER BY m.created_at DESC
  `).all(...params) as Record<string, unknown>[];

  const listings = rows.map((r) => ({
    ...r,
    contact_phone: user ? r.contact_phone : null,
  }));
  res.json({ listings });
});

app.post('/api/marketplace', requireAuth, (req, res) => {
  const user = (req as Request & { authUser: AuthUser }).authUser;
  const title = String(req.body.title || '').trim();
  const description = String(req.body.description || '').trim();
  const price = Number(req.body.price || 0);
  const condition = String(req.body.item_condition || 'used');
  const categoryId = Number(req.body.category_id || 1);
  const location = String(req.body.location || '').trim();
  const upazila = String(req.body.upazila || 'Gazipur Sadar').trim();
  const phone = String(req.body.contact_phone || '').trim();
  const imagePath = req.body.image_path ? String(req.body.image_path) : null;

  if (!title || !description || !location || !phone) {
    return res.status(400).json({ error: 'সকল আবশ্যকীয় তথ্য প্রদান করুন।' });
  }
  const mod = checkModeration(`${title}\n${description}`);
  const status = mod.flagged ? 'pending_moderation' : 'published';
  db.prepare('INSERT INTO marketplace_listings (user_id, category_id, title, description, price, item_condition, location, upazila, contact_phone, image_path, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)').run(user.id, categoryId, title, description, price, condition, location, upazila, phone, imagePath, status);
  res.json({ status, message: mod.flagged ? 'বিজ্ঞাপনটি মডারেশন পর্যালোচনায় পাঠানো হয়েছে।' : 'পণ্যটি মার্কেটপ্লেসে প্রকাশিত হয়েছে।' });
});

// --- RENTALS / TO-LET ---
app.get('/api/rentals', (req, res) => {
  const user = getAuthUser(req);
  const cat = String(req.query.cat || '').trim();
  const upazila = String(req.query.upazila || '').trim();
  const clauses = ["r.status = 'published'"];
  const params: unknown[] = [];
  if (cat) { clauses.push('r.category = ?'); params.push(cat); }
  if (upazila) { clauses.push('r.upazila = ?'); params.push(upazila); }

  const rows = db.prepare(`
    SELECT r.*, u.full_name AS landlord_name
    FROM rental_listings r JOIN users u ON u.id = r.user_id
    WHERE ${clauses.join(' AND ')}
    ORDER BY r.created_at DESC
  `).all(...params) as Record<string, unknown>[];

  const rentals = rows.map((r) => ({
    ...r,
    contact_phone: (!r.hide_contact_public || user) ? r.contact_phone : null,
  }));
  res.json({ rentals });
});

app.post('/api/rentals', requireAuth, (req, res) => {
  const user = (req as Request & { authUser: AuthUser }).authUser;
  if (!hasApprovedCategory(user, 'landlord')) {
    return res.status(403).json({ error: 'টু-লেট বিজ্ঞাপন দিতে অনুমোদিত বাড়িওয়ালা (Landlord) ক্যাটাগরি প্রয়োজন।' });
  }
  const title = String(req.body.title || '').trim();
  const category = String(req.body.category || 'family_apartment');
  const monthlyRent = Number(req.body.monthly_rent || 0);
  const advance = Number(req.body.advance_amount || 0);
  const location = String(req.body.location || '').trim();
  const upazila = String(req.body.upazila || 'Gazipur Sadar').trim();
  const bedrooms = Number(req.body.bedrooms || 2);
  const bathrooms = Number(req.body.bathrooms || 2);
  const floor = String(req.body.floor_number || '').trim();
  const facilities = String(req.body.facilities || '').trim();
  const availableFrom = String(req.body.available_from || 'আগামী মাস').trim();
  const phone = String(req.body.contact_phone || '').trim();
  const hideContact = req.body.hide_contact_public ? 1 : 0;
  const description = String(req.body.description || '').trim();
  const imagePath = req.body.image_path ? String(req.body.image_path) : null;

  if (!title || monthlyRent <= 0 || !location || !phone) {
    return res.status(400).json({ error: 'ভাড়া, অবস্থান ও যোগাযোগের তথ্য সঠিকভাবে দিন।' });
  }
  const mod = checkModeration(`${title}\n${description}`);
  const status = mod.flagged ? 'pending_moderation' : 'published';
  db.prepare('INSERT INTO rental_listings (user_id, category, title, description, monthly_rent, advance_amount, location, upazila, bedrooms, bathrooms, floor_number, facilities, available_from, contact_phone, hide_contact_public, image_path, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)').run(user.id, category, title, description, monthlyRent, advance, location, upazila, bedrooms, bathrooms, floor, facilities, availableFrom, phone, hideContact, imagePath, status);
  res.json({ status, message: mod.flagged ? 'টু-লেট মডারেশনের জন্য অপেক্ষমাণ।' : 'টু-লেট বিজ্ঞাপন প্রকাশিত হয়েছে।' });
});

// --- JOBS / RECRUITMENT ---
app.get('/api/jobs', (req, res) => {
  const q = String(req.query.q || '').trim();
  const jobType = String(req.query.job_type || '').trim();
  const upazila = String(req.query.upazila || '').trim();
  const clauses = ["j.status = 'published'"];
  const params: unknown[] = [];
  if (q) {
    clauses.push('(j.job_title LIKE ? OR j.company_name LIKE ? OR j.location LIKE ? OR j.description LIKE ?)');
    params.push(`%${q}%`, `%${q}%`, `%${q}%`, `%${q}%`);
  }
  if (jobType) {
    clauses.push('j.job_type = ?');
    params.push(jobType);
  }
  if (upazila) {
    clauses.push('j.upazila = ?');
    params.push(upazila);
  }
  const jobs = db.prepare(`
    SELECT j.*, u.full_name AS poster_name
    FROM job_listings j JOIN users u ON u.id = j.user_id
    WHERE ${clauses.join(' AND ')}
    ORDER BY j.created_at DESC
  `).all(...params);
  res.json({ jobs });
});

app.post('/api/jobs', requireAuth, (req, res) => {
  const user = (req as Request & { authUser: AuthUser }).authUser;
  if (!hasApprovedCategory(user, 'job_poster')) {
    return res.status(403).json({ error: 'চাকরির বিজ্ঞপ্তি পোস্ট করতে অ্যাডমিন কর্তৃক অনুমোদিত চাকরিদাতা (Job Poster) রোল প্রয়োজন।' });
  }
  const companyName = String(req.body.company_name || '').trim();
  const jobTitle = String(req.body.job_title || '').trim();
  const allowedTypes = ['full_time', 'part_time', 'contract', 'internship', 'remote'];
  const jobType = allowedTypes.includes(String(req.body.job_type || '')) ? String(req.body.job_type) : 'full_time';
  const salary = String(req.body.salary || '').trim();
  const location = String(req.body.location || '').trim();
  const upazila = String(req.body.upazila || 'Gazipur Sadar').trim();
  const contactInfo = String(req.body.contact_info || '').trim();
  const deadline = String(req.body.deadline || '').trim();
  const description = String(req.body.description || '').trim();
  const imagePath = req.body.image_path ? String(req.body.image_path) : null;

  if (!companyName || !jobTitle || !salary || !location || contactInfo.length < 6) {
    return res.status(400).json({ error: 'কোম্পানির নাম, পদবি, বেতন, লোকেশন এবং যোগাযোগ নম্বর বা ইমেইল সঠিকভাবে দিন।' });
  }
  const mod = checkModeration(`${companyName}\n${jobTitle}\n${description}`);
  const status = mod.flagged ? 'pending_moderation' : 'published';
  db.prepare('INSERT INTO job_listings (user_id, company_name, job_title, job_type, salary, location, upazila, contact_info, deadline, description, image_path, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)').run(
    user.id,
    companyName,
    jobTitle,
    jobType,
    salary,
    location,
    upazila,
    contactInfo,
    deadline,
    description || `${jobTitle} — ${companyName}`,
    imagePath,
    status
  );
  return res.json({ status, message: mod.flagged ? 'চাকরির বিজ্ঞপ্তি মডারেশন পর্যালোচনায় পাঠানো হয়েছে।' : 'চাকরির বিজ্ঞপ্তি সফলভাবে প্রকাশিত হয়েছে।' });
});

// --- EDUCATION ---
app.get('/api/education', (req, res) => {
  const type = String(req.query.type || '').trim();
  const upazila = String(req.query.upazila || '').trim();
  const clauses = ["status = 'published'"];
  const params: unknown[] = [];
  if (type) { clauses.push('type = ?'); params.push(type); }
  if (upazila) { clauses.push('upazila = ?'); params.push(upazila); }
  const institutions = db.prepare(`SELECT * FROM education_institutions WHERE ${clauses.join(' AND ')} ORDER BY id DESC`).all(...params);
  res.json({ institutions });
});

app.post('/api/education', requireAuth, (req, res) => {
  const user = (req as Request & { authUser: AuthUser }).authUser;
  if (!hasApprovedCategory(user, 'educational_institution')) {
    return res.status(403).json({ error: 'শিক্ষা প্রতিষ্ঠান যুক্ত করতে অনুমোদিত Educational Institution ক্যাটাগরি প্রয়োজন।' });
  }
  const { name, type, upazila, address, phone, website, courses, description } = req.body;
  if (!name || !address || !description) return res.status(400).json({ error: 'নাম, ঠিকানা ও বিবরণ দিন।' });
  db.prepare('INSERT INTO education_institutions (user_id, type, name, upazila, address, phone, website, courses, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)').run(user.id, type || 'school', String(name).trim(), upazila || 'Gazipur Sadar', String(address).trim(), phone || '', website || '', courses || '', String(description).trim());
  res.json({ ok: true, message: 'শিক্ষা প্রতিষ্ঠানটি যুক্ত হয়েছে।' });
});

// --- BLOOD DONORS & REQUESTS ---
app.get('/api/blood', (req, res) => {
  const user = getAuthUser(req);
  const group = String(req.query.group || '').trim();
  const upazila = String(req.query.upazila || '').trim();
  const clauses = ['1=1'];
  const params: unknown[] = [];
  if (group) { clauses.push('blood_group = ?'); params.push(group); }
  if (upazila) { clauses.push('upazila = ?'); params.push(upazila); }

  const rawDonors = db.prepare(`SELECT * FROM blood_donors WHERE ${clauses.join(' AND ')} ORDER BY is_available DESC, id DESC`).all(...params) as Record<string, unknown>[];
  const donors = rawDonors.map((d) => ({
    ...d,
    phone: (d.consent_public_contact && user) ? d.phone : null,
  }));
  const requests = db.prepare("SELECT * FROM blood_requests WHERE status = 'open' ORDER BY id DESC").all() as Record<string, unknown>[];
  res.json({
    donors,
    requests: requests.map((r) => ({ ...r, contact_phone: user ? r.contact_phone : null })),
  });
});

app.post('/api/blood/donors', requireAuth, (req, res) => {
  const user = (req as Request & { authUser: AuthUser }).authUser;
  const { donor_name, blood_group, area, upazila, phone, contact_preference, consent_public_contact, last_donation_date } = req.body;
  if (!area || !phone || !blood_group) return res.status(400).json({ error: 'রক্তের গ্রুপ, এলাকা ও ফোন নম্বর দিন।' });
  db.prepare('INSERT INTO blood_donors (user_id, donor_name, blood_group, area, upazila, phone, contact_preference, consent_public_contact, last_donation_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)').run(user.id, donor_name || user.full_name, blood_group, area, upazila || user.upazila, phone, contact_preference || 'call', consent_public_contact ? 1 : 0, last_donation_date || '');
  res.json({ ok: true, message: 'রক্তদাতা হিসেবে আপনার নাম তালিকাভুক্ত হয়েছে।' });
});

app.post('/api/blood/requests', requireAuth, (req, res) => {
  const user = (req as Request & { authUser: AuthUser }).authUser;
  const { patient_name, blood_group, units_needed, hospital_name, upazila, needed_date, contact_phone, notes } = req.body;
  if (!patient_name || !hospital_name || !contact_phone) return res.status(400).json({ error: 'রোগীর নাম, হাসপাতাল ও যোগাযোগ নম্বর দিন।' });
  db.prepare('INSERT INTO blood_requests (user_id, patient_name, blood_group, units_needed, hospital_name, upazila, needed_date, contact_phone, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)').run(user.id, patient_name, blood_group || 'O+', Number(units_needed || 1), hospital_name, upazila || 'Gazipur Sadar', needed_date || 'জরুরি', contact_phone, notes || '');
  res.json({ ok: true, message: 'রক্তের অনুরোধ প্রকাশিত হয়েছে।' });
});

// --- BUSINESSES ---
app.get('/api/businesses', (req, res) => {
  const cat = String(req.query.cat || '').trim();
  const upazila = String(req.query.upazila || '').trim();
  const clauses = ["status = 'published'"];
  const params: unknown[] = [];
  if (cat) { clauses.push('category = ?'); params.push(cat); }
  if (upazila) { clauses.push('upazila = ?'); params.push(upazila); }
  const businesses = db.prepare(`SELECT * FROM businesses WHERE ${clauses.join(' AND ')} ORDER BY is_featured DESC, id DESC`).all(...params);
  res.json({ businesses });
});

app.post('/api/businesses', requireAuth, (req, res) => {
  const user = (req as Request & { authUser: AuthUser }).authUser;
  if (!hasApprovedCategory(user, 'business_owner')) {
    return res.status(403).json({ error: 'ব্যবসা যুক্ত করতে অনুমোদিত Business Owner ক্যাটাগরি প্রয়োজন।' });
  }
  const { name, category, address, upazila, phone, hours, description, map_coords } = req.body;
  if (!name || !address || !phone) return res.status(400).json({ error: 'ব্যবসার নাম, ঠিকানা ও ফোন নম্বর দিন।' });
  db.prepare('INSERT INTO businesses (user_id, category, name, address, upazila, phone, hours, description, map_coords) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)').run(user.id, category || 'services', name, address, upazila || 'Gazipur Sadar', phone, hours || '', description || '', map_coords || '');
  res.json({ ok: true, message: 'ব্যবসা প্রতিষ্ঠান যুক্ত হয়েছে।' });
});

// --- USER DASHBOARD ---
app.get('/api/dashboard', requireAuth, (req, res) => {
  const user = (req as Request & { authUser: AuthUser }).authUser;
  const myPosts = db.prepare('SELECT * FROM posts WHERE user_id = ? ORDER BY created_at DESC').all(user.id);
  const myMarket = db.prepare('SELECT * FROM marketplace_listings WHERE user_id = ? ORDER BY created_at DESC').all(user.id);
  const myRentals = db.prepare('SELECT * FROM rental_listings WHERE user_id = ? ORDER BY created_at DESC').all(user.id);
  const myJobs = db.prepare('SELECT * FROM job_listings WHERE user_id = ? ORDER BY created_at DESC').all(user.id);
  const myComments = db.prepare('SELECT c.*, p.title AS post_title FROM comments c JOIN posts p ON p.id = c.post_id WHERE c.user_id = ? ORDER BY c.created_at DESC').all(user.id);
  const myLikes = db.prepare('SELECT l.created_at, p.id, p.title FROM likes l JOIN posts p ON p.id = l.post_id WHERE l.user_id = ? ORDER BY l.created_at DESC').all(user.id);
  const notifications = db.prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC').all(user.id);
  res.json({ user, myPosts, myMarket, myRentals, myJobs, myComments, myLikes, notifications });
});

app.put('/api/dashboard/profile', requireAuth, (req, res) => {
  const user = (req as Request & { authUser: AuthUser }).authUser;
  const fullName = String(req.body.full_name || '').trim();
  const phone = String(req.body.phone || '').trim();
  const upazila = String(req.body.upazila || 'Gazipur Sadar').trim();
  const bio = String(req.body.bio || '').trim();
  if (!fullName) return res.status(400).json({ error: 'নাম খালি রাখা যাবে না।' });
  db.prepare('UPDATE users SET full_name = ?, phone = ?, upazila = ?, bio = ? WHERE id = ?').run(fullName, phone, upazila, bio, user.id);
  res.json({ user: loadUser(user.id) });
});

app.post('/api/dashboard/notifications/read', requireAuth, (req, res) => {
  const user = (req as Request & { authUser: AuthUser }).authUser;
  db.prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?').run(user.id);
  res.json({ ok: true });
});

// --- ADMIN DASHBOARD API ---
app.get('/api/admin/overview', requireAdmin, (_req, res) => {
  const stats = {
    total_users: (db.prepare('SELECT COUNT(*) as c FROM users').get() as { c: number }).c,
    active_users: (db.prepare("SELECT COUNT(*) as c FROM users WHERE status = 'active'").get() as { c: number }).c,
    pending_categories: (db.prepare("SELECT COUNT(*) as c FROM user_categories WHERE status = 'pending'").get() as { c: number }).c,
    total_posts: (db.prepare('SELECT COUNT(*) as c FROM posts').get() as { c: number }).c,
    pending_posts: (db.prepare("SELECT COUNT(*) as c FROM posts WHERE status = 'pending_moderation'").get() as { c: number }).c,
    total_news: (db.prepare('SELECT COUNT(*) as c FROM news').get() as { c: number }).c,
    pending_news: (db.prepare("SELECT COUNT(*) as c FROM news WHERE status = 'pending'").get() as { c: number }).c,
    marketplace: (db.prepare('SELECT COUNT(*) as c FROM marketplace_listings').get() as { c: number }).c,
    rentals: (db.prepare('SELECT COUNT(*) as c FROM rental_listings').get() as { c: number }).c,
    jobs: (db.prepare('SELECT COUNT(*) as c FROM job_listings').get() as { c: number }).c,
    open_reports: (db.prepare("SELECT COUNT(*) as c FROM reports WHERE status = 'open'").get() as { c: number }).c,
    active_ads: (db.prepare("SELECT COUNT(*) as c FROM advertisements WHERE status = 'active'").get() as { c: number }).c,
  };
  const logs = db.prepare('SELECT al.*, u.full_name AS admin_name FROM admin_logs al JOIN users u ON u.id = al.admin_id ORDER BY al.created_at DESC LIMIT 20').all();
  res.json({ stats, logs });
});

app.get('/api/admin/users', requireAdmin, (_req, res) => {
  const users = db.prepare('SELECT id, role, full_name, email, upazila, status, created_at FROM users ORDER BY created_at DESC').all() as Record<string, unknown>[];
  for (const u of users) {
    u.categories = db.prepare('SELECT * FROM user_categories WHERE user_id = ?').all(u.id);
  }
  res.json({ users });
});

app.post('/api/admin/users/:id/status', requireAdmin, (req, res) => {
  const admin = (req as Request & { authUser: AuthUser }).authUser;
  const userId = Number(req.params.id);
  const { status, role } = req.body;
  db.prepare('UPDATE users SET status = ?, role = ? WHERE id = ?').run(status, role, userId);
  db.prepare("INSERT INTO admin_logs (admin_id, action, target_type, target_id, details) VALUES (?, 'update_user', 'user', ?, ?)").run(admin.id, userId, `${role}/${status}`);
  res.json({ ok: true });
});

app.post('/api/admin/categories/:id/review', requireAdmin, (req, res) => {
  const admin = (req as Request & { authUser: AuthUser }).authUser;
  const catRowId = Number(req.params.id);
  const decision = String(req.body.decision || 'approved');
  const row = db.prepare('SELECT * FROM user_categories WHERE id = ?').get(catRowId) as { user_id: number; category_slug: string } | undefined;
  if (!row) return res.status(404).json({ error: 'Not found' });
  db.prepare("UPDATE user_categories SET status = ?, reviewed_by = ?, reviewed_at = datetime('now') WHERE id = ?").run(decision, admin.id, catRowId);
  db.prepare('INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)').run(
    row.user_id,
    'ক্যাটাগরি অনুমোদন আপডেট',
    `আপনার "${row.category_slug}" ক্যাটাগরিটি ${decision === 'approved' ? 'অনুমোদিত' : 'প্রত্যাখ্যাত'} হয়েছে।`
  );
  db.prepare("INSERT INTO admin_logs (admin_id, action, target_type, target_id, details) VALUES (?, ?, 'user_category', ?, ?)").run(admin.id, `category_${decision}`, catRowId, row.category_slug);
  res.json({ ok: true });
});

app.get('/api/admin/news', requireAdmin, (_req, res) => {
  const articles = db.prepare(`
    SELECT n.*, c.name_bn AS category_bn, u.full_name AS author_name
    FROM news n JOIN news_categories c ON c.id = n.category_id JOIN users u ON u.id = n.author_id
    ORDER BY CASE n.status WHEN 'pending' THEN 0 ELSE 1 END, n.created_at DESC
  `).all();
  res.json({ articles });
});

app.post('/api/admin/news/:id/review', requireAdmin, (req, res) => {
  const admin = (req as Request & { authUser: AuthUser }).authUser;
  const newsId = Number(req.params.id);
  const decision = String(req.body.decision || 'published');
  const isFeatured = req.body.is_featured ? 1 : 0;
  db.prepare('UPDATE news SET status = ?, is_featured = ?, approved_by = ? WHERE id = ?').run(decision, isFeatured, admin.id, newsId);
  db.prepare("INSERT INTO admin_logs (admin_id, action, target_type, target_id, details) VALUES (?, ?, 'news', ?, ?)").run(admin.id, `news_${decision}`, newsId, '');
  res.json({ ok: true });
});

app.get('/api/admin/moderation', requireAdmin, (_req, res) => {
  const flaggedPosts = db.prepare("SELECT p.*, u.full_name AS author_name FROM posts p JOIN users u ON u.id = p.user_id WHERE p.status IN ('pending_moderation', 'hidden') ORDER BY p.created_at DESC").all();
  const flaggedMarket = db.prepare("SELECT m.*, u.full_name AS seller_name FROM marketplace_listings m JOIN users u ON u.id = m.user_id WHERE m.status = 'pending_moderation'").all();
  const flaggedRentals = db.prepare("SELECT r.*, u.full_name AS landlord_name FROM rental_listings r JOIN users u ON u.id = r.user_id WHERE r.status = 'pending_moderation'").all();
  const rules = db.prepare('SELECT * FROM moderation_rules ORDER BY id DESC').all();
  const reports = db.prepare('SELECT r.*, u.full_name AS reporter_name FROM reports r JOIN users u ON u.id = r.reporter_id ORDER BY r.created_at DESC').all();
  const moderationLogs = db.prepare('SELECT * FROM moderation_logs ORDER BY created_at DESC LIMIT 30').all();
  res.json({ flaggedPosts, flaggedMarket, flaggedRentals, rules, reports, moderationLogs });
});

app.post('/api/admin/moderation/posts/:id', requireAdmin, (req, res) => {
  const admin = (req as Request & { authUser: AuthUser }).authUser;
  const postId = Number(req.params.id);
  const status = String(req.body.status || 'published');
  db.prepare('UPDATE posts SET status = ? WHERE id = ?').run(status, postId);
  db.prepare("INSERT INTO moderation_logs (target_type, target_id, user_id, action_taken) VALUES ('post', ?, ?, ?)").run(postId, admin.id, status);
  res.json({ ok: true });
});

app.post('/api/admin/moderation/rules', requireAdmin, (req, res) => {
  const admin = (req as Request & { authUser: AuthUser }).authUser;
  const keyword = String(req.body.keyword || '').trim();
  const matchType = String(req.body.match_type || 'word');
  if (!keyword) return res.status(400).json({ error: 'শব্দ লিখুন।' });
  db.prepare('INSERT OR IGNORE INTO moderation_rules (keyword, match_type, severity, is_active) VALUES (?, ?, ?, 1)').run(keyword, matchType, 'flag');
  db.prepare("INSERT INTO admin_logs (admin_id, action, target_type, target_id, details) VALUES (?, 'add_moderation_rule', 'rule', 0, ?)").run(admin.id, keyword);
  res.json({ ok: true });
});

app.delete('/api/admin/moderation/rules/:id', requireAdmin, (req, res) => {
  db.prepare('DELETE FROM moderation_rules WHERE id = ?').run(Number(req.params.id));
  res.json({ ok: true });
});

app.post('/api/admin/moderation/reports/:id', requireAdmin, (req, res) => {
  const status = String(req.body.status || 'resolved');
  db.prepare('UPDATE reports SET status = ? WHERE id = ?').run(status, Number(req.params.id));
  res.json({ ok: true });
});

app.get('/api/admin/ads', requireAdmin, (_req, res) => {
  const ads = db.prepare('SELECT * FROM advertisements ORDER BY id DESC').all();
  res.json({ ads });
});

app.post('/api/admin/ads', requireAdmin, (req, res) => {
  const admin = (req as Request & { authUser: AuthUser }).authUser;
  const { title, placement, banner_image, target_url, start_date, end_date, status } = req.body;
  db.prepare('INSERT INTO advertisements (title, placement, banner_image, target_url, start_date, end_date, status) VALUES (?, ?, ?, ?, ?, ?, ?)').run(title, placement || 'home_banner', banner_image || '', target_url || '#', start_date || '2026-01-01', end_date || '2027-12-31', status || 'active');
  db.prepare("INSERT INTO admin_logs (admin_id, action, target_type, target_id, details) VALUES (?, 'create_ad', 'ad', 0, ?)").run(admin.id, title);
  res.json({ ok: true });
});

app.put('/api/admin/ads/:id', requireAdmin, (req, res) => {
  db.prepare('UPDATE advertisements SET status = ? WHERE id = ?').run(String(req.body.status || 'active'), Number(req.params.id));
  res.json({ ok: true });
});

app.put('/api/admin/settings', requireAdmin, (req, res) => {
  const admin = (req as Request & { authUser: AuthUser }).authUser;
  const stmt = db.prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON CONFLICT(setting_key) DO UPDATE SET setting_value = excluded.setting_value');
  for (const [k, v] of Object.entries(req.body || {})) {
    stmt.run(k, String(v));
  }
  db.prepare("INSERT INTO admin_logs (admin_id, action, target_type, target_id, details) VALUES (?, 'update_settings', 'settings', 0, '')").run(admin.id);
  res.json({ ok: true });
});

async function startServer() {
  if (process.env.NODE_ENV !== 'production') {
    const vite = await createViteServer({
      server: { middlewareMode: true },
      appType: 'spa',
    });
    app.use(vite.middlewares);
  } else {
    app.use(express.static(path.resolve(process.cwd(), 'dist')));
    app.get('*', (_req, res) => {
      res.sendFile(path.resolve(process.cwd(), 'dist/index.html'));
    });
  }

  app.listen(PORT, '0.0.0.0', () => {
    console.log(`AmarGazipur server running at http://localhost:${PORT}`);
  });
}

startServer();
