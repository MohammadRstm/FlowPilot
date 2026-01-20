import { useState } from "react";
import { useExportPost } from "../hook/useExportPost";
import type { PostDto } from "../hook/useFetchPosts";
import { useToggleLike } from "../hook/useToggleLike";
import CommentsModal from "./CommentsModal";

const PostCard: React.FC<{ post: PostDto }> = ({ post }) => {

    const [open, setOpen] = useState(false);

    const likeMutation = useToggleLike();
    const exportContent = useExportPost();


  return (
    <>
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

        <button onClick={() => setOpen(true)}>💬 Comment</button>
        <button 
        onClick={() => exportContent.mutate(post.id)}
        disabled={exportContent.isLoading}
        >
        ⬇ Export
        </button>
      </div>

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
