import React, { useContext, useEffect, useMemo, useState } from "react";
import "../styles/Profile.css";
import Header from "./components/Header";
import { AuthContext } from "../context/AuthContext";
import { api } from "../api/client"; // adjust path if needed
import { FiSettings } from "react-icons/fi";
import { useNavigate } from "react-router-dom";
import { useParams } from "react-router-dom";


type ProfileApiShape = {
  user?: any;
  totals?: { likes?: number; imports?: number; posts_count?: number };
  followers?: Array<{ id: number; full_name: string; photo_url?: string }>;
  followings?: Array<{ id: number; full_name: string; photo_url?: string }>;
  posts?: { items: any[]; nextCursor?: string | null; hasMore?: boolean; meta?: any };
  workflows?: { items: any[]; nextCursor?: string | null; hasMore?: boolean; meta?: any };
  viewer_follows?: boolean;
  following_count?: number; // optional numeric fallback
};

type UserLite = {
  id: number;
  full_name: string;
  email?: string;
  photo_url?: string;
};

const ProfilePage: React.FC = () => {
  const auth = useContext(AuthContext);
  const authUser = auth?.user;
  const [profile, setProfile] = useState<ProfileApiShape | null>(null);
  const [loading, setLoading] = useState<boolean>(true);
  const [error, setError] = useState<string | null>(null);
  const [modalType, setModalType] = useState<"followers" | "following" | null>(null);
  const navigate = useNavigate();
const { userId } = useParams<{ userId?: string }>();


  const [tab, setTab] = useState<"posts" | "workflows">("posts");
  const [sortBy, setSortBy] = useState<"score" | "likes" | "comments" | "imports">("likes");
  const [imgError, setImgError] = useState(false);

  const isOwnProfile = !userId || Number(userId) === authUser?.id;



  // fallback base user info from auth context (while profile data loads)
  const baseUser = profile?.user ?? authUser ?? {};
  const fullName = `${baseUser.first_name ?? ""} ${baseUser.last_name ?? ""}`.trim();
  const initials =
    (
      (baseUser.first_name?.[0] ?? "") +
      (baseUser.last_name?.[0] ?? "")
    ).toUpperCase() || "U";

  useEffect(() => {
    let mounted = true;
    setLoading(true);
    setError(null);

      api.get("/profileDetails", {
        params: userId ? { user_id: userId } : undefined,
      })
      .then((res) => {
        const payload = res.data?.data ?? null;
        console.log(payload)
        if (!mounted) return;
        if (!payload) {
          console.warn("profileDetails: unexpected response shape", res.data);
          setError("Unexpected server response.");
          return;
        }
        setProfile(payload);
      })
      .catch((err) => {
        console.error("Failed to fetch profileDetails:", err);
        if (!mounted) return;
        setError("Failed to load profile data.");
      })
      .finally(() => {
        if (!mounted) return;
        setLoading(false);
      });

    return () => {
      mounted = false;
    };
  }, []);

  const posts: any[] = profile?.posts?.items ?? [];
  const workflows: any[] = profile?.workflows?.items ?? [];

  const computedTotals = useMemo(() => {
    return posts.reduce(
      (acc: { likes: number; imports: number }, p: any) => {
        const likes = typeof p.likes === "number" ? p.likes : p.likes_count ?? p.likes?.length ?? 0;
        const imports = typeof p.imports === "number" ? p.imports : p.imports_count ?? p.imports?.length ?? 0;
        acc.likes += likes;
        acc.imports += imports;
        return acc;
      },
      { likes: 0, imports: 0 }
    );
  }, [posts]);

  const totals = profile?.totals ?? computedTotals;
  const followers = profile?.followers ?? [];

  // helper getCounts & getScore (same as before)
  const getCounts = (p: any) => {
    const likes = typeof p.likes === "number" ? p.likes : p.likes_count ?? p.likes?.length ?? 0;
    const imports = typeof p.imports === "number" ? p.imports : p.imports_count ?? p.imports?.length ?? 0;
    const comments = typeof p.comments_count === "number" ? p.comments_count : p.comments?.length ?? 0;
    return { likes, imports, comments };
  };

  const getScore = (p: any) => {
    const w = { likes: 1.0, comments: 1.5, imports: 1.2 };
    const { likes, imports, comments } = getCounts(p);
    return likes * w.likes + comments * w.comments + imports * w.imports;
  };

  const downloadHistory = async (url?: string) => {
    if (!url) return;

    try {
      const res = await api.get(url, {
        responseType: "blob",
      });

      const blob = new Blob([res.data], { type: "application/json" });
      const downloadUrl = URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = downloadUrl;
      a.download = "history.json";
      document.body.appendChild(a);
      a.click();
      a.remove();
      URL.revokeObjectURL(downloadUrl);
    } catch (err) {
      console.error("Download failed", err);
      alert("Download failed. Try again.");
    }
  };


  const sortedPosts = useMemo(() => {
    const list = [...posts];
    if (sortBy === "likes") {
      list.sort((a: any, b: any) => getCounts(b).likes - getCounts(a).likes);
    } else if (sortBy === "comments") {
      list.sort((a: any, b: any) => getCounts(b).comments - getCounts(a).comments);
    } else if (sortBy === "imports") {
      list.sort((a: any, b: any) => getCounts(b).imports - getCounts(a).imports);
    } else {
      list.sort((a: any, b: any) => getScore(b) - getScore(a));
    }
    return list;
  }, [posts, sortBy]);

  // Loading / error UI (unchanged)
  if (loading) {
    return (
      <div className="profile-page">
        <Header />
        <main className="profile-main">
          <div className="profile-card wide-grid">
            <div className="profile-column profile-left" style={{ gridArea: "profile" }}>
              <div className="profile-avatar-loading" />
              <div className="profile-basic">
                <div className="skeleton title" />
                <div className="skeleton subtitle" />
              </div>
            </div>

            <div className="profile-column stats-column" style={{ gridArea: "stats" }}>
              <div className="stats-card">
                <div className="stats-inner">
                  <div className="stat-circle skeleton-circle" />
                  <div className="stat-circle skeleton-circle" />
                  <div className="stat-circle skeleton-circle" />
                </div>
              </div>
            </div>

            <div className="profile-column content-column" style={{ gridArea: "content" }}>
              <div className="empty">Loading profile…</div>
            </div>
          </div>
        </main>
      </div>
    );
  }

  if (error) {
    return (
      <div className="profile-page">
        <Header />
        <main className="profile-main">
          <section className="profile-card wide-grid">
            <div className="profile-column profile-left" style={{ gridArea: "profile" }}>
              <div className="profile-avatar">
                {baseUser.photo_url ? <img src={baseUser.photo_url} alt={fullName || "User avatar"} /> : <span className="profile-initials">{initials}</span>}
              </div>
              <div className="profile-basic">
                <h2 className="profile-name">{fullName || "Your Profile"}</h2>
                <p className="profile-email">{baseUser.email}</p>
              </div>
            </div>

            <div className="profile-column stats-column" style={{ gridArea: "stats" }}>
              <div className="stats-card">
                <div className="stats-inner">
                  <div className="stat-circle"><div className="stat-number">—</div><div className="stat-label">Total Likes</div></div>
                  <div className="stat-circle"><div className="stat-number">—</div><div className="stat-label">Total Imports</div></div>
                  <div className="stat-circle"><div className="stat-number">—</div><div className="stat-label">Posts</div></div>
                </div>
              </div>
            </div>

            <div className="profile-column content-column" style={{ gridArea: "content" }}>
              <div className="empty">{error}</div>
            </div>
          </section>
        </main>
      </div>
    );
  }

  // Main UI
  return (
    <div className="profile-page">
      <Header />
      <main className="profile-main">
        <section className="profile-card wide-grid">
            {isOwnProfile && (
              <button
                className="profile-settings-btn"
                onClick={() => navigate("/settings")}
              >
                <FiSettings size={20} />
              </button>
            )}

          {/* profile area */}
          <div className="profile-column profile-left" style={{ gridArea: "profile" }}>
            <div className="profile-head">
              <div className="profile-avatar">
                {baseUser.photo_url && !imgError ? (
                  <img
                    src={baseUser.photo_url}
                    alt={fullName || "User avatar"}
                    onError={() => setImgError(true)}
                  />
                ) : (
                  <span className="profile-initials">{initials}</span>
                )}
              </div>

              <div className="profile-head-info">
                <div className="profile-basic left">
                  <h2 className="profile-name">{fullName || "Your Profile"}</h2>
                  <p className="profile-email">{baseUser.email}</p>
                </div>
              </div>
            </div>

<div className="profile-follow-row">
  <button className="count-link" onClick={() => setModalType("followers")}>
    <strong>{followers.length}</strong>
    <span>Followers</span>
  </button>

  <button className="count-link" onClick={() => setModalType("following")}>
    <strong>{profile?.following?.length ?? 0}</strong>
    <span>Following</span>
  </button>
</div>

          </div>
          {/* stats area */}
          <div className="profile-column stats-column" style={{ gridArea: "stats" }}>
            <div className="stats-card">
              <div className="stats-inner">
                <div className="stat-circle">
                  <div className="stat-number">{profile?.totals?.likes ?? totals.likes ?? 0}</div>
                  <div className="stat-label">Total Likes</div>
                </div>

                <div className="stat-circle">
                  <div className="stat-number">{profile?.totals?.imports ?? totals.imports ?? 0}</div>
                  <div className="stat-label">Total Imports</div>
                </div>

                <div className="stat-circle">
                  <div className="stat-number">{profile?.totals?.posts_count ?? posts.length ?? 0}</div>
                  <div className="stat-label">Posts</div>
                </div>
              </div>

              <div className="stats-actions">
                <div className="segmented-buttons">
                  <button className={`seg-btn ${tab === "posts" ? "active" : ""}`} onClick={() => setTab("posts")}>Posts</button>
                  <button className={`seg-btn ${tab === "workflows" ? "active" : ""}`} onClick={() => setTab("workflows")}>Workflows</button>
                </div>

                {tab === "posts" && (
                  <div className="sort-controls">
                    <label>Sort by</label>
                    <select value={sortBy} onChange={(e) => setSortBy(e.target.value as any)}>
                      <option value="likes">Likes</option>
                      <option value="comments">Comments</option>
                      <option value="imports">Imports</option>
                      <option value="score">Composite score</option>
                    </select>
                  </div>
                )}
              </div>
            </div>
          </div>

          {/* content area */}
          <div className="profile-column content-column" style={{ gridArea: "content" }}>
            {tab === "posts" ? (
              <section className="posts-list">
                {sortedPosts.length === 0 ? (
                  <div className="empty">No posts yet.</div>
                ) : (
                  sortedPosts.map((p: any) => {
                    const { likes, imports, comments } = getCounts(p);
                    return (
                      <article className="post-card" key={p.id ?? p.post_id ?? Math.random()}>
                        <div className="post-header">
                          <h3 className="post-title">{p.title ?? p.description ?? p.text ?? "Untitled post"}</h3>
                          <div className="post-meta">Score: {Math.round(getScore(p) * 10) / 10}</div>
                        </div>

                        <div className="post-body">{p.description ?? p.excerpt ?? p.body ?? ""}</div>

                        <div className="post-stats">
                          <span>❤️ {likes}</span>
                          <span>💬 {comments}</span>
                          <span>📥 {imports}</span>
                        </div>
                      </article>
                    );
                  })
                )}
                {/* Pagination: backend provides profile.posts.nextCursor & profile.posts.hasMore */}
              </section>
            ) : (
              <section className="workflows-list">
                {workflows.length === 0 ? (
                  <div className="empty">No workflows / history available.</div>
                ) : (
                  <ul>
                    {workflows.map((w: any) => (
<li
  key={w.id ?? w.file_url ?? Math.random()}
  className="workflow-item"
>
  <div className="wf-left">
    <span className="wf-id">{w.id ?? w.filename ?? "history"}</span>
    <span className="wf-separator">-</span>
    <span className="wf-date">
      {new Date(w.created_at ?? w.date ?? "").toLocaleString()}
    </span>
  </div>

  <button
    className="btn-download"
    onClick={() => downloadHistory(w.download_url ?? w.file_url)}
    title="Download JSON"
  >
    📄
  </button>
</li>


                    ))}
                  </ul>
                )}
              </section>
            )}
          </div>
        </section>
      </main>

      {modalType && (
      <FollowModal
        title={modalType === "followers" ? "Followers" : "Following"}
        users={
          modalType === "followers"
            ? profile?.followers ?? []
            : profile?.following ?? []
        }
        onClose={() => setModalType(null)}
        onUserClick={(user) => {
          navigate(`/profile/${user.id}`);
          setModalType(null);
        }}
      />
    )}

    </div>
  );
};


type FollowModalProps = {
  title: string;
  users: UserLite[];
  onClose: () => void;
  onUserClick: (user: UserLite) => void;
};

const FollowModal: React.FC<FollowModalProps> = ({
  title,
  users,
  onClose,
  onUserClick,
}) => {
  const [query, setQuery] = useState("");

  const normalizedQuery = query.trim().toLowerCase();

  const scoredUsers = useMemo(() => {
    if (!normalizedQuery) return users;

    return [...users].sort((a, b) => {
      const score = (name: string) => {
        const n = name.toLowerCase();

        if (n.startsWith(normalizedQuery)) return 4;
        if (n.split(" ").some(w => w.startsWith(normalizedQuery))) return 3;
        if (n.includes(normalizedQuery)) return 2;
        return 1;
      };

      return score(b.full_name) - score(a.full_name);
    });
  }, [users, normalizedQuery]);

  const isFollowingModal = title.toLowerCase().includes("following");

  const handleFollowBack = (e: React.MouseEvent, user: any) => {
    e.stopPropagation();
    console.log("Follow back clicked:", user);
    // TODO: hook API later
  };

  return (
    <div className="modal-backdrop" onClick={onClose}>
      <div className="modal-card" onClick={(e) => e.stopPropagation()}>
        <div className="modal-header">
          <h3>{title}</h3>
          <button className="modal-close" onClick={onClose}>✕</button>
        </div>

        <div className="modal-body">
          <div className="modal-search">
            <input
              type="text"
              placeholder="Search by name…"
              value={query}
              onChange={(e) => setQuery(e.target.value)}
            />
          </div>

          {scoredUsers.length === 0 ? (
            <div className="empty">No users found.</div>
          ) : (
            scoredUsers.map((u) => (
              <div
                key={u.id}
                className="follow-row"
                onClick={() => onUserClick(u)}
              >
                <div className="follow-left">
                  <div className="follow-avatar">
                    {u.photo_url ? (
                      <img src={u.photo_url} alt={u.full_name} />
                    ) : (
                      <span>{u.full_name[0]}</span>
                    )}
                  </div>

                  <div className="follow-info">
                    <div className="follow-name">{u.full_name}</div>
                    <div className="follow-email">{u.email ?? "—"}</div>
                  </div>
                </div>

                {isFollowingModal && (
                  <button
                    className="follow-back-btn"
                    onClick={(e) => handleFollowBack(e, u)}
                  >
                    + Follow back
                  </button>
                )}
              </div>
            ))
          )}
        </div>
      </div>
    </div>
  );
};



export default ProfilePage;
