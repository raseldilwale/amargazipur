import React, { useEffect, useState } from 'react';
import { GraduationCap, Droplets, Store, Lock } from 'lucide-react';
import {
  BloodDonor,
  BloodRequest,
  BusinessListing,
  EducationInstitution,
  GAZIPUR_UPAZILAS,
  Lang,
  User,
} from '../types';

interface Props {
  lang: Lang;
  user: User | null;
  token: string | null;
  initialSubTab?: 'education' | 'blood' | 'businesses';
  onRequireAuth: () => void;
}

export const ServicesSection: React.FC<Props> = ({
  lang,
  user,
  token,
  initialSubTab = 'education',
  onRequireAuth,
}) => {
  const [subTab, setSubTab] = useState<'education' | 'blood' | 'businesses'>(initialSubTab);
  const [institutions, setInstitutions] = useState<EducationInstitution[]>([]);
  const [donors, setDonors] = useState<BloodDonor[]>([]);
  const [bloodRequests, setBloodRequests] = useState<BloodRequest[]>([]);
  const [businesses, setBusinesses] = useState<BusinessListing[]>([]);
  const [groupFilter, setGroupFilter] = useState('');
  const [upazilaFilter, setUpazilaFilter] = useState('');
  const [feedback, setFeedback] = useState('');

  // Blood request form
  const [patientName, setPatientName] = useState('');
  const [reqGroup, setReqGroup] = useState('O+');
  const [units, setUnits] = useState('1');
  const [hospital, setHospital] = useState('');
  const [reqPhone, setReqPhone] = useState('');

  // Donor form
  const [donorGroup, setDonorGroup] = useState('O+');
  const [donorArea, setDonorArea] = useState('');
  const [donorPhone, setDonorPhone] = useState('');
  const [donorConsent, setDonorConsent] = useState(true);

  useEffect(() => {
    setSubTab(initialSubTab);
  }, [initialSubTab]);

  const loadTab = async () => {
    const headers = token ? { Authorization: `Bearer ${token}` } : undefined;
    if (subTab === 'education') {
      const res = await fetch(`/api/education?upazila=${encodeURIComponent(upazilaFilter)}`);
      const d = await res.json();
      setInstitutions(d.institutions || []);
    } else if (subTab === 'blood') {
      const res = await fetch(`/api/blood?group=${encodeURIComponent(groupFilter)}&upazila=${encodeURIComponent(upazilaFilter)}`, { headers });
      const d = await res.json();
      setDonors(d.donors || []);
      setBloodRequests(d.requests || []);
    } else if (subTab === 'businesses') {
      const res = await fetch(`/api/businesses?upazila=${encodeURIComponent(upazilaFilter)}`);
      const d = await res.json();
      setBusinesses(d.businesses || []);
    }
  };

  useEffect(() => {
    loadTab();
  }, [subTab, groupFilter, upazilaFilter, token]);

  const submitBloodRequest = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!token) return onRequireAuth();
    const res = await fetch('/api/blood/requests', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${token}` },
      body: JSON.stringify({
        patient_name: patientName,
        blood_group: reqGroup,
        units_needed: Number(units),
        hospital_name: hospital,
        upazila: upazilaFilter || 'Gazipur Sadar',
        contact_phone: reqPhone,
      }),
    });
    const d = await res.json();
    setFeedback(d.message || d.error || '');
    if (res.ok) {
      setPatientName('');
      setHospital('');
      setReqPhone('');
      loadTab();
    }
  };

  const submitDonor = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!token) return onRequireAuth();
    const res = await fetch('/api/blood/donors', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${token}` },
      body: JSON.stringify({
        blood_group: donorGroup,
        area: donorArea,
        upazila: upazilaFilter || 'Gazipur Sadar',
        phone: donorPhone,
        consent_public_contact: donorConsent,
      }),
    });
    const d = await res.json();
    setFeedback(d.message || d.error || '');
    if (res.ok) {
      setDonorArea('');
      setDonorPhone('');
      loadTab();
    }
  };

  return (
    <div className="max-w-6xl mx-auto px-4 sm:px-6 py-8">
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <h1 className="text-2xl sm:text-3xl font-bold text-slate-900">
          {lang === 'bn' ? 'নাগরিক সেবা ও ডিরেক্টরি' : 'District Directories'}
        </h1>
        <div className="flex flex-wrap gap-1 p-1 bg-slate-100 rounded-lg self-start">
          {[
            { id: 'education', bn: 'শিক্ষা প্রতিষ্ঠান', en: 'Education' },
            { id: 'blood', bn: 'রক্তদাতা', en: 'Blood Donors' },
            { id: 'businesses', bn: 'ব্যবসা ডিরেক্টরি', en: 'Businesses' },
          ].map((item) => (
            <button
              key={item.id}
              type="button"
              onClick={() => { setSubTab(item.id as typeof subTab); setFeedback(''); }}
              className={`px-3 py-1.5 text-xs font-semibold rounded-md whitespace-nowrap transition-colors ${
                subTab === item.id ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600'
              }`}
            >
              {lang === 'bn' ? item.bn : item.en}
            </button>
          ))}
        </div>
      </div>

      {feedback && (
        <div className="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900">
          {feedback}
        </div>
      )}

      <div className="rounded-xl border border-slate-200 bg-white p-4 mb-6 flex flex-wrap gap-3">
        {subTab === 'blood' && (
          <select value={groupFilter} onChange={(e) => setGroupFilter(e.target.value)} className="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <option value="">{lang === 'bn' ? 'সকল রক্তের গ্রুপ' : 'All Blood Groups'}</option>
            {['A+','A-','B+','B-','AB+','AB-','O+','O-'].map((g) => <option key={g} value={g}>{g}</option>)}
          </select>
        )}
        <select value={upazilaFilter} onChange={(e) => setUpazilaFilter(e.target.value)} className="rounded-lg border border-slate-300 px-3 py-2 text-sm">
          <option value="">{lang === 'bn' ? 'সকল উপজেলা' : 'All Upazilas'}</option>
          {GAZIPUR_UPAZILAS.map((u) => <option key={u.value} value={u.value}>{lang === 'bn' ? u.bn : u.en}</option>)}
        </select>
      </div>

      {subTab === 'education' && (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {institutions.map((inst) => (
            <div key={inst.id} className="rounded-xl border border-slate-200 bg-white overflow-hidden flex flex-col">
              <div className="h-40 bg-slate-100 flex items-center justify-center overflow-hidden">
                {inst.cover_image ? (
                  <img src={inst.cover_image} alt={inst.name} referrerPolicy="no-referrer" className="h-full w-full object-cover" loading="lazy" />
                ) : (
                  <GraduationCap className="h-8 w-8 text-slate-400" />
                )}
              </div>
              <div className="p-5 flex-1 flex flex-col">
                <div className="text-xs text-slate-500 mb-1">{inst.type} · {inst.upazila}</div>
                <h2 className="text-base font-bold text-slate-900 mb-1">{inst.name}</h2>
                <div className="text-xs text-slate-600 mb-2">{inst.address}</div>
                <p className="text-sm text-slate-600 flex-1 mb-3">{inst.description}</p>
                {inst.courses && <div className="text-xs text-slate-700 border-t border-slate-100 pt-2"><strong>কোর্স:</strong> {inst.courses}</div>}
              </div>
            </div>
          ))}
        </div>
      )}

      {subTab === 'blood' && (
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <div className="lg:col-span-2 space-y-4">
            <h2 className="text-lg font-bold text-slate-900">
              {lang === 'bn' ? 'নিবন্ধিত রক্তদাতা তালিকা' : 'Registered Blood Donors'}
            </h2>
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              {donors.map((d) => (
                <div key={d.id} className="rounded-xl border border-slate-200 bg-white p-4">
                  <div className="flex items-center justify-between mb-1">
                    <span className="text-xl font-bold text-red-700 tabular-nums">{d.blood_group}</span>
                    <span className="text-xs text-emerald-800 font-medium">
                      {d.is_available ? (lang === 'bn' ? 'রক্তদানে প্রস্তুত' : 'Available') : 'Unavailable'}
                    </span>
                  </div>
                  <div className="font-semibold text-slate-900">{d.donor_name}</div>
                  <div className="text-xs text-slate-500 mb-3">{d.area} · {d.upazila}</div>
                  {d.consent_public_contact && d.phone ? (
                    <div className="text-xs font-semibold text-slate-900 tabular-nums">{d.phone} ({d.contact_preference})</div>
                  ) : !user ? (
                    <button type="button" onClick={onRequireAuth} className="inline-flex items-center gap-1 text-xs font-semibold text-red-700 hover:underline">
                      <Lock className="h-3 w-3" /> {lang === 'bn' ? 'যোগাযোগ তথ্য দেখতে লগইন করুন' : 'Sign in for contact'}
                    </button>
                  ) : (
                    <div className="text-xs text-slate-500">{lang === 'bn' ? 'দাতার সম্মতিতে সরাসরি নম্বর গোপন রয়েছে' : 'Donor opted out of direct phone display'}</div>
                  )}
                </div>
              ))}
            </div>

            {user && (
              <form onSubmit={submitDonor} className="rounded-xl border border-slate-200 bg-white p-5 space-y-3 mt-4">
                <h3 className="text-sm font-bold text-slate-900">{lang === 'bn' ? 'রক্তদাতা হিসেবে যুক্ত হোন' : 'Register as a Blood Donor'}</h3>
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                  <select value={donorGroup} onChange={(e) => setDonorGroup(e.target.value)} className="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    {['A+','A-','B+','B-','AB+','AB-','O+','O-'].map((g) => <option key={g} value={g}>{g}</option>)}
                  </select>
                  <input type="text" required value={donorArea} onChange={(e) => setDonorArea(e.target.value)} placeholder={lang === 'bn' ? 'এলাকা' : 'Area'} className="rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                  <input type="text" required value={donorPhone} onChange={(e) => setDonorPhone(e.target.value)} placeholder={lang === 'bn' ? 'ফোন নম্বর' : 'Phone'} className="rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                </div>
                <label className="flex items-center gap-2 text-xs text-slate-700">
                  <input type="checkbox" checked={donorConsent} onChange={(e) => setDonorConsent(e.target.checked)} />
                  <span>{lang === 'bn' ? 'নিবন্ধিত সদস্যদের কাছে আমার যোগাযোগ নম্বর প্রদর্শনের সম্মতি দিচ্ছি' : 'I consent to share my contact number with signed-in members'}</span>
                </label>
                <button type="submit" className="rounded-lg bg-red-700 px-4 py-2 text-xs font-semibold text-white hover:bg-red-800">
                  {lang === 'bn' ? 'রক্তদাতা হিসেবে নিবন্ধন করুন' : 'Save Donor Profile'}
                </button>
              </form>
            )}
          </div>

          <div className="space-y-4">
            <div className="rounded-xl border border-slate-200 bg-white p-5">
              <div className="flex items-center gap-2 text-red-700 font-bold text-base mb-3">
                <Droplets className="h-4 w-4" />
                <span>{lang === 'bn' ? 'জরুরি রক্তের অনুরোধ' : 'Urgent Blood Requests'}</span>
              </div>
              <div className="space-y-3 mb-4">
                {bloodRequests.map((req) => (
                  <div key={req.id} className="border-b border-slate-100 pb-3 last:border-0 text-xs">
                    <div className="font-bold text-slate-900">{req.blood_group} ({req.units_needed} units) — {req.patient_name}</div>
                    <div className="text-slate-600">{req.hospital_name} · {req.upazila}</div>
                    <div className="text-slate-500 mt-0.5">{req.needed_date} {req.contact_phone ? `· ${req.contact_phone}` : ''}</div>
                  </div>
                ))}
              </div>
              {user ? (
                <form onSubmit={submitBloodRequest} className="space-y-2 border-t border-slate-100 pt-3">
                  <div className="text-xs font-bold text-slate-900">{lang === 'bn' ? 'নতুন অনুরোধ পোস্ট করুন' : 'Post Blood Request'}</div>
                  <input type="text" required value={patientName} onChange={(e) => setPatientName(e.target.value)} placeholder={lang === 'bn' ? 'রোগীর নাম' : 'Patient name'} className="w-full rounded border border-slate-300 px-2.5 py-1.5 text-xs" />
                  <div className="grid grid-cols-2 gap-2">
                    <select value={reqGroup} onChange={(e) => setReqGroup(e.target.value)} className="rounded border border-slate-300 px-2 py-1.5 text-xs">
                      {['A+','A-','B+','B-','AB+','AB-','O+','O-'].map((g) => <option key={g} value={g}>{g}</option>)}
                    </select>
                    <input type="number" min={1} value={units} onChange={(e) => setUnits(e.target.value)} className="rounded border border-slate-300 px-2.5 py-1.5 text-xs" />
                  </div>
                  <input type="text" required value={hospital} onChange={(e) => setHospital(e.target.value)} placeholder={lang === 'bn' ? 'হাসপাতালের নাম' : 'Hospital'} className="w-full rounded border border-slate-300 px-2.5 py-1.5 text-xs" />
                  <input type="text" required value={reqPhone} onChange={(e) => setReqPhone(e.target.value)} placeholder={lang === 'bn' ? 'ফোন নম্বর' : 'Phone'} className="w-full rounded border border-slate-300 px-2.5 py-1.5 text-xs" />
                  <button type="submit" className="w-full rounded-lg bg-red-700 py-2 text-xs font-semibold text-white">
                    {lang === 'bn' ? 'অনুরোধ প্রকাশ করুন' : 'Post Request'}
                  </button>
                </form>
              ) : (
                <button type="button" onClick={onRequireAuth} className="w-full rounded-lg border border-red-200 py-2 text-xs font-semibold text-red-700 hover:bg-red-50">
                  {lang === 'bn' ? 'রক্তের অনুরোধ করতে লগইন করুন' : 'Sign in to Post Request'}
                </button>
              )}
            </div>
          </div>
        </div>
      )}

      {subTab === 'businesses' && (
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          {businesses.map((b) => (
            <div key={b.id} className="rounded-xl border border-slate-200 bg-white p-5">
              <div className="flex items-center gap-2 text-xs text-slate-500 mb-1">
                <Store className="h-3.5 w-3.5" />
                <span>{b.category}</span>
                <span aria-hidden="true">·</span>
                <span>{b.upazila}</span>
                {b.is_featured === 1 && <><span aria-hidden="true">·</span><span className="text-emerald-800 font-semibold">Featured</span></>}
              </div>
              <h2 className="text-lg font-bold text-slate-900 mb-1">{b.name}</h2>
              <div className="text-xs text-slate-600 mb-2">{b.address} · {b.hours}</div>
              <p className="text-sm text-slate-600 mb-3">{b.description}</p>
              <div className="text-xs font-semibold text-slate-900 tabular-nums">Phone: {b.phone} {b.map_coords ? `· Map: ${b.map_coords}` : ''}</div>
            </div>
          ))}
        </div>
      )}

    </div>
  );
};
