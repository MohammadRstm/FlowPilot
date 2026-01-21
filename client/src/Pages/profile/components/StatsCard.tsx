import React, { useState } from "react";
import FriendsModal from "./FriendModal";

type Totals = {
  likes: number;
  imports: number;
  posts_count?: number;
};


type Props = {
  totals: Totals;
  postsCount: number;
  tab: "posts" | "workflows";
  setTab: (t: "posts" | "workflows") => void;
  sortBy: "score" | "likes" | "comments" | "imports";
  setSortBy: (s: "score" | "likes" | "comments" | "imports") => void;
  isOwnProfile : boolean;
  isBeingFollowed: any;
};

const StatsCard: React.FC<Props> = ({ totals, postsCount, tab, setTab, sortBy, setSortBy , isOwnProfile , isBeingFollowed}) => {
    const [friendsOpen, setFriendsOpen] = useState(false);

  return (
   <>
      <div className="profile-column stats-column" style={{ gridArea: "stats" }}>
        <div className="stats-card">
          {/* HEADER */}
          <div className="stats-header">
            <div className="stats-inner">
              <div className="stat-circle">
                <div className="stat-number">{totals?.likes ?? 0}</div>
                <div className="stat-label">Total Likes</div>
              </div>

              <div className="stat-circle">
                <div className="stat-number">{totals?.imports ?? 0}</div>
                <div className="stat-label">Total Imports</div>
              </div>

              <div className="stat-circle">
                <div className="stat-number">{totals?.posts_count ?? postsCount}</div>
                <div className="stat-label">Posts</div>
              </div>
            </div>

            <button
              className="friends-btn"
              title="Friends"
              onClick={() => setFriendsOpen(true)}
            >
              👥
            </button>
          </div>

          <div className="stats-actions">
            <div className="segmented-buttons">
              <button className={`seg-btn ${tab === "posts" ? "active" : ""}`} onClick={() => setTab("posts")}>
                Posts
              </button>
              {isOwnProfile && (
                <button className={`seg-btn ${tab === "workflows" ? "active" : ""}`} onClick={() => setTab("workflows")}>
                    Workflows
                </button>
              )}
             {!isOwnProfile && isBeingFollowed?.isFollowing && (
                <button className={`seg-btn ${tab === "workflows" ? "active" : ""}`} onClick={() => setTab("workflows")}>
                    Workflows
                </button>
              )}
              
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

      <FriendsModal isOpen={friendsOpen} onClose={() => setFriendsOpen(false)} />
    </>
  );
};

export default StatsCard;
