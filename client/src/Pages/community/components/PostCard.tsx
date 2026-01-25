import { useState } from "react";
import { useExportPost } from "../hook/useExportPost";
import { useToggleLike } from "../hook/useToggleLike";
import CommentsModal from "./CommentsModal";
import type { PostCardData } from "../../profile/types";
import { Heart, MessageCircle, Download } from "lucide-react";
import { PostCardSkeleton } from "./PostCardSkeleton";

const BASE_URL = import.meta.env.VITE_PHOTO_BASE_URL;

type PostCardProps = {
  post: PostCardData | null;
  showHeader?: boolean;
  showActions?: boolean;
  showStats?: boolean;
  isPending?:boolean
}

const PostCard: React.FC<PostCardProps> = ({
  post,
  isPending,
  showHeader = true,
  showActions = true,
  showStats = true,
}) =>{
    if(isPending){
        return <PostCardSkeleton />;
    }

    if (!post) return null;

    const [open, setOpen] = useState(false);

    const likeMutation = useToggleLike();
    const exportContent = useExportPost();

    const imageUrl = post.photo ? `${BASE_URL}/${post.photo}` : null;

  return (
    <>
      <div className="post-card">
        {showHeader && post.author && (
          <div className="post-header">
            {post.avatar ? (
                <img
                  src={import.meta.env.VITE_PHOTO_BASE_URL + post.avatar || "/avatar-placeholder.png"}
                  alt={post.author}
                />
            ) : (
                <span className="avatar-initials">{post.author[0]}</span>
            )}

            <div>
              <div className="post-author">{post.author}</div>
              <div className="post-username">{post.username}</div>
            </div>

            {post.imports !== undefined && (
              <div className="post-imports">{post.imports} exports</div>
            )}
          </div>
        )}


        {post.title && <h3 className="post-title">{post.title}</h3>}

        {post.content && post.content.trim() !== "" && (
          <div className="post-content">{post.content}</div>
        )}

        {imageUrl && (
          <div className="post-image-wrapper">
            <img src={imageUrl} alt="Post" className="post-image" />
          </div>
        )}

        {showActions && (
          <div className="post-actions">
            <button
              className={`like-btn ${post.liked_by_me ? "liked" : ""}`}
              onClick={() => likeMutation.mutate(post.id)}
              disabled={likeMutation.isPending}
            >
                <Heart
                    size={18}
                    className={post.liked_by_me ? "icon-liked" : ""}
                    fill={post.liked_by_me ? "currentColor" : "none"}
                />likes
            </button>

            <button onClick={() => setOpen(true)}> <MessageCircle size={18} /> Comment</button>

            <button
              onClick={() => exportContent.mutate(post.id)}
              disabled={exportContent.isPending}
            >
               <Download size={18} /> Export
            </button>
          </div>
        )}

        {showStats && (
          <div className="post-stats">
            {post.likes ?? 0} likes · {post.comments ?? 0} comments
          </div>
        )}
      </div>

      {open && showActions && (
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