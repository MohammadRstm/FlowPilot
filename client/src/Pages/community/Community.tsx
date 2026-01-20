import React, { useContext, useState } from "react";
import "../../styles/Community.css";
import Header from "../components/Header";
import { useFetchPosts } from "./hook/useFetchPosts";
import { useInfiniteScroll } from "./hook/useInfiniteScroll";
import PostCard from "./components/PostCard";
import { AuthContext } from "../../context/AuthContext";
import CreatePostModal from "./components/CreatePostModal";

const CommunityPage: React.FC = () => {
  const auth = useContext(AuthContext);
  const { user, loading, logout } = auth;

  const [showModal, setShowModal] = useState(false);

  const handleStartPost = () => setShowModal(true);

  if (loading) {
    return <div>Loading user...</div>;
  }

  const {
    posts,
    isLoading,
    isFetchingMore,
    hasMore,
    loadMore,
    error,
  } = useFetchPosts();

  const loadMoreRef = useInfiniteScroll({
    hasMore,
    isLoading: isFetchingMore,
    onLoadMore: loadMore,
  });

  return (
    <>
      <div className="community-page">
        <Header />

        {/* Feed area */}
        <main className="community-layout">
          {/* LEFT: feed */}
          <section className="feed">
            <div className="feed-header">
              <div className="page-meta">
                <h1 className="page-title">n8n Community</h1>
                <p className="page-subtitle">
                  Automation builders sharing tips, flows, and integrations for n8n.
                </p>
              </div>
            </div>

            {isLoading && <div className="loading">Loading...</div>}
            {error && <div className="error">Error loading posts</div>}

            {posts.map((post) => (
              <PostCard key={post.id} post={post} />
            ))}

            <div ref={loadMoreRef} />

            {isFetchingMore && <div className="loading-more">Loading more…</div>}
          </section>

          {/* RIGHT: sticky profile card */}
          <aside className="community-sidebar">
            <div className="profile-card">
              <div className="profile-header">
                {user?.profile_pic ? (
                  <img
                    src={user?.profile}
                    alt="User avatar"
                    className="community-profile-avatar"
                  />
                ) : (
                  <div className="community-profile-avatar">
                    {`${user?.first_name?.[0] ?? ""}${user?.last_name?.[0] ?? ""}`.toUpperCase()}
                  </div>
                )}
                <div className="profile-meta">
                  <div className="profile-name">{user?.first_name + " " + user?.last_name}</div>
                  <div className="profile-username">{user?.email}</div>
                </div>
              </div>

              <button
                className="start-post-btn full-width"
                onClick={handleStartPost}
              >
                Start a post
              </button>
            </div>
          </aside>
        </main>

      </div>
    
      <CreatePostModal
        isOpen={showModal}
        onClose={() => setShowModal(false)}
      />
      </>
  );
};

export default CommunityPage;
