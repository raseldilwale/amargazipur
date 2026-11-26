# AmarGazipur — Complete District-Based Digital Community Platform (PHP 8.1+ / MySQL)

AmarGazipur is a production-ready digital community platform built specifically for **Gazipur District, Bangladesh**, engineered to run seamlessly on **ordinary cPanel shared hosting** (Apache + PHP 8.1+ + MySQL / MariaDB).

## Key Features
- **Public Browsing Without Login**: Visitors can read Gazipur local news, browse community posts, marketplace listings, house rentals (To-Let), educational institutions, blood donors, and business listings freely. Protected actions (like, comment, create post, contact reveal) prompt visitors with a clean login/register modal.
- **Multi-Category Registration & Admin Approval**: Users can select multiple categories (`General User`, `Student`, `Journalist`, `Landlord`, `Business Owner`, `Educational Institution`, `Blood Donor`, `Organization`). Categories start as `pending` until Admin reviews them individually.
- **Journalist News Workflow**: Only approved Journalists can submit news, and every journalist submission stays `pending` until an Admin or Editor publishes it.
- **Content & Keyword Moderation**: Configurable prohibited-word engine with word-boundary matching, link heuristics, user reporting, and manual Admin review queue.
- **Separate Admin Console**: Dedicated RBAC Admin Dashboard (`/admin`) for managing users, categories, news approvals, moderation rules, ads, SEO, and activity logs.
- **Instant Config & Auto-Start**: Edit `config/database.php` with your MySQL credentials (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`) and visit your domain—all 28 tables and starter Gazipur content initialize automatically on the first visit, and `/admin` lets you create your first Admin account.

## Quick Setup
1. Upload `AmarGazipur_Full_Website.zip` into `public_html/` (or your subdomain folder) in cPanel File Manager and Extract it.
2. Create a MySQL database and user in cPanel -> MySQL Databases (grant ALL PRIVILEGES).
3. Edit `config/database.php` and set `DB_NAME`, `DB_USER`, and `DB_PASS`.
4. Visit `https://yourdomain.com/` to launch the website immediately, then visit `https://yourdomain.com/admin` to create your first Admin account.
5. See `docs/CPANEL_DEPLOYMENT_GUIDE_BN.md` for detailed Bangla instructions.
