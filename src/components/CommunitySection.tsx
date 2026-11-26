import React, { useEffect, useState } from 'react';
import { Heart, MessageSquare, Flag, Share2, Trash2, ImagePlus, Search } from 'lucide-react';
import { CommunityPost, GAZIPUR_UPAZILAS, Lang, User } from '../types';

interface Props {
  lang: Lang;
  user: User | null;
  token: string | null;
  onRequireAuth: () => void;
}

const POST_CATEGORIES = [
  'সাধারণ আলোচনা',
  'নাগরিক সমস্যা',
  'ট্রাফিক ও যাতায়াত',
  'চাকরি ও সুযোগ',
  'হেল্প ডেস্ক',
];

export const CommunitySection: React.FC<Props> = ({ lang, user, token, onRequireAuth }) => {
  const [posts, setPosts] = useState<CommunityPost[]>([]);
  const [loading, setLoading] = useState(true);
  const [q, setQ] = useState('');
  const [category, setCategory] = useState('');
  const [title, setTitle] = useState('');
  const [content, setContent] = useState('');
  const [postCat, setPostCat] = useState('সাধারণ আলোচনা');
  const [upazila, setUpazila] = useState('Gazipur Sadar');
  const [imagePath, setImagePath] = useState<string | null>(null);
  const [uploading, setUploading] = useState(false);
  const [feedback, setFeedback] = useState('');
  const [commentInputs, setCommentInputs] = useState<Record<number, string>>({});
  const [replyInputs, setReplyInputs] = useState<Record<number, string>>({});

  const loadPosts = async () => {
    setLoading(true);
    try {
      const params = new URLSearchParams();
      if (q) params.set('q', q);
      if (category) params.set('category', category);
      const res = await fetch(`/api/posts?${params.toString()}`, {
        headers: token ? { Authorization: `Bearer ${token}` } : {},
      });
      const data = await res.json();
      setPosts(data.posts || []);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadPosts();
  }, [category, token]);

  const handleImageUpload = async (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;
    if (!token) {
      onRequireAuth();
      return;
    }
    setUploading(true);
    try {
      const fd = new FormData();
      fd.append('image', file);
      const res = await fetch('/api/upload', {
        method: 'POST',
        headers: { Authorization: `Bearer ${token}` },
        body: fd,
      });
      const data = await res.json();
      if (res.ok && data.url) setImagePath(data.url);
    } finally {
      setUploading(false);
    }
  };

  const handleCreatePost = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!token) {
      onRequireAuth();
      return;
    }
    setFeedback('');
    const res = await fetch('/api/posts', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Authorization: `Bearer ${token}`,
      },
      body: JSON.stringify({ title, content, category: postCat, upazila, image_path: imagePath }),
    });
    const data = await res.json();
    if (res.ok) {
      setTitle('');
      setContent('');
      setImagePath(null);
      setFeedback(data.message || '');
      loadPosts();
    } else {
      setFeedback(data.error || 'Error');
    }
  };

  const handleLike = async (postId: number) => {
    if (!token) {
      onRequireAuth();
      return;
    }
    const res = await fetch(`/api/posts/${postId}/like`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${token}` },
    });
    if (res.ok) {
      const data = await res.json();
      setPosts((prev) =>
        prev.map((p) => (p.id === postId ? { ...p, liked_by_me: data.liked, likes_count: data.count } : p))
      );
    }
  };

  const handleComment = async (e: React.FormEvent, postId: number) => {
    e.preventDefault();
    if (!token) {
      onRequireAuth();
      return;
    }
    const body = (commentInputs[postId] || '').trim();
    if (!body) return;
    const res = await fetch(`/api/posts/${postId}/comments`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Authorization: `Bearer ${token}`,
      },
      body: JSON.stringify({ body }),
    });
    const data = await res.json();
    if (res.ok) {
      setCommentInputs((prev) => ({ ...prev, [postId]: '' }));
      if (data.status === 'pending_moderation') {
        setFeedback(data.message);
      }
      loadPosts();
    }
  };

  const handleReply = async (e: React.FormEvent, commentId: number) => {
    e.preventDefault();
    if (!token) {
      onRequireAuth();
      return;
    }
    const body = (replyInputs[commentId] || '').trim();
    if (!body) return;
    const res = await fetch(`/api/comments/${commentId}/replies`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Authorization: `Bearer ${token}`,
      },
      body: JSON.stringify({ body }),
    });
    if (res.ok) {
      setReplyInputs((prev) => ({ ...prev, [commentId]: '' }));
      loadPosts();
    }
  };

  const handleReport = async (postId: number) => {
    if (!token) {
      onRequireAuth();
      return;
    }
    const res = await fetch('/api/reports', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Authorization: `Bearer ${token}`,
      },
      body: JSON.stringify({ target_type: 'post', target_id: postId, reason: 'অনুপযুক্ত কনটেন্ট' }),
    });
    const data = await res.json();
    if (res.ok) setFeedback(data.message);
  };

  const handleDelete = async (postId: number) => {
    if (!token) return;
    const res = await fetch(`/api/posts/${postId}`, {
      method: 'DELETE',
      headers: { Authorization: `Bearer ${token}` },
    });
    if (res.ok) loadPosts();
  };

  return (
    <div className="max-w-5xl mx-auto px-4 sm:px-6 py-8">
      <div className="mb-8">
        <h1 className="text-2xl sm:text-3xl font-bold text-slate-900 mb-2">
          {lang === 'bn' ? 'গাজীপুর কমিউনিটি ফোরাম' : 'Gazipur Community Forum'}
        </h1>
        <p className="text-sm text-slate-600">
          {lang === 'bn'
            ? 'গাজীপুর জেলার নাগরিক সমস্যা, ট্রাফিক আপডেট, চাকরি ও সহায়তা নিয়ে আলোচনা করুন।'
            : 'Discuss civic topics, traffic updates, jobs, and neighborhood help across Gazipur.'}
        </p>
      </div>

      {/* Search & Category Filter Bar */}
      <div className="rounded-xl border border-slate-200 bg-white p-4 mb-6 space-y-4">
        <div className="flex flex-col sm:flex-row gap-3">
          <div className="relative flex-1">
            <Search className="h-4 w-4 text-slate-400 absolute left-3 top-3" />
            <input
              type="text"
              value={q}
              onChange={(e) => setQ(e.target.value)}
              onKeyDown={(e) => e.key === 'Enter' && loadPosts()}
              placeholder={lang === 'bn' ? 'পোস্ট খুঁজুন...' : 'Search community posts...'}
              className="w-full rounded-lg border border-slate-300 pl-9 pr-3 py-2 text-sm focus:border-emerald-700 focus:outline-none"
            />
          </div>
          <button
            type="button"
            onClick={loadPosts}
            className="rounded-lg bg-slate-900 px-5 py-2 text-sm font-medium text-white hover:bg-slate-800 whitespace-nowrap"
          >
            {lang === 'bn' ? 'খুঁজুন' : 'Search'}
          </button>
        </div>

        <div className="flex flex-wrap gap-1 p-1 bg-slate-100 rounded-lg">
          <button
            type="button"
            onClick={() => setCategory('')}
            className={`px-3 py-1.5 text-xs font-medium rounded-md transition-colors whitespace-nowrap ${
              category === '' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600 hover:text-slate-900'
            }`}
          >
            {lang === 'bn' ? 'সকল' : 'All'}
          </button>
          {POST_CATEGORIES.map((c) => (
            <button
              key={c}
              type="button"
              onClick={() => setCategory(c)}
              className={`px-3 py-1.5 text-xs font-medium rounded-md transition-colors whitespace-nowrap ${
                category === c ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600 hover:text-slate-900'
              }`}
            >
              {c}
            </button>
          ))}
        </div>
      </div>

      {/* Create Post Box */}
      <div className="rounded-xl border border-slate-200 bg-white p-5 mb-8">
        {feedback && (
          <div className="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
            {feedback}
          </div>
        )}
        {user ? (
          <form onSubmit={handleCreatePost} className="space-y-3">
            <h2 className="text-base font-semibold text-slate-900">
              {lang === 'bn' ? 'নতুন কমিউনিটি পোস্ট লিখুন' : 'Create Community Post'}
            </h2>
            <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
              <input
                type="text"
                required
                value={title}
                onChange={(e) => setTitle(e.target.value)}
                placeholder={lang === 'bn' ? 'পোস্টের শিরোনাম...' : 'Post title...'}
                className="sm:col-span-1 rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-700 focus:outline-none"
              />
              <select
                value={postCat}
                onChange={(e) => setPostCat(e.target.value)}
                className="rounded-lg border border-slate-300 px-3 py-2 text-sm"
              >
                {POST_CATEGORIES.map((c) => (
                  <option key={c} value={c}>{c}</option>
                ))}
              </select>
              <select
                value={upazila}
                onChange={(e) => setUpazila(e.target.value)}
                className="rounded-lg border border-slate-300 px-3 py-2 text-sm"
              >
                {GAZIPUR_UPAZILAS.map((u) => (
                  <option key={u.value} value={u.value}>{lang === 'bn' ? u.bn : u.en}</option>
                ))}
              </select>
            </div>
            <textarea
              rows={3}
              required
              value={content}
              onChange={(e) => setContent(e.target.value)}
              placeholder={lang === 'bn' ? 'বিস্তারিত লিখুন... (নিষিদ্ধ শব্দ থাকলে স্বয়ংক্রিয়ভাবে মডারেশনে যাবে)' : 'Write your post...'}
              className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-700 focus:outline-none"
            />
            {imagePath && (
              <div className="flex items-center gap-3 text-xs text-slate-600">
                <img src={imagePath} alt="Upload preview" className="h-16 w-24 object-cover rounded border border-slate-200" />
                <button type="button" onClick={() => setImagePath(null)} className="text-red-600 hover:underline">
                  ছবি সরান
                </button>
              </div>
            )}
            <div className="flex flex-wrap items-center justify-between gap-3">
              <label className="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-xs font-medium text-slate-700 cursor-pointer hover:bg-slate-50">
                <ImagePlus className="h-4 w-4" />
                <span>{uploading ? '...' : lang === 'bn' ? 'ছবি যুক্ত করুন' : 'Attach Image'}</span>
                <input type="file" accept="image/jpeg,image/png,image/webp" onChange={handleImageUpload} className="hidden" />
              </label>
              <button
                type="submit"
                className="rounded-lg bg-emerald-800 px-5 py-2 text-sm font-semibold text-white hover:bg-emerald-900"
              >
                {lang === 'bn' ? 'পোস্ট প্রকাশ করুন' : 'Publish Post'}
              </button>
            </div>
          </form>
        ) : (
          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <p className="text-sm text-slate-600">
              {lang === 'bn'
                ? 'কমিউনিটিতে পোস্ট করতে, লাইক দিতে বা মন্তব্য করতে সদস্য অ্যাকাউন্টে লগইন করুন।'
                : 'Sign in or register to create posts, like discussions, and reply to residents.'}
            </p>
            <button
              type="button"
              onClick={onRequireAuth}
              className="rounded-lg bg-emerald-800 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-900 whitespace-nowrap"
            >
              {lang === 'bn' ? 'নতুন পোস্ট লিখুন' : 'Create Post'}
            </button>
          </div>
        )}
      </div>

      {/* Posts List */}
      {loading ? (
        <div className="space-y-4">
          {[1, 2].map((i) => (
            <div key={i} className="h-40 rounded-xl border border-slate-200 bg-white animate-pulse" />
          ))}
        </div>
      ) : posts.length === 0 ? (
        <div className="rounded-xl border border-slate-200 bg-white p-10 text-center text-sm text-slate-500">
          {lang === 'bn' ? 'কোনো পোস্ট পাওয়া যায়নি।' : 'No community posts found.'}
        </div>
      ) : (
        <div className="space-y-5">
          {posts.map((post) => (
            <article key={post.id} className="rounded-xl border border-slate-200 bg-white p-6">
              <div className="flex items-start justify-between gap-4 mb-3">
                <div>
                  <div className="flex flex-wrap items-center gap-2 text-xs text-slate-500 mb-1">
                    <span>{post.category}</span>
                    <span aria-hidden="true">·</span>
                    <span>{post.upazila}</span>
                    <span aria-hidden="true">·</span>
                    <span className="font-medium text-slate-700">{post.author_name}</span>
                  </div>
                  <h2 className="text-lg font-bold text-slate-900">{post.title}</h2>
                </div>
                {user && (user.id === post.user_id || user.role === 'admin') && (
                  <button
                    type="button"
                    onClick={() => handleDelete(post.id)}
                    className="rounded-lg p-2 text-slate-400 hover:bg-red-50 hover:text-red-600"
                    title="Delete"
                  >
                    <Trash2 className="h-4 w-4" />
                  </button>
                )}
              </div>

              <p className="text-sm text-slate-700 leading-relaxed whitespace-pre-line mb-4">{post.content}</p>

              {post.image_path && (
                <div className="mb-4 overflow-hidden rounded-lg border border-slate-200 bg-slate-100">
                  <img
                    src={post.image_path}
                    alt={post.title}
                    referrerPolicy="no-referrer"
                    className="w-full max-h-80 object-cover"
                    loading="lazy"
                  />
                </div>
              )}

              <div className="flex flex-wrap items-center gap-4 border-t border-slate-100 pt-3">
                <button
                  type="button"
                  onClick={() => handleLike(post.id)}
                  className={`inline-flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-lg border transition-colors ${
                    post.liked_by_me
                      ? 'border-emerald-700 bg-emerald-50 text-emerald-900'
                      : 'border-slate-200 text-slate-600 hover:bg-slate-50'
                  }`}
                >
                  <Heart className="h-3.5 w-3.5" />
                  <span>{lang === 'bn' ? 'পছন্দ' : 'Like'} ({post.likes_count})</span>
                </button>
                <button
                  type="button"
                  onClick={() => !token && onRequireAuth()}
                  className="inline-flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50"
                >
                  <MessageSquare className="h-3.5 w-3.5" />
                  <span>{lang === 'bn' ? 'মন্তব্য' : 'Comments'} ({post.comments?.length || 0})</span>
                </button>
                <button
                  type="button"
                  onClick={() => navigator.clipboard?.writeText(window.location.href)}
                  className="inline-flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50"
                >
                  <Share2 className="h-3.5 w-3.5" />
                  <span>{lang === 'bn' ? 'শেয়ার' : 'Share'}</span>
                </button>
                <button
                  type="button"
                  onClick={() => handleReport(post.id)}
                  className="inline-flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-lg border border-slate-200 text-slate-600 hover:bg-amber-50 hover:text-amber-800 ml-auto"
                >
                  <Flag className="h-3.5 w-3.5" />
                  <span>{lang === 'bn' ? 'রিপোর্ট' : 'Report'}</span>
                </button>
              </div>

              {/* Comments & Replies */}
              <div className="mt-4 space-y-2.5 border-t border-slate-100 pt-3">
                {(post.comments || []).map((c) => (
                  <div key={c.id} className="rounded-lg bg-slate-50 p-3">
                    <div className="text-xs font-semibold text-slate-800">{c.author_name}</div>
                    <div className="text-sm text-slate-700 mt-0.5">{c.body}</div>
                    {(c.replies || []).map((rep) => (
                      <div key={rep.id} className="mt-2 ml-4 pl-3 border-l border-slate-300 text-xs text-slate-600">
                        <span className="font-semibold text-slate-800">{rep.author_name}:</span> {rep.body}
                      </div>
                    ))}
                    {user && (
                      <form onSubmit={(e) => handleReply(e, c.id)} className="mt-2 flex gap-2">
                        <input
                          type="text"
                          value={replyInputs[c.id] || ''}
                          onChange={(e) => setReplyInputs((prev) => ({ ...prev, [c.id]: e.target.value }))}
                          placeholder={lang === 'bn' ? 'উত্তর লিখুন...' : 'Reply...'}
                          className="flex-1 rounded border border-slate-300 bg-white px-2.5 py-1 text-xs"
                        />
                        <button type="submit" className="rounded bg-slate-800 px-3 py-1 text-xs text-white">
                          {lang === 'bn' ? 'উত্তর' : 'Reply'}
                        </button>
                      </form>
                    )}
                  </div>
                ))}

                {user ? (
                  <form onSubmit={(e) => handleComment(e, post.id)} className="flex gap-2 pt-1">
                    <input
                      type="text"
                      value={commentInputs[post.id] || ''}
                      onChange={(e) => setCommentInputs((prev) => ({ ...prev, [post.id]: e.target.value }))}
                      placeholder={lang === 'bn' ? 'মন্তব্য লিখুন...' : 'Write a comment...'}
                      className="flex-1 rounded-lg border border-slate-300 px-3 py-1.5 text-sm focus:border-emerald-700 focus:outline-none"
                    />
                    <button
                      type="submit"
                      className="rounded-lg bg-emerald-800 px-4 py-1.5 text-xs font-semibold text-white hover:bg-emerald-900"
                    >
                      {lang === 'bn' ? 'পাঠান' : 'Post'}
                    </button>
                  </form>
                ) : (
                  <button
                    type="button"
                    onClick={onRequireAuth}
                    className="text-xs font-medium text-emerald-800 hover:underline"
                  >
                    {lang === 'bn' ? 'মন্তব্য করতে লগইন করুন' : 'Sign in to comment'}
                  </button>
                )}
              </div>
            </article>
          ))}
        </div>
      )}
    </div>
  );
};
