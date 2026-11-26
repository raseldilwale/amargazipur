import React, { useEffect, useState } from 'react';
import { ArrowRight, Briefcase, Building2, Droplets, ShoppingBag, Menu, X } from 'lucide-react';
import {
  ActivePage,
  Advertisement,
  CommunityPost,
  Lang,
  MarketplaceCategory,
  NewsArticle,
  NewsCategory,
  RentalListing,
  TEXT,
  User,
} from './types';
import { AuthModal } from './components/AuthModal';
import { CommunitySection } from './components/CommunitySection';
import { NewsSection } from './components/NewsSection';
import { MarketRentSection } from './components/MarketRentSection';
import { ServicesSection } from './components/ServicesSection';
import { UserDashboard } from './components/UserDashboard';
import { AdminConsole } from './components/AdminConsole';

export default function App() {
  const [lang, setLang] = useState<Lang>('bn');
  const [page, setPage] = useState<ActivePage>('home');
  const [servicesSubTab, setServicesSubTab] = useState<'education' | 'blood' | 'businesses'>('education');
  const [marketRentSubTab, setMarketRentSubTab] = useState<'marketplace' | 'rentals' | 'jobs'>('marketplace');
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

  // Auth held strictly in React memory (never localStorage)
  const [token, setToken] = useState<string | null>(null);
  const [user, setUser] = useState<User | null>(null);
  const [adminClaimed, setAdminClaimed] = useState(true);

  const [authModalOpen, setAuthModalOpen] = useState(false);
  const [authModalMode, setAuthModalMode] = useState<'prompt' | 'login' | 'register'>('prompt');

  // Bootstrap & Home data
  const [newsCategories, setNewsCategories] = useState<NewsCategory[]>([]);
  const [marketCategories, setMarketCategories] = useState<MarketplaceCategory[]>([]);
  const [activeAds, setActiveAds] = useState<Advertisement[]>([]);
  const [homeNews, setHomeNews] = useState<NewsArticle[]>([]);
  const [homePosts, setHomePosts] = useState<CommunityPost[]>([]);
  const [homeRentals, setHomeRentals] = useState<RentalListing[]>([]);

  const t = TEXT[lang];

  const loadBootstrapAndHome = async () => {
    try {
      const [bootRes, newsRes, postRes, rentRes] = await Promise.all([
        fetch('/api/bootstrap', { headers: token ? { Authorization: `Bearer ${token}` } : {} }),
        fetch('/api/news'),
        fetch('/api/posts'),
        fetch('/api/rentals'),
      ]);
      if (bootRes.ok) {
        const b = await bootRes.json();
        setAdminClaimed(Boolean(b.adminClaimed));
        setNewsCategories(b.newsCategories || []);
        setMarketCategories(b.marketCategories || []);
        setActiveAds(b.activeAds || []);
        if (b.user) setUser(b.user);
      }
      if (newsRes.ok) setHomeNews(((await newsRes.json()).articles || []).slice(0, 3));
      if (postRes.ok) setHomePosts(((await postRes.json()).posts || []).slice(0, 3));
      if (rentRes.ok) setHomeRentals(((await rentRes.json()).rentals || []).slice(0, 3));
    } catch {
      // keep safe state
    }
  };

  useEffect(() => {
    loadBootstrapAndHome();
  }, [token]);

  const openAuthPrompt = (mode: 'prompt' | 'login' | 'register' = 'prompt') => {
    setAuthModalMode(mode);
    setAuthModalOpen(true);
  };

  const handleAuthSuccess = (newToken: string, loggedUser: User) => {
    setToken(newToken);
    setUser(loggedUser);
    if (['admin', 'editor', 'moderator'].includes(loggedUser.role)) {
      setAdminClaimed(true);
    }
  };

  const handleLogout = () => {
    setToken(null);
    setUser(null);
    setPage('home');
  };

  if (page === 'admin') {
    return (
      <>
        <AdminConsole
          lang={lang}
          user={user}
          token={token}
          adminClaimed={adminClaimed}
          onAdminClaimed={(newTok, admUser) => {
            setToken(newTok);
            setUser(admUser);
            setAdminClaimed(true);
          }}
          onRequireLogin={() => openAuthPrompt('login')}
          onExitAdmin={() => setPage('home')}
        />
        <AuthModal
          isOpen={authModalOpen}
          initialMode={authModalMode}
          lang={lang}
          onClose={() => setAuthModalOpen(false)}
          onSuccess={handleAuthSuccess}
        />
      </>
    );
  }

  return (
    <div className="min-h-screen flex flex-col bg-slate-50 text-slate-900">
      {/* Strict 3-Zone Top Bar Contract */}
      <header className="sticky top-0 z-40 bg-white/95 backdrop-blur-xs border-b border-slate-200">
        <div className="max-w-6xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between gap-8">
          {/* Zone 1: Single text wordmark */}
          <button
            type="button"
            onClick={() => { setPage('home'); setMobileMenuOpen(false); }}
            className="text-lg font-bold tracking-tight text-slate-900 whitespace-nowrap shrink-0 cursor-pointer"
          >
            {t.brand}
          </button>

          {/* Zone 2: 5 concise single-line nav links */}
          <nav className="hidden md:flex items-center gap-6 text-sm font-medium text-slate-600">
            <button
              type="button"
              onClick={() => setPage('home')}
              className={`whitespace-nowrap shrink-0 transition-colors hover:text-slate-900 ${page === 'home' ? 'text-emerald-900 font-semibold' : ''}`}
            >
              {t.navHome}
            </button>
            <button
              type="button"
              onClick={() => setPage('news')}
              className={`whitespace-nowrap shrink-0 transition-colors hover:text-slate-900 ${page === 'news' ? 'text-emerald-900 font-semibold' : ''}`}
            >
              {t.navNews}
            </button>
            <button
              type="button"
              onClick={() => setPage('community')}
              className={`whitespace-nowrap shrink-0 transition-colors hover:text-slate-900 ${page === 'community' ? 'text-emerald-900 font-semibold' : ''}`}
            >
              {t.navCommunity}
            </button>
            <button
              type="button"
              onClick={() => setPage('market_rent')}
              className={`whitespace-nowrap shrink-0 transition-colors hover:text-slate-900 ${page === 'market_rent' ? 'text-emerald-900 font-semibold' : ''}`}
            >
              {t.navMarketRent}
            </button>
            <button
              type="button"
              onClick={() => { setServicesSubTab('education'); setPage('services'); }}
              className={`whitespace-nowrap shrink-0 transition-colors hover:text-slate-900 ${page === 'services' ? 'text-emerald-900 font-semibold' : ''}`}
            >
              {t.navServices}
            </button>
          </nav>

          {/* Zone 3: 1 primary action */}
          <div className="flex items-center gap-2 shrink-0">
            {user ? (
              <button
                type="button"
                onClick={() => setPage('dashboard')}
                className="rounded-lg bg-emerald-800 px-4 py-2 text-xs font-semibold text-white hover:bg-emerald-900 whitespace-nowrap shrink-0"
              >
                {t.dashboardBtn}
              </button>
            ) : (
              <button
                type="button"
                onClick={() => openAuthPrompt('login')}
                className="rounded-lg bg-emerald-800 px-4 py-2 text-xs font-semibold text-white hover:bg-emerald-900 whitespace-nowrap shrink-0"
              >
                {t.loginBtn}
              </button>
            )}
            <button
              type="button"
              onClick={() => setMobileMenuOpen((v) => !v)}
              className="md:hidden rounded-lg p-2 text-slate-600 hover:bg-slate-100"
              aria-label="Toggle Menu"
            >
              {mobileMenuOpen ? <X className="h-5 w-5" /> : <Menu className="h-5 w-5" />}
            </button>
          </div>
        </div>

        {/* Mobile Drawer */}
        {mobileMenuOpen && (
          <div className="md:hidden border-t border-slate-200 bg-white px-4 py-3 space-y-2 text-sm">
            {[
              { id: 'home', label: t.navHome },
              { id: 'news', label: t.navNews },
              { id: 'community', label: t.navCommunity },
              { id: 'market_rent', label: t.navMarketRent },
              { id: 'services', label: t.navServices },
            ].map((item) => (
              <button
                key={item.id}
                type="button"
                onClick={() => { setPage(item.id as ActivePage); setMobileMenuOpen(false); }}
                className="block w-full text-left py-2 font-medium text-slate-700 hover:text-emerald-900"
              >
                {item.label}
              </button>
            ))}
          </div>
        )}
      </header>

      <main className="flex-1">
        {page === 'home' && (
          <div className="max-w-6xl mx-auto px-4 sm:px-6 py-8 sm:py-12 space-y-12">
            {/* Utility strip below header */}
            <div className="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 pb-4 text-xs">
              <div className="text-slate-600 font-medium">{t.heroKicker}</div>
              <div className="flex items-center gap-4">
                <button
                  type="button"
                  onClick={() => setLang(lang === 'bn' ? 'en' : 'bn')}
                  className="font-semibold text-slate-700 hover:text-emerald-800 whitespace-nowrap"
                >
                  {lang === 'bn' ? 'Switch to English' : 'বাংলায় দেখুন'}
                </button>
                <span aria-hidden="true" className="text-slate-300">|</span>
                <button
                  type="button"
                  onClick={() => setPage('admin')}
                  className="font-semibold text-slate-700 hover:text-slate-900 whitespace-nowrap"
                >
                  {lang === 'bn' ? 'অ্যাডমিন প্যানেল' : 'Admin Console'}
                </button>
              </div>
            </div>

            {/* Hero Split Section */}
            <section className="grid grid-cols-1 lg:grid-cols-12 gap-8 items-stretch">
              <div className="lg:col-span-6 flex flex-col justify-center">
                <h1 className="text-3xl sm:text-5xl font-bold text-slate-900 tracking-tight leading-tight mb-4 [text-wrap:balance]">
                  {t.heroTitle}
                </h1>
                <p className="text-base text-slate-600 leading-relaxed mb-6 max-w-xl">
                  {t.heroSub}
                </p>
                <div className="flex flex-wrap items-center gap-3">
                  <button
                    type="button"
                    onClick={() => setPage('news')}
                    className="inline-flex items-center gap-2 rounded-lg bg-emerald-800 px-5 py-3 text-sm font-semibold text-white hover:bg-emerald-900 whitespace-nowrap"
                  >
                    <span>{t.heroCtaPrimary}</span>
                    <ArrowRight className="h-4 w-4" />
                  </button>
                  <button
                    type="button"
                    onClick={() => setPage('community')}
                    className="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-800 hover:bg-slate-50 whitespace-nowrap"
                  >
                    <span>{t.heroCtaZip}</span>
                  </button>
                </div>
              </div>

              <div className="lg:col-span-6 min-h-[280px] lg:min-h-[380px] rounded-xl overflow-hidden border border-slate-200 bg-slate-200">
                <img
                  src="/src/assets/images/hero_gazipur_community_1795729662480.jpg"
                  alt="Gazipur District Bhawal and City"
                  referrerPolicy="no-referrer"
                  className="w-full h-full object-cover"
                />
              </div>
            </section>

            {/* Quick District Modules Strip */}
            <section className="grid grid-cols-2 sm:grid-cols-4 gap-4">
              {[
                {
                  icon: ShoppingBag,
                  title: lang === 'bn' ? 'মার্কেটপ্লেস' : 'Marketplace',
                  sub: lang === 'bn' ? 'স্থানীয় কেনাবেচা' : 'Buy & sell locally',
                  action: () => { setMarketRentSubTab('marketplace'); setPage('market_rent'); },
                },
                {
                  icon: Building2,
                  title: lang === 'bn' ? 'বাসা ভাড়া / টু-লেট' : 'House Rent',
                  sub: lang === 'bn' ? 'ফ্যামিলি ও মেস' : 'Flats, hostels & shops',
                  action: () => { setMarketRentSubTab('rentals'); setPage('market_rent'); },
                },
                {
                  icon: Briefcase,
                  title: lang === 'bn' ? 'চাকরি ও নিয়োগ' : 'Gazipur Jobs',
                  sub: lang === 'bn' ? 'স্থানীয় চাকরির সুযোগ' : 'Local openings & hiring',
                  action: () => { setMarketRentSubTab('jobs'); setPage('market_rent'); },
                },
                {
                  icon: Droplets,
                  title: lang === 'bn' ? 'রক্তদাতা ও শিক্ষা' : 'Blood & Education',
                  sub: lang === 'bn' ? 'জরুরি সেবা ডিরেক্টরি' : 'Donors & campuses',
                  action: () => { setServicesSubTab('blood'); setPage('services'); },
                },
              ].map((card) => {
                const Icon = card.icon;
                return (
                  <button
                    key={card.title}
                    type="button"
                    onClick={card.action}
                    className="rounded-xl border border-slate-200 bg-white p-5 text-left hover:border-emerald-700 transition-colors"
                  >
                    <Icon className="h-5 w-5 text-emerald-800 mb-3" />
                    <div className="text-sm font-bold text-slate-900">{card.title}</div>
                    <div className="text-xs text-slate-500 mt-0.5">{card.sub}</div>
                  </button>
                );
              })}
            </section>

            {/* Active Advertisement */}
            {activeAds.length > 0 && (
              <section className="rounded-xl border border-slate-200 bg-white p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                  <div className="text-xs text-slate-500 mb-1">{lang === 'bn' ? 'স্পন্সরড বিজ্ঞাপন' : 'Sponsored Advertisement'}</div>
                  <div className="text-base font-bold text-slate-900">{activeAds[0].title}</div>
                </div>
                <button
                  type="button"
                  onClick={() => { setServicesSubTab('education'); setPage('services'); }}
                  className="rounded-lg border border-slate-300 px-4 py-2 text-xs font-semibold text-slate-800 hover:bg-slate-50 whitespace-nowrap"
                >
                  {lang === 'bn' ? 'বিস্তারিত দেখুন' : 'Learn More'}
                </button>
              </section>
            )}

            {/* Featured Gazipur News */}
            <section>
              <div className="flex items-center justify-between mb-5">
                <h2 className="text-xl font-bold text-slate-900">
                  {lang === 'bn' ? 'গাজীপুরের সাম্প্রতিক সংবাদ' : 'Latest Gazipur News'}
                </h2>
                <button type="button" onClick={() => setPage('news')} className="text-xs font-semibold text-emerald-800 hover:underline">
                  {lang === 'bn' ? 'সকল সংবাদ →' : 'All News →'}
                </button>
              </div>
              <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                {homeNews.map((art) => (
                  <article
                    key={art.id}
                    onClick={() => setPage('news')}
                    className="cursor-pointer rounded-xl border border-slate-200 bg-white overflow-hidden flex flex-col hover:border-slate-300"
                  >
                    <div className="h-44 bg-slate-100 overflow-hidden">
                      {art.featured_image && (
                        <img
                          src={art.featured_image}
                          alt={art.title}
                          referrerPolicy="no-referrer"
                          className="h-full w-full object-cover"
                          loading="lazy"
                        />
                      )}
                    </div>
                    <div className="p-5 flex-1 flex flex-col">
                      <div className="text-xs text-slate-500 mb-1.5">
                        {lang === 'bn' ? art.category_bn : art.category_en} · {art.upazila}
                      </div>
                      <h3 className="text-base font-bold text-slate-900 mb-2 leading-snug">{art.title}</h3>
                      <p className="text-sm text-slate-600 line-clamp-2 flex-1">{art.summary}</p>
                    </div>
                  </article>
                ))}
              </div>
            </section>

            {/* Community + Rentals Split */}
            <section className="grid grid-cols-1 lg:grid-cols-2 gap-8">
              <div className="rounded-xl border border-slate-200 bg-white p-6">
                <div className="flex items-center justify-between mb-4">
                  <h2 className="text-lg font-bold text-slate-900">{lang === 'bn' ? 'কমিউনিটি আলোচনা' : 'Community Feed'}</h2>
                  <button type="button" onClick={() => setPage('community')} className="text-xs font-semibold text-emerald-800 hover:underline">
                    {lang === 'bn' ? 'ফোরাম দেখুন →' : 'Open Forum →'}
                  </button>
                </div>
                <div className="space-y-4">
                  {homePosts.map((p) => (
                    <div key={p.id} className="border-b border-slate-100 pb-4 last:border-0 last:pb-0">
                      <div className="text-xs text-slate-500 mb-1">{p.category} · {p.upazila} · {p.author_name}</div>
                      <button type="button" onClick={() => setPage('community')} className="text-sm font-bold text-slate-900 hover:text-emerald-800 text-left">
                        {p.title}
                      </button>
                      <p className="text-xs text-slate-600 mt-1 line-clamp-2">{p.content}</p>
                    </div>
                  ))}
                </div>
              </div>

              <div className="rounded-xl border border-slate-200 bg-white p-6">
                <div className="flex items-center justify-between mb-4">
                  <h2 className="text-lg font-bold text-slate-900">{lang === 'bn' ? 'সাম্প্রতিক টু-লেট ও বাসা ভাড়া' : 'Recent Rentals & To-Let'}</h2>
                  <button type="button" onClick={() => setPage('market_rent')} className="text-xs font-semibold text-emerald-800 hover:underline">
                    {lang === 'bn' ? 'সকল ভাড়া →' : 'All Rentals →'}
                  </button>
                </div>
                <div className="space-y-4">
                  {homeRentals.map((r) => (
                    <div key={r.id} className="border-b border-slate-100 pb-4 last:border-0 last:pb-0">
                      <div className="text-xs text-slate-500 mb-1">{r.upazila} · {r.location}</div>
                      <button type="button" onClick={() => setPage('market_rent')} className="text-sm font-bold text-slate-900 hover:text-emerald-800 text-left">
                        {r.title}
                      </button>
                      <div className="text-xs font-semibold text-emerald-800 tabular-nums mt-1">
                        ৳ {r.monthly_rent.toLocaleString()} / {lang === 'bn' ? 'মাস' : 'mo'} · {r.bedrooms} Bed · {r.bathrooms} Bath
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            </section>
          </div>
        )}

        {page === 'news' && (
          <NewsSection
            lang={lang}
            user={user}
            token={token}
            categories={newsCategories}
            onRequireAuth={() => openAuthPrompt('prompt')}
          />
        )}

        {page === 'community' && (
          <CommunitySection
            lang={lang}
            user={user}
            token={token}
            onRequireAuth={() => openAuthPrompt('prompt')}
          />
        )}

        {page === 'market_rent' && (
          <MarketRentSection
            lang={lang}
            user={user}
            token={token}
            marketCategories={marketCategories}
            initialSubTab={marketRentSubTab}
            onRequireAuth={() => openAuthPrompt('prompt')}
          />
        )}

        {page === 'services' && (
          <ServicesSection
            lang={lang}
            user={user}
            token={token}
            initialSubTab={servicesSubTab}
            onRequireAuth={() => openAuthPrompt('prompt')}
          />
        )}

        {page === 'dashboard' && user && token && (
          <UserDashboard
            lang={lang}
            user={user}
            token={token}
            onUserUpdated={(updated) => setUser(updated)}
            onLogout={handleLogout}
            onOpenAdmin={() => setPage('admin')}
          />
        )}
      </main>

      {/* Quiet Footer */}
      <footer className="border-t border-slate-200 bg-white mt-12">
        <div className="max-w-6xl mx-auto px-4 sm:px-6 py-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-500">
          <div>
            © ২০২৬ AmarGazipur — {lang === 'bn' ? 'গাজীপুর জেলার ডিজিটাল কমিউনিটি প্ল্যাটফর্ম' : 'Gazipur District Digital Community Platform'}
          </div>
          <div className="flex flex-wrap items-center gap-4">
            <button type="button" onClick={() => setLang(lang === 'bn' ? 'en' : 'bn')} className="hover:text-slate-900">
              {lang === 'bn' ? 'English' : 'বাংলা'}
            </button>
            <button type="button" onClick={() => setPage('admin')} className="hover:text-slate-900">
              {lang === 'bn' ? 'অ্যাডমিন প্যানেল' : 'Admin Console'}
            </button>
          </div>
        </div>
      </footer>

      <AuthModal
        isOpen={authModalOpen}
        initialMode={authModalMode}
        lang={lang}
        onClose={() => setAuthModalOpen(false)}
        onSuccess={handleAuthSuccess}
      />
    </div>
  );
}
