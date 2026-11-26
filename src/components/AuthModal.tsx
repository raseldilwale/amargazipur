import React, { useState } from 'react';
import { X, ShieldCheck } from 'lucide-react';
import { ACCOUNT_CATEGORIES, GAZIPUR_UPAZILAS, Lang, User } from '../types';

interface AuthModalProps {
  isOpen: boolean;
  initialMode: 'prompt' | 'login' | 'register';
  lang: Lang;
  onClose: () => void;
  onSuccess: (token: string, user: User) => void;
}

export const AuthModal: React.FC<AuthModalProps> = ({
  isOpen,
  initialMode,
  lang,
  onClose,
  onSuccess,
}) => {
  const [mode, setMode] = useState<'prompt' | 'login' | 'register'>(initialMode);
  const [fullName, setFullName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [upazila, setUpazila] = useState('Gazipur Sadar');
  const [selectedCats, setSelectedCats] = useState<string[]>(['general_user']);
  const [terms, setTerms] = useState(true);
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  React.useEffect(() => {
    setMode(initialMode);
    setError('');
  }, [initialMode, isOpen]);

  if (!isOpen) return null;

  const toggleCat = (slug: string) => {
    setSelectedCats((prev) =>
      prev.includes(slug) ? prev.filter((c) => c !== slug) : [...prev, slug]
    );
  };

  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    setLoading(true);
    try {
      const res = await fetch('/api/auth/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, password }),
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data.error || 'Login failed');
      onSuccess(data.token, data.user);
      onClose();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Login error');
    } finally {
      setLoading(false);
    }
  };

  const handleRegister = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    setLoading(true);
    try {
      const res = await fetch('/api/auth/register', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          full_name: fullName,
          email,
          password,
          confirm_password: confirmPassword,
          upazila,
          categories: selectedCats,
          terms_accepted: terms,
        }),
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data.error || 'Registration failed');
      onSuccess(data.token, data.user);
      onClose();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Registration error');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-xs">
      <div className="w-full max-w-lg rounded-xl border border-slate-200 bg-white p-6 shadow-xl max-h-[90vh] overflow-y-auto">
        <div className="flex items-center justify-between border-b border-slate-100 pb-4 mb-4">
          <h2 className="text-lg font-bold text-slate-900">
            {mode === 'prompt'
              ? lang === 'bn'
                ? 'সদস্য অ্যাকাউন্ট প্রয়োজন'
                : 'Member Account Required'
              : mode === 'login'
              ? lang === 'bn'
                ? 'অ্যাকাউন্টে লগইন করুন'
                : 'Sign In to AmarGazipur'
              : lang === 'bn'
              ? 'নতুন অ্যাকাউন্ট নিবন্ধন'
              : 'Create AmarGazipur Account'}
          </h2>
          <button
            type="button"
            onClick={onClose}
            className="rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-900"
            aria-label="Close"
          >
            <X className="h-5 w-5" />
          </button>
        </div>

        {mode === 'prompt' && (
          <div className="space-y-4">
            <div className="flex items-start gap-3 rounded-lg bg-slate-50 p-4 border border-slate-200">
              <ShieldCheck className="h-5 w-5 text-emerald-800 shrink-0 mt-0.5" />
              <p className="text-sm text-slate-700 leading-relaxed">
                {lang === 'bn'
                  ? 'আপনি লগইন ছাড়াই সব সংবাদ, পোস্ট, মার্কেটপ্লেস ও টু-লেট দেখতে পারবেন। তবে পছন্দ (Like), মন্তব্য, নতুন পোস্ট বা সুরক্ষিত যোগাযোগের তথ্য দেখতে অনুগ্রহ করে লগইন অথবা নিবন্ধন করুন।'
                  : 'You can browse all public content without logging in. To like, comment, create posts, or view protected contact numbers, please sign in or register.'}
              </p>
            </div>
            <div className="flex flex-wrap gap-3">
              <button
                type="button"
                onClick={() => setMode('login')}
                className="flex-1 rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-800 hover:bg-slate-50"
              >
                {lang === 'bn' ? 'লগইন করুন' : 'Sign In'}
              </button>
              <button
                type="button"
                onClick={() => setMode('register')}
                className="flex-1 rounded-lg bg-emerald-800 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-900"
              >
                {lang === 'bn' ? 'নতুন নিবন্ধন' : 'Register Now'}
              </button>
            </div>
          </div>
        )}

        {mode === 'login' && (
          <form onSubmit={handleLogin} className="space-y-4">
            {error && <div className="rounded-lg bg-red-50 border border-red-200 p-3 text-sm text-red-700">{error}</div>}
            <div>
              <label className="block text-sm font-medium text-slate-700 mb-1">
                {lang === 'bn' ? 'ইমেইল ঠিকানা' : 'Email Address'}
              </label>
              <input
                type="email"
                required
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-700 focus:outline-none"
                placeholder="name@example.com"
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-slate-700 mb-1">
                {lang === 'bn' ? 'পাসওয়ার্ড' : 'Password'}
              </label>
              <input
                type="password"
                required
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-700 focus:outline-none"
              />
            </div>
            <button
              type="submit"
              disabled={loading}
              className="w-full rounded-lg bg-emerald-800 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-900 disabled:opacity-50"
            >
              {loading ? '...' : lang === 'bn' ? 'লগইন করুন' : 'Sign In'}
            </button>
            <div className="text-center text-sm text-slate-600">
              {lang === 'bn' ? 'অ্যাকাউন্ট নেই?' : "Don't have an account?"}{' '}
              <button type="button" onClick={() => setMode('register')} className="font-semibold text-emerald-800 hover:underline">
                {lang === 'bn' ? 'নিবন্ধন করুন' : 'Register'}
              </button>
            </div>
          </form>
        )}

        {mode === 'register' && (
          <form onSubmit={handleRegister} className="space-y-4">
            {error && <div className="rounded-lg bg-red-50 border border-red-200 p-3 text-sm text-red-700">{error}</div>}
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label className="block text-xs font-medium text-slate-700 mb-1">
                  {lang === 'bn' ? 'পূর্ণ নাম *' : 'Full Name *'}
                </label>
                <input
                  type="text"
                  required
                  value={fullName}
                  onChange={(e) => setFullName(e.target.value)}
                  className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-700 focus:outline-none"
                />
              </div>
              <div>
                <label className="block text-xs font-medium text-slate-700 mb-1">
                  {lang === 'bn' ? 'ইমেইল *' : 'Email *'}
                </label>
                <input
                  type="email"
                  required
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-700 focus:outline-none"
                />
              </div>
              <div>
                <label className="block text-xs font-medium text-slate-700 mb-1">
                  {lang === 'bn' ? 'পাসওয়ার্ড (৮+ অক্ষর) *' : 'Password (8+ chars) *'}
                </label>
                <input
                  type="password"
                  required
                  minLength={8}
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-700 focus:outline-none"
                />
              </div>
              <div>
                <label className="block text-xs font-medium text-slate-700 mb-1">
                  {lang === 'bn' ? 'পাসওয়ার্ড নিশ্চিত করুন *' : 'Confirm Password *'}
                </label>
                <input
                  type="password"
                  required
                  minLength={8}
                  value={confirmPassword}
                  onChange={(e) => setConfirmPassword(e.target.value)}
                  className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-700 focus:outline-none"
                />
              </div>
            </div>

            <div>
              <label className="block text-xs font-medium text-slate-700 mb-1">
                {lang === 'bn' ? 'উপজেলা / এলাকা' : 'Upazila / Area'}
              </label>
              <select
                value={upazila}
                onChange={(e) => setUpazila(e.target.value)}
                className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-700 focus:outline-none"
              >
                {GAZIPUR_UPAZILAS.map((u) => (
                  <option key={u.value} value={u.value}>
                    {lang === 'bn' ? u.bn : u.en}
                  </option>
                ))}
              </select>
            </div>

            <div>
              <label className="block text-xs font-medium text-slate-700 mb-1.5">
                {lang === 'bn'
                  ? 'অ্যাকাউন্ট ক্যাটাগরি (প্রাথমিকভাবে Pending থাকবে — অ্যাডমিন অনুমোদন করবেন)'
                  : 'Account Categories (Initially Pending Admin Approval)'}
              </label>
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
                {ACCOUNT_CATEGORIES.map((cat) => (
                  <label
                    key={cat.slug}
                    className="flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs cursor-pointer hover:bg-slate-100"
                  >
                    <input
                      type="checkbox"
                      checked={selectedCats.includes(cat.slug)}
                      onChange={() => toggleCat(cat.slug)}
                      className="rounded border-slate-300 text-emerald-800"
                    />
                    <span className="text-slate-800 font-medium">{lang === 'bn' ? cat.bn : cat.en}</span>
                  </label>
                ))}
              </div>
            </div>

            <label className="flex items-center gap-2 text-xs text-slate-600 cursor-pointer">
              <input
                type="checkbox"
                checked={terms}
                onChange={(e) => setTerms(e.target.checked)}
                required
                className="rounded border-slate-300 text-emerald-800"
              />
              <span>
                {lang === 'bn'
                  ? 'আমি AmarGazipur-এর শর্তাবলী ও গোপনীয়তা নীতিতে সম্মতি দিচ্ছি।'
                  : 'I agree to the AmarGazipur Terms of Service and Privacy Policy.'}
              </span>
            </label>

            <button
              type="submit"
              disabled={loading}
              className="w-full rounded-lg bg-emerald-800 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-900 disabled:opacity-50"
            >
              {loading ? '...' : lang === 'bn' ? 'অ্যাকাউন্ট তৈরি করুন' : 'Create Account'}
            </button>
            <div className="text-center text-sm text-slate-600">
              {lang === 'bn' ? 'ইতিমধ্যে অ্যাকাউন্ট আছে?' : 'Already have an account?'}{' '}
              <button type="button" onClick={() => setMode('login')} className="font-semibold text-emerald-800 hover:underline">
                {lang === 'bn' ? 'লগইন করুন' : 'Sign In'}
              </button>
            </div>
          </form>
        )}
      </div>
    </div>
  );
};
