export type Lang = 'bn' | 'en';

export type ActivePage =
  | 'home'
  | 'news'
  | 'community'
  | 'market_rent'
  | 'services'
  | 'dashboard'
  | 'admin';

export interface UserCategory {
  id: number;
  category_slug: string;
  status: 'pending' | 'approved' | 'rejected';
}

export interface User {
  id: number;
  role: 'admin' | 'editor' | 'moderator' | 'member';
  full_name: string;
  email: string;
  upazila: string;
  phone: string;
  bio: string;
  status: string;
  categories: UserCategory[];
}

export interface NewsCategory {
  id: number;
  slug: string;
  name_bn: string;
  name_en: string;
}

export interface NewsArticle {
  id: number;
  category_id: number;
  category_bn: string;
  category_en: string;
  category_slug: string;
  author_id: number;
  author_name: string;
  author_bio?: string;
  slug: string;
  title: string;
  summary: string;
  content: string;
  featured_image: string | null;
  upazila: string;
  is_featured: number;
  views_count: number;
  status: string;
  created_at: string;
}

export interface CommentReply {
  id: number;
  comment_id: number;
  author_name: string;
  body: string;
  created_at: string;
}

export interface PostComment {
  id: number;
  post_id: number;
  author_name: string;
  body: string;
  created_at: string;
  replies: CommentReply[];
}

export interface CommunityPost {
  id: number;
  user_id: number;
  author_name: string;
  category: string;
  upazila: string;
  title: string;
  content: string;
  image_path: string | null;
  status: string;
  moderation_reason?: string | null;
  likes_count: number;
  liked_by_me: boolean;
  comments: PostComment[];
  created_at: string;
}

export interface MarketplaceCategory {
  id: number;
  slug: string;
  name_bn: string;
  name_en: string;
}

export interface MarketplaceListing {
  id: number;
  user_id: number;
  category_id: number;
  category_bn: string;
  category_en: string;
  seller_name: string;
  title: string;
  description: string;
  price: number;
  item_condition: string;
  location: string;
  upazila: string;
  contact_phone: string | null;
  image_path: string | null;
  status: string;
  created_at: string;
}

export interface RentalListing {
  id: number;
  user_id: number;
  landlord_name: string;
  category: string;
  title: string;
  description: string;
  monthly_rent: number;
  advance_amount: number;
  location: string;
  upazila: string;
  bedrooms: number;
  bathrooms: number;
  floor_number: string;
  facilities: string;
  available_from: string;
  contact_phone: string | null;
  hide_contact_public: number;
  image_path: string | null;
  status: string;
  created_at: string;
}

export interface JobListing {
  id: number;
  user_id: number;
  poster_name: string;
  company_name: string;
  job_title: string;
  job_type: string;
  salary: string;
  location: string;
  upazila: string;
  contact_info: string;
  deadline: string;
  description: string;
  image_path: string | null;
  status: string;
  created_at: string;
}

export interface EducationInstitution {
  id: number;
  type: string;
  name: string;
  upazila: string;
  address: string;
  phone: string;
  website: string;
  courses: string;
  description: string;
  cover_image: string | null;
}

export interface BloodDonor {
  id: number;
  donor_name: string;
  blood_group: string;
  area: string;
  upazila: string;
  phone: string | null;
  contact_preference: string;
  consent_public_contact: number;
  is_available: number;
  last_donation_date: string;
}

export interface BloodRequest {
  id: number;
  patient_name: string;
  blood_group: string;
  units_needed: number;
  hospital_name: string;
  upazila: string;
  needed_date: string;
  contact_phone: string | null;
  notes: string;
}

export interface BusinessListing {
  id: number;
  category: string;
  name: string;
  address: string;
  upazila: string;
  phone: string;
  hours: string;
  description: string;
  map_coords: string;
  is_featured: number;
}

export interface Advertisement {
  id: number;
  title: string;
  placement: string;
  banner_image: string;
  target_url: string;
  start_date: string;
  end_date: string;
  status: string;
}

export const GAZIPUR_UPAZILAS = [
  { value: 'Gazipur Sadar', bn: 'গাজীপুর সদর', en: 'Gazipur Sadar' },
  { value: 'Tongi', bn: 'টঙ্গী', en: 'Tongi' },
  { value: 'Kaliakoir', bn: 'কালিয়াকৈর', en: 'Kaliakoir' },
  { value: 'Kaliganj', bn: 'কালীগঞ্জ', en: 'Kaliganj' },
  { value: 'Kapasia', bn: 'কাপাসিয়া', en: 'Kapasia' },
  { value: 'Sreepur', bn: 'শ্রীপুর', en: 'Sreepur' },
];

export const ACCOUNT_CATEGORIES = [
  { slug: 'general_user', bn: 'সাধারণ নাগরিক', en: 'General User' },
  { slug: 'student', bn: 'শিক্ষার্থী', en: 'Student' },
  { slug: 'journalist', bn: 'সাংবাদিক', en: 'Journalist' },
  { slug: 'landlord', bn: 'বাড়িওয়ালা / প্রপার্টি মালিক', en: 'Landlord / Property Owner' },
  { slug: 'business_owner', bn: 'ব্যবসা প্রতিষ্ঠান মালিক', en: 'Business Owner' },
  { slug: 'educational_institution', bn: 'শিক্ষা প্রতিষ্ঠান', en: 'Educational Institution' },
  { slug: 'blood_donor', bn: 'রক্তদাতা', en: 'Blood Donor' },
  { slug: 'organization', bn: 'সামাজিক সংগঠন', en: 'Organization' },
  { slug: 'job_poster', bn: 'চাকরিদাতা / নিয়োগদাতা', en: 'Job Poster / Employer' },
];

export const TEXT = {
  bn: {
    brand: 'আমার গাজীপুর',
    navHome: 'হোম',
    navNews: 'সংবাদ',
    navCommunity: 'কমিউনিটি',
    navMarketRent: 'মার্কেট, ভাড়া ও চাকরি',
    navServices: 'সেবা ও ডিরেক্টরি',
    loginBtn: 'লগইন / যোগ দিন',
    dashboardBtn: 'ড্যাশবোর্ড',
    heroKicker: 'গাজীপুর জেলা · ডিজিটাল কমিউনিটি প্ল্যাটফর্ম',
    heroTitle: 'গাজীপুরের সংবাদ, নাগরিক সেবা ও কমিউনিটি এক ঠিকানায়',
    heroSub: 'চান্দনা চৌরাস্তা থেকে শ্রীপুর, টঙ্গী থেকে কাপাসিয়া — স্থানীয় সংবাদ, আলোচনা, মার্কেটপ্লেস, বাসা ভাড়া, শিক্ষা ও জরুরি রক্তদাতা খুঁজুন কোনো লগইন ছাড়াই।',
    heroCtaPrimary: 'সংবাদ ও কমিউনিটি দেখুন',
    heroCtaZip: 'কমিউনিটি ফোরাম',
  },
  en: {
    brand: 'AmarGazipur',
    navHome: 'Home',
    navNews: 'News',
    navCommunity: 'Community',
    navMarketRent: 'Market, Rent & Jobs',
    navServices: 'Directories',
    loginBtn: 'Sign In / Join',
    dashboardBtn: 'Dashboard',
    heroKicker: 'Gazipur District · Digital Community Platform',
    heroTitle: 'Local Journalism, Civic Services & Marketplace for Gazipur',
    heroSub: 'From Gazipur Sadar and Tongi to Kaliakoir, Kaliganj, Kapasia, and Sreepur — browse local news, community posts, To-Let rentals, education, and blood donors freely.',
    heroCtaPrimary: 'Explore News & Feed',
    heroCtaZip: 'Community Forum',
  },
} as const;
