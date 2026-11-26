import React, { useEffect, useState } from 'react';
import {
  LayoutDashboard,
  Users,
  Newspaper,
  ShieldAlert,
  Megaphone,
  Settings,
  ArrowLeft,
  Trash2,
} from 'lucide-react';
import { Lang, User } from '../types';

interface Props {
  lang: Lang;
  user: User | null;
  token: string | null;
  adminClaimed: boolean;
  onAdminClaimed: (token: string, user: User) => void;
  onRequireLogin: () => void;
  onExitAdmin: () => void;
}

type AdminTab = 'overview' | 'users' | 'news' | 'moderation' | 'ads' | 'settings';

export const AdminConsole: React.FC<Props> = ({
  lang,
  user,
  token,
  adminClaimed,
  onAdminClaimed,
  onRequireLogin,
  onExitAdmin,
}) => {
  const [tab, setTab] = useState<AdminTab>('overview');
  const [claimName, setClaimName] = useState('');
  const [claimEmail, setClaimEmail] = useState('');
  const [claimPassword, setClaimPassword] = useState('');
  const [claimError, setClaimError] = useState('');

  // Admin data states
  const [overview, setOverview] = useState<{ stats: Record<string, number>; logs: Record<string, unknown>[] } | null>(null);
  const [adminUsers, setAdminUsers] = useState<Record<string, unknown>[]>([]);
  const [adminNews, setAdminNews] = useState<Record<string, unknown>[]>([]);
  const [modData, setModData] = useState<{
    flaggedPosts: Record<string, unknown>[];
    rules: { id: number; keyword: string; match_type: string }[];
    reports: Record<string, unknown>[];
  } | null>(null);
  const [ads, setAds] = useState<Record<string, unknown>[]>([]);
  const [settings, setSettings] = useState<Record<string, string>>({});

  // Forms
  const [newKeyword, setNewKeyword] = useState('');
  const [newMatchType, setNewMatchType] = useState('word');
  const [adTitle, setAdTitle] = useState('');
  const [adPlacement, setAdPlacement] = useState('home_banner');
  const [adUrl, setAdUrl] = useState('#');
  const [feedback, setFeedback] = useState('');

  const isAuthorized = user && ['admin', 'editor', 'moderator'].includes(user.role);

  const loadTabData = async () => {
    if (!token || !isAuthorized) return;
    const headers = { Authorization: `Bearer ${token}` };
    if (tab === 'overview') {
      const r = await fetch('/api/admin/overview', { headers });
      if (r.ok) setOverview(await r.json());
    } else if (tab === 'users') {
      const r = await fetch('/api/admin/users', { headers });
      if (r.ok) setAdminUsers((await r.json()).users || []);
    } else if (tab === 'news') {
      const r = await fetch('/api/admin/news', { headers });
      if (r.ok) setAdminNews((await r.json()).articles || []);
    } else if (tab === 'moderation') {
      const r = await fetch('/api/admin/moderation', { headers });
      if (r.ok) setModData(await r.json());
    } else if (tab === 'ads') {
      const r = await fetch('/api/admin/ads', { headers });
      if (r.ok) setAds((await r.json()).ads || []);
    } else if (tab === 'settings') {
      const r = await fetch('/api/bootstrap');
      if (r.ok) setSettings((await r.json()).settings || {});
    }
  };

  useEffect(() => {
    loadTabData();
  }, [tab, token, isAuthorized]);

  const handleClaimAdmin = async (e: React.FormEvent) => {
    e.preventDefault();
    setClaimError('');
    const res = await fetch('/api/auth/claim-admin', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ full_name: claimName, email: claimEmail, password: claimPassword }),
    });
    const d = await res.json();
    if (!res.ok) {
      setClaimError(d.error || 'Error');
      return;
    }
    onAdminClaimed(d.token, d.user);
  };

  // First-Run Admin Claim Setup Form (when no Admin account exists yet)
  if (!adminClaimed && !isAuthorized) {
    return (
      <div className="min-h-[80vh] bg-slate-900 flex items-center justify-center p-6">
        <div className="w-full max-w-md rounded-xl border border-slate-700 bg-slate-800 p-6 text-slate-100">
          <div className="text-xs text-emerald-400 font-semibold mb-1">AmarGazipur Admin Setup</div>
          <h1 className="text-xl font-bold mb-2">
            {lang === 'bn' ? 'প্রথম অ্যাডমিন অ্যাকাউন্ট সেটআপ' : 'First-Run Admin Setup'}
          </h1>
          <p className="text-xs text-slate-400 mb-5 leading-relaxed">
            {lang === 'bn'
              ? 'সিস্টেমে এখনো কোনো অ্যাডমিন তৈরি করা হয়নি। নিচের ফর্মে আপনার ইমেইল ও পাসওয়ার্ড দিয়ে প্রধান অ্যাডমিন অ্যাকাউন্টটি সেটআপ করুন।'
              : 'No admin account has been claimed yet. Create the initial Super Admin account below; this setup locks automatically once claimed.'}
          </p>
          {claimError && <div className="mb-3 rounded bg-red-950/60 border border-red-800 p-2.5 text-xs text-red-200">{claimError}</div>}
          <form onSubmit={handleClaimAdmin} className="space-y-3">
            <input type="text" required value={claimName} onChange={(e) => setClaimName(e.target.value)} placeholder={lang === 'bn' ? 'অ্যাডমিন নাম' : 'Admin Full Name'} className="w-full rounded-lg border border-slate-600 bg-slate-900 px-3 py-2 text-sm text-white" />
            <input type="email" required value={claimEmail} onChange={(e) => setClaimEmail(e.target.value)} placeholder={lang === 'bn' ? 'অ্যাডমিন ইমেইল' : 'Admin Email'} className="w-full rounded-lg border border-slate-600 bg-slate-900 px-3 py-2 text-sm text-white" />
            <input type="password" required minLength={8} value={claimPassword} onChange={(e) => setClaimPassword(e.target.value)} placeholder={lang === 'bn' ? 'শক্তিশালী পাসওয়ার্ড (৮+ অক্ষর)' : 'Password (8+ chars)'} className="w-full rounded-lg border border-slate-600 bg-slate-900 px-3 py-2 text-sm text-white" />
            <button type="submit" className="w-full rounded-lg bg-emerald-700 py-2.5 text-sm font-semibold text-white hover:bg-emerald-600">
              {lang === 'bn' ? 'অ্যাডমিন অ্যাকাউন্ট তৈরি ও প্রবেশ করুন' : 'Create Admin & Enter Console'}
            </button>
          </form>
          <button type="button" onClick={onExitAdmin} className="mt-4 w-full text-center text-xs text-slate-400 hover:text-white">
            {lang === 'bn' ? 'মূল ওয়েবসাইটে ফিরে যান' : 'Return to Public Website'}
          </button>
        </div>
      </div>
    );
  }

  if (!isAuthorized) {
    return (
      <div className="min-h-[75vh] bg-slate-900 flex items-center justify-center p-6">
        <div className="max-w-md rounded-xl border border-slate-700 bg-slate-800 p-6 text-center text-slate-100 space-y-4">
          <h1 className="text-lg font-bold">{lang === 'bn' ? 'অ্যাডমিন অনুমোদন প্রয়োজন' : 'Admin Access Required'}</h1>
          <p className="text-xs text-slate-400">
            {lang === 'bn'
              ? 'এই পৃষ্ঠাটি শুধুমাত্র অনুমোদিত অ্যাডমিন, সম্পাদক বা মডারেটরদের জন্য সংরক্ষিত।'
              : 'This separate console is restricted to authorized Admins, Editors, and Moderators.'}
          </p>
          <div className="flex gap-3 justify-center">
            <button type="button" onClick={onRequireLogin} className="rounded-lg bg-emerald-700 px-4 py-2 text-xs font-semibold text-white">
              {lang === 'bn' ? 'অ্যাডমিন লগইন' : 'Admin Login'}
            </button>
            <button type="button" onClick={onExitAdmin} className="rounded-lg border border-slate-600 px-4 py-2 text-xs text-slate-300">
              {lang === 'bn' ? 'ফিরে যান' : 'Go Back'}
            </button>
          </div>
        </div>
      </div>
    );
  }

  const reviewCategory = async (catId: number, decision: 'approved' | 'rejected') => {
    await fetch(`/api/admin/categories/${catId}/review`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${token}` },
      body: JSON.stringify({ decision }),
    });
    setFeedback(`Category ${decision}`);
    loadTabData();
  };

  const updateUser = async (userId: number, status: string, role: string) => {
    await fetch(`/api/admin/users/${userId}/status`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${token}` },
      body: JSON.stringify({ status, role }),
    });
    setFeedback('User updated');
    loadTabData();
  };

  const reviewNews = async (newsId: number, decision: 'published' | 'rejected', isFeatured: boolean) => {
    await fetch(`/api/admin/news/${newsId}/review`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${token}` },
      body: JSON.stringify({ decision, is_featured: isFeatured }),
    });
    setFeedback(`News ${decision}`);
    loadTabData();
  };

  const addKeywordRule = async (e: React.FormEvent) => {
    e.preventDefault();
    await fetch('/api/admin/moderation/rules', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${token}` },
      body: JSON.stringify({ keyword: newKeyword, match_type: newMatchType }),
    });
    setNewKeyword('');
    loadTabData();
  };

  const deleteKeywordRule = async (id: number) => {
    await fetch(`/api/admin/moderation/rules/${id}`, {
      method: 'DELETE',
      headers: { Authorization: `Bearer ${token}` },
    });
    loadTabData();
  };

  const reviewFlaggedPost = async (id: number, status: string) => {
    await fetch(`/api/admin/moderation/posts/${id}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${token}` },
      body: JSON.stringify({ status }),
    });
    loadTabData();
  };

  const resolveReport = async (id: number, status: string) => {
    await fetch(`/api/admin/moderation/reports/${id}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${token}` },
      body: JSON.stringify({ status }),
    });
    loadTabData();
  };

  const createAd = async (e: React.FormEvent) => {
    e.preventDefault();
    await fetch('/api/admin/ads', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${token}` },
      body: JSON.stringify({ title: adTitle, placement: adPlacement, target_url: adUrl }),
    });
    setAdTitle('');
    loadTabData();
  };

  const saveSettings = async (e: React.FormEvent) => {
    e.preventDefault();
    await fetch('/api/admin/settings', {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${token}` },
      body: JSON.stringify(settings),
    });
    setFeedback(lang === 'bn' ? 'সেটিংস সংরক্ষিত হয়েছে' : 'Settings saved');
  };

  return (
    <div className="min-h-screen bg-slate-950 text-slate-100 flex flex-col md:flex-row">
      {/* Separate Admin Sidebar */}
      <aside className="w-full md:w-64 bg-slate-900 border-b md:border-b-0 md:border-r border-slate-800 p-4 flex flex-col shrink-0">
        <div className="mb-6">
          <div className="text-base font-bold text-white">AmarGazipur Console</div>
          <div className="text-xs text-slate-400">Admin &amp; Moderation</div>
        </div>
        <nav className="space-y-1 flex-1">
          {[
            { id: 'overview', icon: LayoutDashboard, bn: 'ওভারভিউ', en: 'Overview' },
            { id: 'users', icon: Users, bn: 'ব্যবহারকারী ও ক্যাটাগরি', en: 'Users & Categories' },
            { id: 'news', icon: Newspaper, bn: 'সংবাদ অনুমোদন কিউ', en: 'News Approval Queue' },
            { id: 'moderation', icon: ShieldAlert, bn: 'মডারেশন ও নিষিদ্ধ শব্দ', en: 'Moderation & Keywords' },
            { id: 'ads', icon: Megaphone, bn: 'বিজ্ঞাপন ব্যবস্থাপনা', en: 'Advertisements' },
            { id: 'settings', icon: Settings, bn: 'সাইট ও SEO সেটিংস', en: 'Site Settings' },
          ].map((item) => {
            const Icon = item.icon;
            return (
              <button
                key={item.id}
                type="button"
                onClick={() => { setTab(item.id as AdminTab); setFeedback(''); }}
                className={`w-full flex items-center gap-2.5 rounded-lg px-3 py-2 text-xs font-medium transition-colors ${
                  tab === item.id ? 'bg-emerald-800 text-white' : 'text-slate-300 hover:bg-slate-800'
                }`}
              >
                <Icon className="h-4 w-4 shrink-0" />
                <span>{lang === 'bn' ? item.bn : item.en}</span>
              </button>
            );
          })}
        </nav>
        <button
          type="button"
          onClick={onExitAdmin}
          className="mt-6 flex items-center gap-2 rounded-lg border border-slate-700 px-3 py-2 text-xs text-slate-300 hover:bg-slate-800"
        >
          <ArrowLeft className="h-4 w-4" />
          <span>{lang === 'bn' ? 'মূল সাইটে ফিরুন' : 'Back to Public Site'}</span>
        </button>
      </aside>

      <main className="flex-1 p-6 sm:p-8 overflow-x-auto">
        <div className="flex items-center justify-between border-b border-slate-800 pb-4 mb-6">
          <h1 className="text-xl font-bold text-white capitalize">{tab}</h1>
          <div className="text-xs text-slate-400">{user.full_name} ({user.role})</div>
        </div>

        {feedback && <div className="mb-4 rounded-lg bg-emerald-900/40 border border-emerald-700 px-4 py-2 text-xs text-emerald-200">{feedback}</div>}

        {tab === 'overview' && overview && (
          <div className="space-y-6">
            <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
              {[
                { label: 'মোট ব্যবহারকারী', val: overview.stats.total_users },
                { label: 'অপেক্ষমাণ ক্যাটাগরি', val: overview.stats.pending_categories },
                { label: 'অপেক্ষমাণ সংবাদ', val: overview.stats.pending_news },
                { label: 'মডারেশন কিউ পোস্ট', val: overview.stats.pending_posts },
                { label: 'মোট পোস্ট', val: overview.stats.total_posts },
                { label: 'মার্কেটপ্লেস', val: overview.stats.marketplace },
                { label: 'বাসা ভাড়া', val: overview.stats.rentals },
                { label: 'চাকরি ও নিয়োগ', val: overview.stats.jobs || 0 },
                { label: 'খোলা রিপোর্ট', val: overview.stats.open_reports },
              ].map((kpi) => (
                <div key={kpi.label} className="rounded-xl border border-slate-800 bg-slate-900 p-4">
                  <div className="text-xs text-slate-400">{kpi.label}</div>
                  <div className="text-2xl font-bold text-white tabular-nums mt-1">{kpi.val}</div>
                </div>
              ))}
            </div>
            <div className="rounded-xl border border-slate-800 bg-slate-900 p-5">
              <h2 className="text-sm font-bold text-white mb-3">সাম্প্রতিক অ্যাডমিন অ্যাক্টিভিটি লগ</h2>
              <div className="space-y-2 text-xs">
                {overview.logs.map((l, idx) => (
                  <div key={idx} className="flex justify-between border-b border-slate-800 pb-2 text-slate-300">
                    <span>{String(l.admin_name)} · {String(l.action)} ({String(l.target_type)})</span>
                    <span className="font-mono text-slate-500">{String(l.created_at)}</span>
                  </div>
                ))}
              </div>
            </div>
          </div>
        )}

        {tab === 'users' && (
          <div className="rounded-xl border border-slate-800 bg-slate-900 p-5 space-y-4">
            {adminUsers.map((u) => (
              <div key={String(u.id)} className="border-b border-slate-800 pb-4 last:border-0">
                <div className="flex flex-wrap items-center justify-between gap-3 mb-2">
                  <div>
                    <div className="font-bold text-white text-sm">{String(u.full_name)}</div>
                    <div className="text-xs text-slate-400">{String(u.email)} · {String(u.upazila)}</div>
                  </div>
                  <div className="flex items-center gap-2 text-xs">
                    <select
                      defaultValue={String(u.role)}
                      onChange={(e) => updateUser(Number(u.id), String(u.status), e.target.value)}
                      className="rounded border border-slate-700 bg-slate-950 px-2 py-1"
                    >
                      <option value="member">member</option>
                      <option value="moderator">moderator</option>
                      <option value="editor">editor</option>
                      <option value="admin">admin</option>
                    </select>
                    <select
                      defaultValue={String(u.status)}
                      onChange={(e) => updateUser(Number(u.id), e.target.value, String(u.role))}
                      className="rounded border border-slate-700 bg-slate-950 px-2 py-1"
                    >
                      <option value="active">active</option>
                      <option value="suspended">suspended</option>
                    </select>
                  </div>
                </div>
                <div className="flex flex-wrap gap-2">
                  {((u.categories as { id: number; category_slug: string; status: string }[]) || []).map((c) => (
                    <div key={c.id} className="flex items-center gap-2 rounded-lg border border-slate-700 bg-slate-950 px-3 py-1.5 text-xs">
                      <span>{c.category_slug}: <strong>{c.status}</strong></span>
                      {c.status !== 'approved' && (
                        <button type="button" onClick={() => reviewCategory(c.id, 'approved')} className="text-emerald-400 hover:underline">Approve</button>
                      )}
                      {c.status !== 'rejected' && (
                        <button type="button" onClick={() => reviewCategory(c.id, 'rejected')} className="text-red-400 hover:underline">Reject</button>
                      )}
                    </div>
                  ))}
                </div>
              </div>
            ))}
          </div>
        )}

        {tab === 'news' && (
          <div className="rounded-xl border border-slate-800 bg-slate-900 p-5 space-y-4">
            {adminNews.map((art) => (
              <div key={String(art.id)} className="flex flex-wrap items-center justify-between gap-4 border-b border-slate-800 pb-4 last:border-0">
                <div>
                  <div className="text-xs text-slate-400">{String(art.category_bn)} · {String(art.author_name)} · Status: <strong className="text-white">{String(art.status)}</strong></div>
                  <div className="text-sm font-bold text-white mt-0.5">{String(art.title)}</div>
                </div>
                <div className="flex gap-2">
                  <button type="button" onClick={() => reviewNews(Number(art.id), 'published', true)} className="rounded bg-emerald-700 px-3 py-1.5 text-xs font-semibold text-white">
                    Publish &amp; Feature
                  </button>
                  <button type="button" onClick={() => reviewNews(Number(art.id), 'rejected', false)} className="rounded border border-red-700 px-3 py-1.5 text-xs text-red-300">
                    Reject
                  </button>
                </div>
              </div>
            ))}
          </div>
        )}

        {tab === 'moderation' && modData && (
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div className="rounded-xl border border-slate-800 bg-slate-900 p-5 space-y-4">
              <h2 className="text-sm font-bold text-white">নিষিদ্ধ শব্দ ও নিয়ম (Keyword Rules)</h2>
              <form onSubmit={addKeywordRule} className="flex gap-2">
                <input type="text" required value={newKeyword} onChange={(e) => setNewKeyword(e.target.value)} placeholder="নিষিদ্ধ শব্দ..." className="flex-1 rounded border border-slate-700 bg-slate-950 px-3 py-1.5 text-xs text-white" />
                <select value={newMatchType} onChange={(e) => setNewMatchType(e.target.value)} className="rounded border border-slate-700 bg-slate-950 px-2 py-1.5 text-xs">
                  <option value="word">Word</option>
                  <option value="phrase">Phrase</option>
                </select>
                <button type="submit" className="rounded bg-emerald-700 px-3 py-1.5 text-xs font-semibold text-white">Add</button>
              </form>
              <div className="space-y-2">
                {modData.rules.map((r) => (
                  <div key={r.id} className="flex justify-between items-center border-b border-slate-800 pb-1.5 text-xs">
                    <span>{r.keyword} ({r.match_type})</span>
                    <button type="button" onClick={() => deleteKeywordRule(r.id)} className="text-red-400 hover:text-red-300">
                      <Trash2 className="h-3.5 w-3.5" />
                    </button>
                  </div>
                ))}
              </div>
            </div>

            <div className="space-y-6">
              <div className="rounded-xl border border-slate-800 bg-slate-900 p-5 space-y-3">
                <h2 className="text-sm font-bold text-white">ফ্ল্যাগকৃত পোস্ট ({modData.flaggedPosts.length})</h2>
                {modData.flaggedPosts.map((fp) => (
                  <div key={String(fp.id)} className="border-b border-slate-800 pb-3 text-xs">
                    <div className="font-bold text-white">{String(fp.title)}</div>
                    <div className="text-amber-300 mb-2">{String(fp.moderation_reason || 'Flagged')}</div>
                    <div className="flex gap-2">
                      <button type="button" onClick={() => reviewFlaggedPost(Number(fp.id), 'published')} className="rounded bg-emerald-700 px-2.5 py-1 text-white">Approve</button>
                      <button type="button" onClick={() => reviewFlaggedPost(Number(fp.id), 'rejected')} className="rounded border border-red-700 px-2.5 py-1 text-red-300">Reject</button>
                    </div>
                  </div>
                ))}
              </div>

              <div className="rounded-xl border border-slate-800 bg-slate-900 p-5 space-y-3">
                <h2 className="text-sm font-bold text-white">ব্যবহারকারী রিপোর্ট ({modData.reports.length})</h2>
                {modData.reports.map((rep) => (
                  <div key={String(rep.id)} className="border-b border-slate-800 pb-3 text-xs">
                    <div>{String(rep.target_type)} #{String(rep.target_id)} — {String(rep.reason)} ({String(rep.status)})</div>
                    <div className="flex gap-2 mt-2">
                      <button type="button" onClick={() => resolveReport(Number(rep.id), 'resolved')} className="rounded bg-slate-700 px-2.5 py-1 text-white">Resolve</button>
                      <button type="button" onClick={() => resolveReport(Number(rep.id), 'dismissed')} className="rounded border border-slate-700 px-2.5 py-1 text-slate-300">Dismiss</button>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          </div>
        )}

        {tab === 'ads' && (
          <div className="space-y-6">
            <form onSubmit={createAd} className="rounded-xl border border-slate-800 bg-slate-900 p-5 grid grid-cols-1 sm:grid-cols-4 gap-3">
              <input type="text" required value={adTitle} onChange={(e) => setAdTitle(e.target.value)} placeholder="বিজ্ঞাপনের শিরোনাম" className="sm:col-span-2 rounded border border-slate-700 bg-slate-950 px-3 py-2 text-xs text-white" />
              <select value={adPlacement} onChange={(e) => setAdPlacement(e.target.value)} className="rounded border border-slate-700 bg-slate-950 px-3 py-2 text-xs">
                <option value="home_banner">Home Banner</option>
                <option value="sidebar">Sidebar</option>
              </select>
              <button type="submit" className="rounded bg-emerald-700 px-4 py-2 text-xs font-semibold text-white">বিজ্ঞাপন প্রকাশ</button>
            </form>
            <div className="rounded-xl border border-slate-800 bg-slate-900 p-5 space-y-2 text-xs">
              {ads.map((a) => (
                <div key={String(a.id)} className="flex justify-between border-b border-slate-800 pb-2">
                  <span>{String(a.title)} ({String(a.placement)})</span>
                  <span className="text-emerald-400">{String(a.status)}</span>
                </div>
              ))}
            </div>
          </div>
        )}

        {tab === 'settings' && (
          <form onSubmit={saveSettings} className="rounded-xl border border-slate-800 bg-slate-900 p-5 space-y-3 max-w-2xl">
            {Object.entries(settings).map(([key, val]) => (
              <div key={key}>
                <label className="block text-xs text-slate-400 mb-1">{key}</label>
                <input
                  type="text"
                  value={val}
                  onChange={(e) => setSettings((prev) => ({ ...prev, [key]: e.target.value }))}
                  className="w-full rounded border border-slate-700 bg-slate-950 px-3 py-2 text-xs text-white"
                />
              </div>
            ))}
            <button type="submit" className="rounded-lg bg-emerald-700 px-5 py-2 text-xs font-semibold text-white">Save Settings</button>
          </form>
        )}
      </main>
    </div>
  );
};
