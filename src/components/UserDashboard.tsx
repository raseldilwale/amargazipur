import React, { useEffect, useState } from 'react';
import { Bell, LogOut, UserCheck, Shield } from 'lucide-react';
import { ACCOUNT_CATEGORIES, GAZIPUR_UPAZILAS, Lang, User } from '../types';

interface Props {
  lang: Lang;
  user: User;
  token: string;
  onUserUpdated: (user: User) => void;
  onLogout: () => void;
  onOpenAdmin: () => void;
}

interface DashData {
  myPosts: { id: number; title: string; status: string; created_at: string }[];
  myMarket: { id: number; title: string; price: number; status: string }[];
  myRentals: { id: number; title: string; monthly_rent: number; status: string }[];
  myJobs?: { id: number; company_name: string; job_title: string; salary: string; status: string }[];
  myComments: { id: number; post_title: string; body: string }[];
  myLikes: { id: number; title: string }[];
  notifications: { id: number; title: string; message: string; is_read: number }[];
}

export const UserDashboard: React.FC<Props> = ({
  lang,
  user,
  token,
  onUserUpdated,
  onLogout,
  onOpenAdmin,
}) => {
  const [data, setData] = useState<DashData | null>(null);
  const [fullName, setFullName] = useState(user.full_name);
  const [phone, setPhone] = useState(user.phone || '');
  const [upazila, setUpazila] = useState(user.upazila);
  const [bio, setBio] = useState(user.bio || '');
  const [savedMsg, setSavedMsg] = useState('');

  const loadDashboard = async () => {
    const res = await fetch('/api/dashboard', {
      headers: { Authorization: `Bearer ${token}` },
    });
    if (res.ok) {
      const json = await res.json();
      setData(json);
    }
  };

  useEffect(() => {
    loadDashboard();
  }, [token]);

  const handleSaveProfile = async (e: React.FormEvent) => {
    e.preventDefault();
    setSavedMsg('');
    const res = await fetch('/api/dashboard/profile', {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        Authorization: `Bearer ${token}`,
      },
      body: JSON.stringify({ full_name: fullName, phone, upazila, bio }),
    });
    const json = await res.json();
    if (res.ok && json.user) {
      onUserUpdated(json.user);
      setSavedMsg(lang === 'bn' ? 'প্রোফাইল তথ্য সংরক্ষিত হয়েছে।' : 'Profile updated.');
    }
  };

  const markRead = async () => {
    await fetch('/api/dashboard/notifications/read', {
      method: 'POST',
      headers: { Authorization: `Bearer ${token}` },
    });
    loadDashboard();
  };

  return (
    <div className="max-w-6xl mx-auto px-4 sm:px-6 py-8">
      <div className="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
          <h1 className="text-2xl font-bold text-slate-900">
            {lang === 'bn' ? 'ব্যবহারকারী ড্যাশবোর্ড' : 'Member Dashboard'}
          </h1>
          <p className="text-sm text-slate-600">{user.full_name} · {user.email}</p>
        </div>
        <div className="flex items-center gap-3">
          {['admin', 'editor', 'moderator'].includes(user.role) && (
            <button
              type="button"
              onClick={onOpenAdmin}
              className="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-xs font-semibold text-white hover:bg-slate-800"
            >
              <Shield className="h-4 w-4" />
              <span>{lang === 'bn' ? 'অ্যাডমিন কনসোল' : 'Admin Console'}</span>
            </button>
          )}
          <button
            type="button"
            onClick={onLogout}
            className="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100"
          >
            <LogOut className="h-4 w-4" />
            <span>{lang === 'bn' ? 'লগআউট' : 'Logout'}</span>
          </button>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div className="space-y-6">
          <div className="rounded-xl border border-slate-200 bg-white p-5">
            <div className="flex items-center gap-2 text-sm font-bold text-slate-900 mb-3">
              <UserCheck className="h-4 w-4 text-emerald-800" />
              <span>{lang === 'bn' ? 'অ্যাকাউন্ট ক্যাটাগরি অনুমোদন' : 'Category Approval Status'}</span>
            </div>
            <div className="space-y-2">
              {user.categories.map((cat) => {
                const label = ACCOUNT_CATEGORIES.find((c) => c.slug === cat.category_slug);
                return (
                  <div key={cat.id} className="flex items-center justify-between border-b border-slate-100 pb-2 text-xs">
                    <span className="font-medium text-slate-800">{lang === 'bn' ? label?.bn : label?.en}</span>
                    <span
                      className={`font-semibold ${
                        cat.status === 'approved'
                          ? 'text-emerald-700'
                          : cat.status === 'rejected'
                          ? 'text-red-700'
                          : 'text-amber-700'
                      }`}
                    >
                      {cat.status === 'approved'
                        ? lang === 'bn' ? 'অনুমোদিত' : 'Approved'
                        : cat.status === 'rejected'
                        ? lang === 'bn' ? 'বাতিল' : 'Rejected'
                        : lang === 'bn' ? 'অপেক্ষমাণ (Pending)' : 'Pending'}
                    </span>
                  </div>
                );
              })}
            </div>
          </div>

          <form onSubmit={handleSaveProfile} className="rounded-xl border border-slate-200 bg-white p-5 space-y-3">
            <h2 className="text-sm font-bold text-slate-900">
              {lang === 'bn' ? 'প্রোফাইল সম্পাদনা' : 'Edit Profile'}
            </h2>
            {savedMsg && <div className="text-xs text-emerald-700 font-medium">{savedMsg}</div>}
            <div>
              <label className="block text-xs text-slate-600 mb-1">{lang === 'bn' ? 'পূর্ণ নাম' : 'Full Name'}</label>
              <input type="text" required value={fullName} onChange={(e) => setFullName(e.target.value)} className="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-sm" />
            </div>
            <div>
              <label className="block text-xs text-slate-600 mb-1">{lang === 'bn' ? 'ফোন নম্বর' : 'Phone'}</label>
              <input type="text" value={phone} onChange={(e) => setPhone(e.target.value)} className="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-sm" />
            </div>
            <div>
              <label className="block text-xs text-slate-600 mb-1">{lang === 'bn' ? 'উপজেলা' : 'Upazila'}</label>
              <select value={upazila} onChange={(e) => setUpazila(e.target.value)} className="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-sm">
                {GAZIPUR_UPAZILAS.map((u) => <option key={u.value} value={u.value}>{lang === 'bn' ? u.bn : u.en}</option>)}
              </select>
            </div>
            <div>
              <label className="block text-xs text-slate-600 mb-1">{lang === 'bn' ? 'সংক্ষিপ্ত পরিচিতি' : 'Bio'}</label>
              <textarea rows={2} value={bio} onChange={(e) => setBio(e.target.value)} className="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-sm" />
            </div>
            <button type="submit" className="w-full rounded-lg bg-emerald-800 py-2 text-xs font-semibold text-white hover:bg-emerald-900">
              {lang === 'bn' ? 'সংরক্ষণ করুন' : 'Save Changes'}
            </button>
          </form>
        </div>

        <div className="lg:col-span-2 space-y-6">
          <div className="rounded-xl border border-slate-200 bg-white p-5">
            <div className="flex items-center justify-between mb-3">
              <div className="flex items-center gap-2 text-sm font-bold text-slate-900">
                <Bell className="h-4 w-4 text-slate-700" />
                <span>{lang === 'bn' ? 'নোটিফিকেশন' : 'Notifications'} ({data?.notifications.length || 0})</span>
              </div>
              <button type="button" onClick={markRead} className="text-xs text-emerald-800 hover:underline">
                {lang === 'bn' ? 'সব পঠিত চিহ্নিত করুন' : 'Mark all read'}
              </button>
            </div>
            {data?.notifications.length ? (
              <div className="space-y-2">
                {data.notifications.map((n) => (
                  <div key={n.id} className={`rounded-lg p-3 text-xs ${n.is_read ? 'bg-slate-50 text-slate-600' : 'bg-emerald-50 text-slate-900'}`}>
                    <div className="font-bold">{n.title}</div>
                    <div>{n.message}</div>
                  </div>
                ))}
              </div>
            ) : (
              <p className="text-xs text-slate-500">{lang === 'bn' ? 'কোনো নতুন নোটিফিকেশন নেই।' : 'No notifications.'}</p>
            )}
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div className="rounded-xl border border-slate-200 bg-white p-5">
              <h3 className="text-sm font-bold text-slate-900 mb-3">{lang === 'bn' ? 'আমার পোস্টসমূহ' : 'My Posts'} ({data?.myPosts.length || 0})</h3>
              <div className="space-y-2 text-xs">
                {data?.myPosts.map((p) => (
                  <div key={p.id} className="flex justify-between border-b border-slate-100 pb-2">
                    <span className="truncate pr-2 font-medium text-slate-800">{p.title}</span>
                    <span className="text-slate-500 shrink-0">{p.status}</span>
                  </div>
                ))}
              </div>
            </div>

            <div className="rounded-xl border border-slate-200 bg-white p-5">
              <h3 className="text-sm font-bold text-slate-900 mb-3">{lang === 'bn' ? 'আমার মার্কেট ও টু-লেট' : 'My Listings & Rentals'}</h3>
              <div className="space-y-2 text-xs">
                {data?.myMarket.map((m) => (
                  <div key={`m-${m.id}`} className="flex justify-between border-b border-slate-100 pb-2">
                    <span className="truncate pr-2">{m.title}</span>
                    <span className="tabular-nums text-emerald-800 font-semibold">৳ {m.price}</span>
                  </div>
                ))}
                {data?.myRentals.map((r) => (
                  <div key={`r-${r.id}`} className="flex justify-between border-b border-slate-100 pb-2">
                    <span className="truncate pr-2">{r.title}</span>
                    <span className="tabular-nums text-emerald-800 font-semibold">৳ {r.monthly_rent}/mo</span>
                  </div>
                ))}
                {(data?.myJobs || []).map((j) => (
                  <div key={`j-${j.id}`} className="flex justify-between border-b border-slate-100 pb-2">
                    <span className="truncate pr-2">{j.job_title} ({j.company_name})</span>
                    <span className="text-emerald-800 font-semibold">{j.salary}</span>
                  </div>
                ))}
              </div>
            </div>

            <div className="rounded-xl border border-slate-200 bg-white p-5">
              <h3 className="text-sm font-bold text-slate-900 mb-3">{lang === 'bn' ? 'আমার মন্তব্যসমূহ' : 'My Comments'} ({data?.myComments.length || 0})</h3>
              <div className="space-y-2 text-xs">
                {data?.myComments.map((c) => (
                  <div key={c.id} className="border-b border-slate-100 pb-2">
                    <div className="font-semibold text-slate-800">{c.post_title}</div>
                    <div className="text-slate-600">{c.body}</div>
                  </div>
                ))}
              </div>
            </div>

            <div className="rounded-xl border border-slate-200 bg-white p-5">
              <h3 className="text-sm font-bold text-slate-900 mb-3">{lang === 'bn' ? 'পছন্দ করা পোস্ট' : 'Liked Posts'} ({data?.myLikes.length || 0})</h3>
              <div className="space-y-2 text-xs">
                {data?.myLikes.map((l) => (
                  <div key={l.id} className="border-b border-slate-100 pb-2 text-slate-800 font-medium">{l.title}</div>
                ))}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};
