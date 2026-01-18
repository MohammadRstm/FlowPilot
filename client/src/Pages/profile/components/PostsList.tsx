// src/pages/profile/components/PostsList.tsx
import React, { useMemo } from "react";
import { getCounts, getScore } from "../utils/postScoring";

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
      {sortedPosts.map((p: any) => {
        const { likes, imports, comments } = getCounts(p);
        return (
          <article className="post-card" key={p.id ?? p.post_id ?? `${Math.random()}`}>
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
      })}
    </section>
  );
};

export default PostsList;
