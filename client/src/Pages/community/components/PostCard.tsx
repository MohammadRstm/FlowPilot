import { useState } from "react";
import { useExportPost } from "../hook/useExportPost";
import type { PostDto } from "../hook/useFetchPosts";
import { useToggleLike } from "../hook/useToggleLike";
import CommentsModal from "./CommentsModal";
import type { PostCardData } from "../../profile/types";

type PostCardProps = {
  post: PostCardData;

  showHeader?: boolean;
  showActions?: boolean;
  showStats?: boolean;
};

const BASE_URL = import.meta.env.VITE_PHOTO_BASE_URL;

const PostCard: React.FC<{
  post: PostCardData;
  showHeader?: boolean;
  showActions?: boolean;
  showStats?: boolean;
}> = ({
  post,
  showHeader = true,
  showActions = true,
  showStats = true,
}) => {
  const [open, setOpen] = useState(false);

  const likeMutation = useToggleLike();
  const exportContent = useExportPost();

  const imageUrl = post.photo ? `${BASE_URL}/${post.photo}` : null;

  return (
    <>
      <div className="post-card">
        {/* HEADER */}
        {showHeader && post.author && (
          <div className="post-header">
            <img
              src={import.meta.env.VITE_PHOTO_BASE_URL + post.avatar || "/avatar-placeholder.png"}
              alt={post.author}
            />

            <div>
              <div className="post-author">{post.author}</div>
              <div className="post-username">{post.username}</div>
            </div>

            {post.imports !== undefined && (
              <div className="post-imports">{post.imports} exports</div>
            )}
          </div>
        )}

        {/* TITLE */}
        {post.title && <h3 className="post-title">{post.title}</h3>}

        {/* CONTENT */}
        {post.content && post.content.trim() !== "" && (
          <div className="post-content">{post.content}</div>
        )}

        {/* IMAGE */}
        {imageUrl && (
          <div className="post-image-wrapper">
            <img src={imageUrl} alt="Post" className="post-image" />
          </div>
        )}

        {/* ACTIONS */}
        {showActions && (
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
        )}

        {/* STATS */}
        {showStats && (
          <div className="post-stats">
            {post.likes ?? 0} likes · {post.comments ?? 0} comments
          </div>
        )}
      </div>

      {showActions && (
        <CommentsModal
          post={post as any}
          isOpen={open}
          onClose={() => setOpen(false)}
        />
      )}
    </>
  );
};

export default PostCard;