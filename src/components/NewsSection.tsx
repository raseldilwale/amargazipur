import React, { useEffect, useState } from 'react';
import { Search, Newspaper, ArrowLeft } from 'lucide-react';
import { GAZIPUR_UPAZILAS, Lang, NewsArticle, NewsCategory, User } from '../types';

interface Props {
  lang: Lang;
  user: User | null;
  token: string | null;
  categories: NewsCategory[];
  onRequireAuth: () => void;
}

export const NewsSection: React.FC<Props> = ({ lang, user, token, categories }) => {
  const [articles, setArticles] = useState<NewsArticle[]>([]);
  const [selected, setSelected] = useState<NewsArticle | null>(null);
  const [cat, setCat] = useState('');
  const [q, setQ] = useState('');
  const [showSubmit, setShowSubmit] = useState(false);

  const [title, setTitle] = useState('');
  const [summary, setSummary] = useState('');
  const [content, setContent] = useState('');
  const [catId, setCatId] = useState(1);
  const [upazila, setUpazila] = useState('Gazipur Sadar');
  const [feedback, setFeedback] = useState('');
  const [error, setError] = useState('');

  const isJournalist =
    user &&
    (user.role === 'admin' ||
      user.role === 'editor' ||
      user.categories.some((c) => c.category_slug === 'journalist' && c.status === 'approved'));

  const loadNews = async () => {
    const params = new URLSearchParams();
    if (cat) params.set('cat', cat);
    if (q) params.set('q', q);
    const res = await fetch(`/api/news?${params.toString()}`);
    const data = await res.json();
    setArticles(data.articles || []);
  };

  useEffect(() => {
    loadNews();
  }, [cat]);

  const handleJournalistSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!token) return;
    setError('');
    setFeedback('');
    const res = await fetch('/api/news', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Authorization: `Bearer ${token}`,
      },
      body: JSON.stringify({ title, summary, content, category_id: catId, upazila }),
    });
    const data = await res.json();
    if (res.ok) {
      setTitle('');
      setSummary('');
      setContent('');
      setFeedback(data.message);
      setShowSubmit(false);
    } else {
      setError(data.error || 'Submission failed');
    }
  };

  if (selected) {
    const related = articles.filter((a) => a.id !== selected.id).slice(0, 3);
    return (
      <div className="max-w-5xl mx-auto px-4 sm:px-6 py-8">
        <button
          type="button"
          onClick={() => setSelected(null)}
          className="inline-flex items-center gap-2 text-sm font-medium text-emerald-800 hover:underline mb-6"
        >
          <ArrowLeft className="h-4 w-4" />
          <span>{lang === 'bn' ? 'সকল সংবাদে ফিরে যান' : 'Back to All News'}</span>
        </button>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          <article className="lg:col-span-2 rounded-xl border border-slate-200 bg-white p-6 sm:p-8">
            <div className="flex flex-wrap items-center gap-2 text-xs text-slate-500 mb-3">
              <span>{lang === 'bn' ? selected.category_bn : selected.category_en}</span>
              <span aria-hidden="true">·</span>
              <span>{selected.upazila}</span>
              <span aria-hidden="true">·</span>
              <span className="tabular-nums">{selected.views_count} {lang === 'bn' ? 'বার পঠিত' : 'views'}</span>
            </div>
            <h1 className="text-2xl sm:text-3xl font-bold text-slate-900 mb-4 leading-snug">{selected.title}</h1>
            {selected.featured_image && (
              <div className="mb-5 overflow-hidden rounded-xl border border-slate-200 bg-slate-100">
                <img
                  src={selected.featured_image}
                  alt={selected.title}
                  referrerPolicy="no-referrer"
                  className="w-full max-h-96 object-cover"
                />
              </div>
            )}
            <p className="text-base font-medium text-slate-700 mb-4 leading-relaxed">{selected.summary}</p>
            <div className="text-sm sm:text-base text-slate-700 leading-relaxed whitespace-pre-line mb-8">
              {selected.content}
            </div>
            <div className="rounded-lg bg-slate-50 border border-slate-200 p-4">
              <div className="text-xs text-slate-500 mb-1">{lang === 'bn' ? 'প্রতিবেদক' : 'Journalist'}</div>
              <div className="text-sm font-bold text-slate-900">{selected.author_name}</div>
              <div className="text-xs text-slate-600 mt-0.5">
                {selected.author_bio || (lang === 'bn' ? 'অনুমোদিত গাজীপুর জেলা প্রতিবেদক' : 'Approved Gazipur Correspondent')}
              </div>
            </div>
          </article>

          <aside className="space-y-4">
            <div className="rounded-xl border border-slate-200 bg-white p-5">
              <h2 className="text-sm font-bold text-slate-900 mb-3">
                {lang === 'bn' ? 'সম্পর্কিত সংবাদ' : 'Related News'}
              </h2>
              <div className="space-y-3">
                {related.map((rel) => (
                  <button
                    key={rel.id}
                    type="button"
                    onClick={() => setSelected(rel)}
                    className="block w-full text-left border-b border-slate-100 pb-3 last:border-0 last:pb-0 hover:text-emerald-800"
                  >
                    <div className="text-xs text-slate-500 mb-0.5">{lang === 'bn' ? rel.category_bn : rel.category_en}</div>
                    <div className="text-sm font-semibold text-slate-900">{rel.title}</div>
                  </button>
                ))}
              </div>
            </div>
          </aside>
        </div>
      </div>
    );
  }

  return (
    <div className="max-w-6xl mx-auto px-4 sm:px-6 py-8">
      <div className="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-6">
        <div>
          <h1 className="text-2xl sm:text-3xl font-bold text-slate-900 mb-1">
            {lang === 'bn' ? 'গাজীপুর স্থানীয় সংবাদ পোর্টাল' : 'Gazipur Local News Portal'}
          </h1>
          <p className="text-sm text-slate-600">
            {lang === 'bn'
              ? 'অনুমোদিত সাংবাদিকদের প্রতিবেদন — সম্পাদকীয় অনুমোদনের পর প্রকাশিত।'
              : 'Verified Gazipur journalism — every journalist submission undergoes editor review before publishing.'}
          </p>
        </div>

        {isJournalist && (
          <button
            type="button"
            onClick={() => setShowSubmit((v) => !v)}
            className="rounded-lg bg-emerald-800 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-900 whitespace-nowrap"
          >
            {lang === 'bn' ? 'সংবাদ জমা দিন (সাংবাদিক)' : 'Submit News Article'}
          </button>
        )}
      </div>

      {feedback && (
        <div className="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900">
          {feedback}
        </div>
      )}

      {showSubmit && isJournalist && (
        <form onSubmit={handleJournalistSubmit} className="rounded-xl border border-slate-200 bg-white p-6 mb-8 space-y-4">
          <h2 className="text-base font-bold text-slate-900">
            {lang === 'bn'
              ? 'সাংবাদিক প্রতিবেদন জমা (প্রকাশের আগে অ্যাডমিন অনুমোদন বাধ্যতামূলক)'
              : 'Journalist News Submission (Pending Admin Approval)'}
          </h2>
          {error && <div className="text-sm text-red-600">{error}</div>}
          <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <input
              type="text"
              required
              value={title}
              onChange={(e) => setTitle(e.target.value)}
              placeholder={lang === 'bn' ? 'সংবাদের শিরোনাম' : 'Headline'}
              className="rounded-lg border border-slate-300 px-3 py-2 text-sm"
            />
            <select value={catId} onChange={(e) => setCatId(Number(e.target.value))} className="rounded-lg border border-slate-300 px-3 py-2 text-sm">
              {categories.map((c) => (
                <option key={c.id} value={c.id}>{lang === 'bn' ? c.name_bn : c.name_en}</option>
              ))}
            </select>
            <select value={upazila} onChange={(e) => setUpazila(e.target.value)} className="rounded-lg border border-slate-300 px-3 py-2 text-sm">
              {GAZIPUR_UPAZILAS.map((u) => (
                <option key={u.value} value={u.value}>{lang === 'bn' ? u.bn : u.en}</option>
              ))}
            </select>
          </div>
          <input
            type="text"
            required
            value={summary}
            onChange={(e) => setSummary(e.target.value)}
            placeholder={lang === 'bn' ? 'সংক্ষিপ্ত সারাংশ' : 'Summary'}
            className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
          />
          <textarea
            rows={4}
            required
            value={content}
            onChange={(e) => setContent(e.target.value)}
            placeholder={lang === 'bn' ? 'সম্পূর্ণ সংবাদ প্রতিবেদন...' : 'Full article content...'}
            className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
          />
          <div className="flex justify-end gap-2">
            <button type="button" onClick={() => setShowSubmit(false)} className="rounded-lg border border-slate-300 px-4 py-2 text-sm">
              {lang === 'bn' ? 'বাতিল' : 'Cancel'}
            </button>
            <button type="submit" className="rounded-lg bg-emerald-800 px-5 py-2 text-sm font-semibold text-white hover:bg-emerald-900">
              {lang === 'bn' ? 'অনুমোদনের জন্য জমা দিন' : 'Submit for Review'}
            </button>
          </div>
        </form>
      )}

      {/* Search & Category Tabs */}
      <div className="rounded-xl border border-slate-200 bg-white p-4 mb-6 space-y-3">
        <div className="flex gap-3">
          <div className="relative flex-1">
            <Search className="h-4 w-4 text-slate-400 absolute left-3 top-3" />
            <input
              type="text"
              value={q}
              onChange={(e) => setQ(e.target.value)}
              onKeyDown={(e) => e.key === 'Enter' && loadNews()}
              placeholder={lang === 'bn' ? 'গাজীপুরের সংবাদ খুঁজুন...' : 'Search Gazipur news...'}
              className="w-full rounded-lg border border-slate-300 pl-9 pr-3 py-2 text-sm"
            />
          </div>
          <button type="button" onClick={loadNews} className="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white">
            {lang === 'bn' ? 'খুঁজুন' : 'Search'}
          </button>
        </div>
        <div className="flex flex-wrap gap-1 p-1 bg-slate-100 rounded-lg">
          <button
            type="button"
            onClick={() => setCat('')}
            className={`px-3 py-1.5 text-xs font-medium rounded-md whitespace-nowrap ${
              cat === '' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600'
            }`}
          >
            {lang === 'bn' ? 'সকল বিভাগ' : 'All'}
          </button>
          {categories.map((c) => (
            <button
              key={c.id}
              type="button"
              onClick={() => setCat(c.slug)}
              className={`px-3 py-1.5 text-xs font-medium rounded-md whitespace-nowrap ${
                cat === c.slug ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600'
              }`}
            >
              {lang === 'bn' ? c.name_bn : c.name_en}
            </button>
          ))}
        </div>
      </div>

      {/* Articles Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {articles.map((art) => (
          <article
            key={art.id}
            onClick={() => setSelected(art)}
            className="cursor-pointer rounded-xl border border-slate-200 bg-white overflow-hidden flex flex-col hover:border-slate-300 transition-colors"
          >
            <div className="h-44 bg-slate-100 flex items-center justify-center overflow-hidden">
              {art.featured_image ? (
                <img
                  src={art.featured_image}
                  alt={art.title}
                  referrerPolicy="no-referrer"
                  className="h-full w-full object-cover"
                  loading="lazy"
                />
              ) : (
                <Newspaper className="h-8 w-8 text-slate-400" />
              )}
            </div>
            <div className="p-5 flex-1 flex flex-col">
              <div className="flex items-center gap-2 text-xs text-slate-500 mb-2">
                <span>{lang === 'bn' ? art.category_bn : art.category_en}</span>
                <span aria-hidden="true">·</span>
                <span>{art.upazila}</span>
              </div>
              <h2 className="text-base font-bold text-slate-900 mb-2 leading-snug">{art.title}</h2>
              <p className="text-sm text-slate-600 line-clamp-3 flex-1 mb-4">{art.summary}</p>
              <div className="flex items-center justify-between text-xs text-slate-500 pt-3 border-t border-slate-100">
                <span>{art.author_name}</span>
                <span className="tabular-nums">{art.views_count} {lang === 'bn' ? 'পঠিত' : 'reads'}</span>
              </div>
            </div>
          </article>
        ))}
      </div>
    </div>
  );
};
