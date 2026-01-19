import React from "react";
import "../../styles/Community.css";
import Header from "../components/Header";
import { useFetchPosts } from "./hook/useFetchPosts";
import { useInfiniteScroll } from "./hook/useInfiniteScroll";
import PostCard from "./components/PostCard";


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
