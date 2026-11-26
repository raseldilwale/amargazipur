import React, { useEffect, useState } from 'react';
import { ShoppingBag, Building2, Briefcase, Lock, ImagePlus } from 'lucide-react';
import {
  GAZIPUR_UPAZILAS,
  JobListing,
  Lang,
  MarketplaceCategory,
  MarketplaceListing,
  RentalListing,
  User,
} from '../types';

interface Props {
  lang: Lang;
  user: User | null;
  token: string | null;
  marketCategories: MarketplaceCategory[];
  initialSubTab?: 'marketplace' | 'rentals' | 'jobs';
  onRequireAuth: () => void;
}

const RENTAL_CATEGORIES = [
  { value: 'family_apartment', bn: 'ফ্যামিলি বাসা', en: 'Family Apartment' },
  { value: 'bachelor_room', bn: 'ব্যাচেলর রুম', en: 'Bachelor Room' },
  { value: 'sublet', bn: 'সাবলেট', en: 'Sublet' },
  { value: 'hostel', bn: 'হোস্টেল', en: 'Hostel' },
  { value: 'mess', bn: 'মেস', en: 'Mess' },
  { value: 'shop', bn: 'দোকান', en: 'Shop' },
  { value: 'office', bn: 'অফিস', en: 'Office' },
  { value: 'commercial', bn: 'বাণিজ্যিক স্পেস', en: 'Commercial' },
];

const JOB_TYPES = [
  { value: 'full_time', bn: 'ফুল-টাইম', en: 'Full-Time' },
  { value: 'part_time', bn: 'পার্ট-টাইম', en: 'Part-Time' },
  { value: 'contract', bn: 'চুক্তিভিত্তিক', en: 'Contract' },
  { value: 'internship', bn: 'ইন্টার্নশিপ', en: 'Internship' },
  { value: 'remote', bn: 'রিমোট', en: 'Remote' },
];

export const MarketRentSection: React.FC<Props> = ({
  lang,
  user,
  token,
  marketCategories,
  initialSubTab = 'marketplace',
  onRequireAuth,
}) => {
  const [tab, setTab] = useState<'marketplace' | 'rentals' | 'jobs'>(initialSubTab);
  const [listings, setListings] = useState<MarketplaceListing[]>([]);
  const [rentals, setRentals] = useState<RentalListing[]>([]);
  const [jobs, setJobs] = useState<JobListing[]>([]);
  const [catFilter, setCatFilter] = useState('');
  const [upazilaFilter, setUpazilaFilter] = useState('');
  const [q, setQ] = useState('');
  const [feedback, setFeedback] = useState('');

  // Marketplace form
  const [mTitle, setMTitle] = useState('');
  const [mPrice, setMPrice] = useState('');
  const [mCatId, setMCatId] = useState(1);
  const [mCond, setMCond] = useState('used');
  const [mLoc, setMLoc] = useState('');
  const [mUpazila, setMUpazila] = useState('Gazipur Sadar');
  const [mPhone, setMPhone] = useState('');
  const [mDesc, setMDesc] = useState('');
  const [mImg, setMImg] = useState<string | null>(null);

  // Rental form
  const [rTitle, setRTitle] = useState('');
  const [rCat, setRCat] = useState('family_apartment');
  const [rRent, setRRent] = useState('');
  const [rAdv, setRAdv] = useState('');
  const [rLoc, setRLoc] = useState('');
  const [rUpazila, setRUpazila] = useState('Gazipur Sadar');
  const [rBeds, setRBeds] = useState('2');
  const [rBaths, setRBaths] = useState('2');
  const [rFloor, setRFloor] = useState('');
  const [rFac, setRFac] = useState('');
  const [rPhone, setRPhone] = useState('');
  const [rHideContact, setRHideContact] = useState(true);
  const [rDesc, setRDesc] = useState('');

  // Job form
  const [jCompany, setJCompany] = useState('');
  const [jTitle, setJTitle] = useState('');
  const [jSalary, setJSalary] = useState('');
  const [jType, setJType] = useState('full_time');
  const [jLocation, setJLocation] = useState('');
  const [jUpazila, setJUpazila] = useState('Gazipur Sadar');
  const [jContact, setJContact] = useState('');
  const [jDeadline, setJDeadline] = useState('');
  const [jDesc, setJDesc] = useState('');

  useEffect(() => {
    setTab(initialSubTab);
  }, [initialSubTab]);

  const isLandlord =
    user &&
    (user.role === 'admin' ||
      user.categories.some((c) => c.category_slug === 'landlord' && c.status === 'approved'));

  const isJobPoster =
    user &&
    (user.role === 'admin' ||
      user.categories.some((c) => c.category_slug === 'job_poster' && c.status === 'approved'));

  const loadData = async () => {
    const params = new URLSearchParams();
    if (tab === 'jobs') {
      if (catFilter) params.set('job_type', catFilter);
    } else if (catFilter) {
      params.set('cat', catFilter);
    }
    if (upazilaFilter) params.set('upazila', upazilaFilter);
    if (q) params.set('q', q);
    const headers = token ? { Authorization: `Bearer ${token}` } : undefined;
    if (tab === 'marketplace') {
      const res = await fetch(`/api/marketplace?${params.toString()}`, { headers });
      const data = await res.json();
      setListings(data.listings || []);
    } else if (tab === 'rentals') {
      const res = await fetch(`/api/rentals?${params.toString()}`, { headers });
      const data = await res.json();
      setRentals(data.rentals || []);
    } else {
      const res = await fetch(`/api/jobs?${params.toString()}`, { headers });
      const data = await res.json();
      setJobs(data.jobs || []);
    }
  };

  useEffect(() => {
    loadData();
  }, [tab, catFilter, upazilaFilter, token]);

  const handleUploadMarketImg = async (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file || !token) return;
    const fd = new FormData();
    fd.append('image', file);
    const res = await fetch('/api/upload', {
      method: 'POST',
      headers: { Authorization: `Bearer ${token}` },
      body: fd,
    });
    const data = await res.json();
    if (res.ok && data.url) setMImg(data.url);
  };

  const submitMarketplace = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!token) return onRequireAuth();
    const res = await fetch('/api/marketplace', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${token}` },
      body: JSON.stringify({
        title: mTitle,
        price: Number(mPrice),
        category_id: mCatId,
        item_condition: mCond,
        location: mLoc,
        upazila: mUpazila,
        contact_phone: mPhone,
        description: mDesc,
        image_path: mImg,
      }),
    });
    const data = await res.json();
    setFeedback(data.message || data.error || '');
    if (res.ok) {
      setMTitle('');
      setMPrice('');
      setMLoc('');
      setMPhone('');
      setMDesc('');
      setMImg(null);
      loadData();
    }
  };

  const submitRental = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!token) return onRequireAuth();
    const res = await fetch('/api/rentals', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${token}` },
      body: JSON.stringify({
        title: rTitle,
        category: rCat,
        monthly_rent: Number(rRent),
        advance_amount: Number(rAdv || 0),
        location: rLoc,
        upazila: rUpazila,
        bedrooms: Number(rBeds),
        bathrooms: Number(rBaths),
        floor_number: rFloor,
        facilities: rFac,
        contact_phone: rPhone,
        hide_contact_public: rHideContact,
        description: rDesc,
      }),
    });
    const data = await res.json();
    setFeedback(data.message || data.error || '');
    if (res.ok) {
      setRTitle('');
      setRRent('');
      setRLoc('');
      setRPhone('');
      setRDesc('');
      loadData();
    }
  };

  const submitJob = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!token) return onRequireAuth();
    const res = await fetch('/api/jobs', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${token}` },
      body: JSON.stringify({
        company_name: jCompany,
        job_title: jTitle,
        job_type: jType,
        salary: jSalary,
        location: jLocation,
        upazila: jUpazila,
        contact_info: jContact,
        deadline: jDeadline,
        description: jDesc,
      }),
    });
    const data = await res.json();
    setFeedback(data.message || data.error || '');
    if (res.ok) {
      setJCompany('');
      setJTitle('');
      setJSalary('');
      setJLocation('');
      setJContact('');
      setJDeadline('');
      setJDesc('');
      loadData();
    }
  };

  return (
    <div className="max-w-6xl mx-auto px-4 sm:px-6 py-8">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
          <h1 className="text-2xl sm:text-3xl font-bold text-slate-900">
            {tab === 'marketplace'
              ? lang === 'bn'
                ? 'গাজীপুর মার্কেটপ্লেস'
                : 'Gazipur Local Marketplace'
              : tab === 'rentals'
              ? lang === 'bn'
                ? 'বাসা ভাড়া ও টু-লেট'
                : 'Gazipur House Rent & To-Let'
              : lang === 'bn'
              ? 'গাজীপুর চাকরি ও নিয়োগ বিজ্ঞপ্তি'
              : 'Gazipur Jobs & Recruitment'}
          </h1>
        </div>
        <div className="inline-flex flex-wrap p-1 bg-slate-100 rounded-lg self-start">
          <button
            type="button"
            onClick={() => { setTab('marketplace'); setCatFilter(''); setQ(''); setFeedback(''); }}
            className={`px-4 py-2 text-xs font-semibold rounded-md transition-colors whitespace-nowrap ${
              tab === 'marketplace' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600'
            }`}
          >
            {lang === 'bn' ? 'মার্কেটপ্লেস' : 'Marketplace'}
          </button>
          <button
            type="button"
            onClick={() => { setTab('rentals'); setCatFilter(''); setQ(''); setFeedback(''); }}
            className={`px-4 py-2 text-xs font-semibold rounded-md transition-colors whitespace-nowrap ${
              tab === 'rentals' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600'
            }`}
          >
            {lang === 'bn' ? 'বাসা ভাড়া (টু-লেট)' : 'House Rent / To-Let'}
          </button>
          <button
            type="button"
            onClick={() => { setTab('jobs'); setCatFilter(''); setQ(''); setFeedback(''); }}
            className={`px-4 py-2 text-xs font-semibold rounded-md transition-colors whitespace-nowrap ${
              tab === 'jobs' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600'
            }`}
          >
            {lang === 'bn' ? 'চাকরি (Jobs)' : 'Jobs'}
          </button>
        </div>
      </div>

      {feedback && (
        <div className="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900">
          {feedback}
        </div>
      )}

      {/* Filter Bar */}
      <div className="rounded-xl border border-slate-200 bg-white p-4 mb-6 grid grid-cols-1 sm:grid-cols-4 gap-3">
        {tab === 'marketplace' ? (
          <>
            <input
              type="text"
              value={q}
              onChange={(e) => setQ(e.target.value)}
              placeholder={lang === 'bn' ? 'পণ্য খুঁজুন...' : 'Search products...'}
              className="rounded-lg border border-slate-300 px-3 py-2 text-sm"
            />
            <select value={catFilter} onChange={(e) => setCatFilter(e.target.value)} className="rounded-lg border border-slate-300 px-3 py-2 text-sm">
              <option value="">{lang === 'bn' ? 'সকল ক্যাটাগরি' : 'All Categories'}</option>
              {marketCategories.map((c) => (
                <option key={c.id} value={c.slug}>{lang === 'bn' ? c.name_bn : c.name_en}</option>
              ))}
            </select>
          </>
        ) : tab === 'rentals' ? (
          <select value={catFilter} onChange={(e) => setCatFilter(e.target.value)} className="sm:col-span-2 rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <option value="">{lang === 'bn' ? 'সকল প্রপার্টি ধরন' : 'All Property Types'}</option>
            {RENTAL_CATEGORIES.map((c) => (
              <option key={c.value} value={c.value}>{lang === 'bn' ? c.bn : c.en}</option>
            ))}
          </select>
        ) : (
          <>
            <input
              type="text"
              value={q}
              onChange={(e) => setQ(e.target.value)}
              placeholder={lang === 'bn' ? 'পদবি, কোম্পানি বা এলাকা খুঁজুন...' : 'Search position or company...'}
              className="rounded-lg border border-slate-300 px-3 py-2 text-sm"
            />
            <select value={catFilter} onChange={(e) => setCatFilter(e.target.value)} className="rounded-lg border border-slate-300 px-3 py-2 text-sm">
              <option value="">{lang === 'bn' ? 'সকল চাকরির ধরন' : 'All Job Types'}</option>
              {JOB_TYPES.map((jt) => (
                <option key={jt.value} value={jt.value}>{lang === 'bn' ? jt.bn : jt.en}</option>
              ))}
            </select>
          </>
        )}

        <select value={upazilaFilter} onChange={(e) => setUpazilaFilter(e.target.value)} className="rounded-lg border border-slate-300 px-3 py-2 text-sm">
          <option value="">{lang === 'bn' ? 'সকল উপজেলা' : 'All Upazilas'}</option>
          {GAZIPUR_UPAZILAS.map((u) => (
            <option key={u.value} value={u.value}>{lang === 'bn' ? u.bn : u.en}</option>
          ))}
        </select>

        <button type="button" onClick={loadData} className="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white">
          {lang === 'bn' ? 'খুঁজুন' : 'Filter'}
        </button>
      </div>

      {/* Create Form */}
      {tab === 'marketplace' ? (
        <div className="rounded-xl border border-slate-200 bg-white p-5 mb-8">
          {user ? (
            <form onSubmit={submitMarketplace} className="space-y-3">
              <h2 className="text-base font-bold text-slate-900">
                {lang === 'bn' ? 'নতুন পণ্য বিজ্ঞাপন দিন' : 'Post Marketplace Listing'}
              </h2>
              <div className="grid grid-cols-1 sm:grid-cols-4 gap-3">
                <input type="text" required value={mTitle} onChange={(e) => setMTitle(e.target.value)} placeholder={lang === 'bn' ? 'পণ্যের নাম' : 'Product title'} className="rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                <input type="number" required value={mPrice} onChange={(e) => setMPrice(e.target.value)} placeholder={lang === 'bn' ? 'মূল্য (৳)' : 'Price (BDT)'} className="rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                <select value={mCatId} onChange={(e) => setMCatId(Number(e.target.value))} className="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                  {marketCategories.map((c) => <option key={c.id} value={c.id}>{lang === 'bn' ? c.name_bn : c.name_en}</option>)}
                </select>
                <select value={mCond} onChange={(e) => setMCond(e.target.value)} className="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                  <option value="new">New</option>
                  <option value="like_new">Like New</option>
                  <option value="used">Used</option>
                </select>
              </div>
              <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <input type="text" required value={mLoc} onChange={(e) => setMLoc(e.target.value)} placeholder={lang === 'bn' ? 'এলাকা (যেমন: জয়দেবপুর)' : 'Location'} className="rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                <select value={mUpazila} onChange={(e) => setMUpazila(e.target.value)} className="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                  {GAZIPUR_UPAZILAS.map((u) => <option key={u.value} value={u.value}>{lang === 'bn' ? u.bn : u.en}</option>)}
                </select>
                <input type="text" required value={mPhone} onChange={(e) => setMPhone(e.target.value)} placeholder={lang === 'bn' ? 'ফোন নম্বর' : 'Contact Phone'} className="rounded-lg border border-slate-300 px-3 py-2 text-sm" />
              </div>
              <textarea rows={2} required value={mDesc} onChange={(e) => setMDesc(e.target.value)} placeholder={lang === 'bn' ? 'পণ্যের বিবরণ...' : 'Description...'} className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
              <div className="flex flex-wrap items-center justify-between gap-3">
                <label className="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-xs font-medium cursor-pointer">
                  <ImagePlus className="h-4 w-4" />
                  <span>{mImg ? 'ছবি যুক্ত হয়েছে' : lang === 'bn' ? 'পণ্যের ছবি' : 'Upload Photo'}</span>
                  <input type="file" accept="image/jpeg,image/png,image/webp" onChange={handleUploadMarketImg} className="hidden" />
                </label>
                <button type="submit" className="rounded-lg bg-emerald-800 px-5 py-2 text-sm font-semibold text-white hover:bg-emerald-900">
                  {lang === 'bn' ? 'পণ্য প্রকাশ করুন' : 'Publish Listing'}
                </button>
              </div>
            </form>
          ) : (
            <div className="flex items-center justify-between gap-4">
              <span className="text-sm text-slate-600">{lang === 'bn' ? 'পণ্য বিক্রি করতে লগইন করুন।' : 'Sign in to post a product for sale.'}</span>
              <button type="button" onClick={onRequireAuth} className="rounded-lg bg-emerald-800 px-4 py-2 text-sm font-semibold text-white">
                {lang === 'bn' ? 'বিজ্ঞাপন দিন' : 'Post Ad'}
              </button>
            </div>
          )}
        </div>
      ) : tab === 'rentals' ? (
        <div className="rounded-xl border border-slate-200 bg-white p-5 mb-8">
          {isLandlord ? (
            <form onSubmit={submitRental} className="space-y-3">
              <h2 className="text-base font-bold text-slate-900">
                {lang === 'bn' ? 'নতুন টু-লেট বিজ্ঞাপন দিন (অনুমোদিত বাড়িওয়ালা)' : 'Post Rental Listing (Approved Landlord)'}
              </h2>
              <div className="grid grid-cols-1 sm:grid-cols-4 gap-3">
                <input type="text" required value={rTitle} onChange={(e) => setRTitle(e.target.value)} placeholder={lang === 'bn' ? 'শিরোনাম' : 'Property title'} className="sm:col-span-2 rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                <select value={rCat} onChange={(e) => setRCat(e.target.value)} className="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                  {RENTAL_CATEGORIES.map((c) => <option key={c.value} value={c.value}>{lang === 'bn' ? c.bn : c.en}</option>)}
                </select>
                <input type="number" required value={rRent} onChange={(e) => setRRent(e.target.value)} placeholder={lang === 'bn' ? 'মাসিক ভাড়া (৳)' : 'Monthly Rent'} className="rounded-lg border border-slate-300 px-3 py-2 text-sm" />
              </div>
              <div className="grid grid-cols-2 sm:grid-cols-5 gap-3">
                <input type="number" value={rAdv} onChange={(e) => setRAdv(e.target.value)} placeholder={lang === 'bn' ? 'অগ্রিম (৳)' : 'Advance'} className="rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                <input type="number" value={rBeds} onChange={(e) => setRBeds(e.target.value)} placeholder="Beds" className="rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                <input type="number" value={rBaths} onChange={(e) => setRBaths(e.target.value)} placeholder="Baths" className="rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                <input type="text" value={rFloor} onChange={(e) => setRFloor(e.target.value)} placeholder={lang === 'bn' ? 'তলা' : 'Floor'} className="rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                <input type="text" required value={rPhone} onChange={(e) => setRPhone(e.target.value)} placeholder={lang === 'bn' ? 'ফোন' : 'Phone'} className="rounded-lg border border-slate-300 px-3 py-2 text-sm" />
              </div>
              <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <input type="text" required value={rLoc} onChange={(e) => setRLoc(e.target.value)} placeholder={lang === 'bn' ? 'ঠিকানা / রোড' : 'Address'} className="rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                <select value={rUpazila} onChange={(e) => setRUpazila(e.target.value)} className="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                  {GAZIPUR_UPAZILAS.map((u) => <option key={u.value} value={u.value}>{lang === 'bn' ? u.bn : u.en}</option>)}
                </select>
                <input type="text" value={rFac} onChange={(e) => setRFac(e.target.value)} placeholder={lang === 'bn' ? 'সুবিধাসমূহ (লিফট, গ্যাস)' : 'Facilities'} className="rounded-lg border border-slate-300 px-3 py-2 text-sm" />
              </div>
              <textarea rows={2} value={rDesc} onChange={(e) => setRDesc(e.target.value)} placeholder={lang === 'bn' ? 'বিস্তারিত বিবরণ...' : 'Details...'} className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
              <div className="flex flex-wrap items-center justify-between gap-3">
                <label className="flex items-center gap-2 text-xs text-slate-700 cursor-pointer">
                  <input type="checkbox" checked={rHideContact} onChange={(e) => setRHideContact(e.target.checked)} />
                  <span>{lang === 'bn' ? 'অতিথি ভিজিটরদের থেকে ফোন নম্বর গোপন রাখুন' : 'Protect contact phone from logged-out visitors'}</span>
                </label>
                <button type="submit" className="rounded-lg bg-emerald-800 px-5 py-2 text-sm font-semibold text-white">
                  {lang === 'bn' ? 'টু-লেট প্রকাশ করুন' : 'Publish To-Let'}
                </button>
              </div>
            </form>
          ) : (
            <div className="flex flex-wrap items-center justify-between gap-3 text-sm text-slate-600">
              <span>
                {user
                  ? lang === 'bn'
                    ? 'টু-লেট বিজ্ঞাপন দিতে অ্যাডমিন কর্তৃক অনুমোদিত বাড়িওয়ালা (Landlord) ক্যাটাগরি প্রয়োজন।'
                    : 'An approved Landlord account category is required to publish rentals.'
                  : lang === 'bn'
                  ? 'টু-লেট দিতে বাড়িওয়ালা হিসেবে নিবন্ধন ও লগইন করুন।'
                  : 'Sign in with an approved Landlord account to publish rentals.'}
              </span>
              {!user && (
                <button type="button" onClick={onRequireAuth} className="rounded-lg bg-emerald-800 px-4 py-2 text-sm font-semibold text-white">
                  {lang === 'bn' ? 'লগইন করুন' : 'Sign In'}
                </button>
              )}
            </div>
          )}
        </div>
      ) : (
        <div className="rounded-xl border border-slate-200 bg-white p-5 mb-8">
          {isJobPoster ? (
            <form onSubmit={submitJob} className="space-y-3">
              <h2 className="text-base font-bold text-slate-900">
                {lang === 'bn' ? 'নতুন চাকরির বিজ্ঞপ্তি পোস্ট করুন (অনুমোদিত চাকরিদাতা)' : 'Post a Job Circular (Approved Job Poster)'}
              </h2>
              <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <input type="text" required value={jCompany} onChange={(e) => setJCompany(e.target.value)} placeholder={lang === 'bn' ? 'কোম্পানির নাম *' : 'Company Name *'} className="rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                <input type="text" required value={jTitle} onChange={(e) => setJTitle(e.target.value)} placeholder={lang === 'bn' ? 'পদবি (Position) *' : 'Job Title / Position *'} className="rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                <input type="text" required value={jSalary} onChange={(e) => setJSalary(e.target.value)} placeholder={lang === 'bn' ? 'বেতন (যেমন: ২০,০০০ টাকা) *' : 'Salary (e.g. 20,000 BDT) *'} className="rounded-lg border border-slate-300 px-3 py-2 text-sm" />
              </div>
              <div className="grid grid-cols-1 sm:grid-cols-4 gap-3">
                <input type="text" required value={jLocation} onChange={(e) => setJLocation(e.target.value)} placeholder={lang === 'bn' ? 'কর্মস্থল / লোকেশন *' : 'Workplace Location *'} className="rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                <select value={jUpazila} onChange={(e) => setJUpazila(e.target.value)} className="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                  {GAZIPUR_UPAZILAS.map((u) => <option key={u.value} value={u.value}>{lang === 'bn' ? u.bn : u.en}</option>)}
                </select>
                <input type="text" required value={jContact} onChange={(e) => setJContact(e.target.value)} placeholder={lang === 'bn' ? 'যোগাযোগ নম্বর বা ইমেইল *' : 'Phone or Email *'} className="rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                <select value={jType} onChange={(e) => setJType(e.target.value)} className="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                  {JOB_TYPES.map((jt) => <option key={jt.value} value={jt.value}>{lang === 'bn' ? jt.bn : jt.en}</option>)}
                </select>
              </div>
              <div className="grid grid-cols-1 sm:grid-cols-4 gap-3">
                <input type="date" value={jDeadline} onChange={(e) => setJDeadline(e.target.value)} className="rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                <textarea rows={2} value={jDesc} onChange={(e) => setJDesc(e.target.value)} placeholder={lang === 'bn' ? 'কাজের বিবরণ ও শিক্ষাগত যোগ্যতা...' : 'Job description & requirements...'} className="sm:col-span-3 rounded-lg border border-slate-300 px-3 py-2 text-sm" />
              </div>
              <div className="flex justify-end">
                <button type="submit" className="rounded-lg bg-emerald-800 px-5 py-2 text-sm font-semibold text-white hover:bg-emerald-900">
                  {lang === 'bn' ? 'চাকরি প্রকাশ করুন' : 'Publish Job'}
                </button>
              </div>
            </form>
          ) : (
            <div className="flex flex-wrap items-center justify-between gap-3 text-sm text-slate-600">
              <span>
                {user
                  ? lang === 'bn'
                    ? 'চাকরির বিজ্ঞপ্তি পোস্ট করতে অ্যাডমিন কর্তৃক অনুমোদিত চাকরিদাতা (Job Poster) রোল প্রয়োজন।'
                    : 'An approved Job Poster role is required to publish job openings.'
                  : lang === 'bn'
                  ? 'চাকরি পোস্ট করতে চাকরিদাতা (Job Poster) হিসেবে নিবন্ধন ও লগইন করুন।'
                  : 'Sign in with an approved Job Poster account to publish jobs.'}
              </span>
              {!user && (
                <button type="button" onClick={onRequireAuth} className="rounded-lg bg-emerald-800 px-4 py-2 text-sm font-semibold text-white">
                  {lang === 'bn' ? 'লগইন করুন' : 'Sign In'}
                </button>
              )}
            </div>
          )}
        </div>
      )}

      {/* Results Grid */}
      {tab === 'marketplace' ? (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {listings.map((item) => (
            <div key={item.id} className="rounded-xl border border-slate-200 bg-white overflow-hidden flex flex-col">
              <div className="h-44 bg-slate-100 flex items-center justify-center overflow-hidden">
                {item.image_path ? (
                  <img src={item.image_path} alt={item.title} referrerPolicy="no-referrer" className="h-full w-full object-cover" loading="lazy" />
                ) : (
                  <ShoppingBag className="h-8 w-8 text-slate-400" />
                )}
              </div>
              <div className="p-5 flex-1 flex flex-col">
                <div className="text-xs text-slate-500 mb-1">
                  {lang === 'bn' ? item.category_bn : item.category_en} · {item.upazila} · {item.item_condition}
                </div>
                <h2 className="text-base font-bold text-slate-900 mb-1">{item.title}</h2>
                <div className="text-base font-bold text-emerald-800 tabular-nums mb-2">৳ {item.price.toLocaleString()}</div>
                <p className="text-sm text-slate-600 flex-1 mb-4">{item.description}</p>
                <div className="border-t border-slate-100 pt-3 text-xs text-slate-600 flex items-center justify-between">
                  <span>{item.seller_name} · {item.location}</span>
                  {item.contact_phone ? (
                    <span className="font-semibold text-slate-900 tabular-nums">{item.contact_phone}</span>
                  ) : (
                    <button type="button" onClick={onRequireAuth} className="inline-flex items-center gap-1 font-semibold text-emerald-800 hover:underline">
                      <Lock className="h-3 w-3" /> {lang === 'bn' ? 'নম্বর দেখুন' : 'Show Phone'}
                    </button>
                  )}
                </div>
              </div>
            </div>
          ))}
        </div>
      ) : tab === 'rentals' ? (
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          {rentals.map((r) => (
            <div key={r.id} className="rounded-xl border border-slate-200 bg-white overflow-hidden flex flex-col">
              <div className="h-48 bg-slate-100 flex items-center justify-center overflow-hidden">
                {r.image_path ? (
                  <img src={r.image_path} alt={r.title} referrerPolicy="no-referrer" className="h-full w-full object-cover" loading="lazy" />
                ) : (
                  <Building2 className="h-8 w-8 text-slate-400" />
                )}
              </div>
              <div className="p-5 flex-1 flex flex-col">
                <div className="text-xs text-slate-500 mb-1">{r.category} · {r.upazila} · {r.location}</div>
                <h2 className="text-lg font-bold text-slate-900 mb-1">{r.title}</h2>
                <div className="text-base font-bold text-emerald-800 tabular-nums mb-2">
                  ৳ {r.monthly_rent.toLocaleString()} / {lang === 'bn' ? 'মাস' : 'mo'}
                  {r.advance_amount > 0 && <span className="text-xs font-normal text-slate-500 ml-2">({lang === 'bn' ? 'অগ্রিম' : 'Advance'}: ৳ {r.advance_amount.toLocaleString()})</span>}
                </div>
                <div className="text-xs text-slate-600 mb-2 tabular-nums">
                  {r.bedrooms} Bed · {r.bathrooms} Bath · {r.floor_number || 'N/A'} {r.facilities ? `· ${r.facilities}` : ''}
                </div>
                <p className="text-sm text-slate-600 flex-1 mb-4">{r.description}</p>
                <div className="border-t border-slate-100 pt-3 flex items-center justify-between text-xs">
                  <span className="text-slate-600">{r.landlord_name}</span>
                  {r.contact_phone ? (
                    <span className="font-semibold text-slate-900 tabular-nums">{r.contact_phone}</span>
                  ) : (
                    <button type="button" onClick={onRequireAuth} className="inline-flex items-center gap-1 font-semibold text-emerald-800 hover:underline">
                      <Lock className="h-3 w-3" /> {lang === 'bn' ? 'যোগাযোগ নম্বর দেখুন' : 'Sign in for Contact'}
                    </button>
                  )}
                </div>
              </div>
            </div>
          ))}
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          {jobs.map((j) => {
            const typeLabel = JOB_TYPES.find((t) => t.value === j.job_type);
            return (
              <div key={j.id} className="rounded-xl border border-slate-200 bg-white p-5 flex flex-col">
                <div className="flex items-start justify-between gap-3 mb-2">
                  <div>
                    <div className="text-xs text-slate-500 mb-1">
                      {lang === 'bn' ? typeLabel?.bn : typeLabel?.en} · {j.upazila}
                      {j.deadline ? ` · ${lang === 'bn' ? 'শেষ তারিখ:' : 'Deadline:'} ${j.deadline}` : ''}
                    </div>
                    <h2 className="text-lg font-bold text-slate-900">{j.job_title}</h2>
                    <div className="text-sm font-semibold text-slate-700">{j.company_name}</div>
                  </div>
                  <Briefcase className="h-6 w-6 text-emerald-800 shrink-0" />
                </div>
                <div className="text-sm font-bold text-emerald-800 mb-1">
                  {lang === 'bn' ? 'বেতন:' : 'Salary:'} {j.salary}
                </div>
                <div className="text-xs text-slate-600 mb-2">
                  <strong>{lang === 'bn' ? 'লোকেশন:' : 'Location:'}</strong> {j.location}
                </div>
                <p className="text-sm text-slate-600 flex-1 mb-4">{j.description}</p>
                <div className="border-t border-slate-100 pt-3 flex flex-wrap items-center justify-between gap-2 text-xs">
                  <span className="font-semibold text-slate-900">
                    {lang === 'bn' ? 'যোগাযোগ:' : 'Contact:'} {j.contact_info}
                  </span>
                  <span className="text-slate-500">{j.poster_name}</span>
                </div>
              </div>
            );
          })}
        </div>
      )}
    </div>
  );
};
