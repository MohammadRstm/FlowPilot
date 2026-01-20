import { useState } from "react";
import { useExportPost } from "../hook/useExportPost";
import type { PostDto } from "../hook/useFetchPosts";
import { useToggleLike } from "../hook/useToggleLike";
import CommentsModal from "./CommentsModal";

const BASE_URL = import.meta.env.VITE_PHOTO_BASE_URL;

const PostCard: React.FC<{ post: PostDto }> = ({ post }) => {
  const [open, setOpen] = useState(false);

  const likeMutation = useToggleLike();
  const exportContent = useExportPost();

  const imageUrl =
    post.photo ? `${BASE_URL}/${post.photo}` : null;

  return (
    <>
      <div className="post-card">
        {/* HEADER */}
        <div className="post-header">
          <img
            src={post.avatar || "/avatar-placeholder.png"}
            alt={post.author}
          />

          <div className="post-author-meta">
            <div className="post-author">{post.author}</div>
            <div className="post-username">{post.username}</div>
          </div>

          <div className="post-imports">{post.exports} exports</div>
        </div>

        {post.title && (
          <h3 className="post-title">{post.title}</h3>
        )}

        {post.content && post.content.trim() !== "" && (
          <div className="post-content">{post.content}</div>
        )}

        {imageUrl && (
          <div className="post-image-wrapper">
            <img src={imageUrl} alt="Post" className="post-image" />
          </div>
        )}

        <div className="post-actions">
          <button
            className={`like-btn ${post.liked_by_me ? "liked" : ""}`}
            onClick={() => likeMutation.mutate(post.id)}
            disabled={likeMutation.isLoading}
          >
            👍 Like
          </button>

          <button onClick={() => setOpen(true)}>💬 Comment</button>

          <button
            onClick={() => exportContent.mutate(post.id)}
            disabled={exportContent.isLoading}
          >
            ⬇ Export
          </button>
        </div>

        {/* STATS */}
        <div className="post-stats">
          {post.likes} likes · {post.comments} comments
        </div>
      </div>

      <CommentsModal
        post={post}
        isOpen={open}
        onClose={() => setOpen(false)}
      />
    </>
  );
};

export default PostCard;
