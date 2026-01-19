import React from "react";
import "../../styles/Community.css";
import Header from "../components/Header";
import { useFetchPosts, type PostDto } from "./hook/useFetchPosts";
import { useInfiniteScroll } from "./hook/useInfiniteScroll";
import { useToggleLike } from "./hook/useToggleLike";


const PostCard: React.FC<{ post: PostDto }> = ({ post }) => {
  const likeMutation = useToggleLike();

  return (
    <div className="post-card">
      <div className="post-header">
        <img src={post.avatar ?? ""} alt={post.author} />
        <div>
          <div className="post-author">{post.author}</div>
          <div className="post-username">{post.username}</div>
        </div>
        <div className="post-imports">{post.exports} exports</div>
      </div>

      <div className="post-content">{post.content}</div>

      <div className="post-actions">
        <button
          className={`like-btn ${post.liked_by_me ? "liked" : ""}`}
          onClick={() => likeMutation.mutate(post.id)}
          disabled={likeMutation.isLoading}
        >
          👍 Like
        </button>

        <button>💬 Comment</button>
        <button>⬇ Export</button>
      </div>

      <div className="post-stats">
        {post.likes} likes · {post.comments} comments
      </div>
    </div>
  );
};


const CommunityPage: React.FC = () => {
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
    <div className="community-page">
      <Header />

      <main className="feed">
        {isLoading && <div>Loading...</div>}
        {error && <div>Error loading posts</div>}

        {posts.map((post) => (
          <PostCard key={post.id} post={post} />
        ))}

        <div ref={loadMoreRef} />

        {isFetchingMore && <div className="loading-more">Loading more…</div>}
      </main>
    </div>
  );
};

export default CommunityPage;
