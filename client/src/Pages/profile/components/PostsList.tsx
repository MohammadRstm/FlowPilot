import React, { useMemo } from "react";
import { getCounts, getScore } from "../utils/postScoring";
import PostCard from "../../community/components/PostCard";
import { adaptListPost } from "../../../api/adapters/ProfilePostAdapter";

type Props = {
  posts: any[];
  sortBy: "score" | "likes" | "comments" | "imports";
};

const PostsList: React.FC<Props> = ({ posts, sortBy }) => {
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

  if (sortedPosts.length === 0) {
    return <div className="empty">No posts yet.</div>;
  }

   return (
    <section className="posts-list">
      {sortedPosts.map((p) => (
        <PostCard
          key={p.id}
          post={adaptListPost(p)}
          showHeader={false}
          showActions={true}
        />
      ))}
    </section>
  );
};

export default PostsList;
