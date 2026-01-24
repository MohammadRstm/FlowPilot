import React, { useState } from "react";
import "./community.css";
import Header from "../components/Header";
import { useFetchPosts } from "./hook/useFetchPosts";
import { useInfiniteScroll } from "./hook/useInfiniteScroll";
import PostCard from "./components/PostCard";
import CreatePostModal from "./components/CreatePostModal";
import { adaptCommunityPost } from "./adapters/CommunityPostAdapter";
import { useAuth } from "../../context/useAuth";
import { Spinner } from "../components/Spinner";

const CommunityPage: React.FC = () => {
  const { user, loading } = useAuth();

  const [showModal, setShowModal] = useState(false);

  const handleStartPost = () => setShowModal(true);

  if (loading) {
    return <Spinner />;
  }

  const {
    posts,
    isPending,
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

        <main className="community-layout">
          <section className="feed">
            <div className="feed-header">
              <div className="page-meta">
                <h1 className="page-title">n8n Community</h1>
                <p className="page-subtitle">
                  Automation builders sharing tips, flows, and integrations for n8n.
                </p>
              </div>
            </div>

            {error && <div className="error">Error loading posts</div>}
            {isPending &&
            Array.from({ length: 5 }).map((_, i) => (
              <PostCard key={i} post={null} isPending />
            ))}

            {posts.map((post) => (
              <PostCard key={post.id} post={adaptCommunityPost(post)} isPending={isPending} />
            ))}

            <div ref={loadMoreRef} />

            {isFetchingMore && <div className="loading-more">Loading more…</div>}
          </section>

          <aside className="community-sidebar">
            <div className="sticky-profile-card">
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
