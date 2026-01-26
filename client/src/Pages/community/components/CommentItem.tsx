import { useToggleCommentLike } from "../hook/useToggleCommentLike";

const CommentItem: React.FC<{ comment: any }> = ({ comment }) => {
  const toggleLike = useToggleCommentLike(comment.post_id);
  return (
    <div className="comment">
        {comment.user?.photo_url ? (
            <img
              src={import.meta.env.VITE_PHOTO_BASE_URL + comment.user?.photo_url}
              alt="avatar"
              className="comment-avatar"
            />
        ) : (
            <span className="avatar-initials">{comment.user?.first_name[0].toUpperCase()}</span>
        )}

      <div className="comment-body">
        <div className="comment-author">
          {comment.user?.first_name} {comment.user?.last_name}
        </div>

        <div className="comment-content">{comment.content}</div>

        <div className="comment-actions">
          <button
            className={`comment-like ${
              comment.liked_by_me ? "liked" : ""
            }`}
            onClick={() => toggleLike.mutate(comment.id)}
            aria-label="Like comment"
          >
            <svg
              viewBox="0 0 24 24"
              width="16"
              height="16"
              fill="currentColor"
            >
              <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 6 4 4 6.5 4c1.74 0 3.41 1.01 4.22 2.44C11.09 5.01 12.76 4 14.5 4 17 4 19 6 19 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" />
            </svg>
            <span style={{color:"white"}}>{comment.likes}</span>
          </button>
        </div>
      </div>
    </div>
  );
};

export default CommentItem;
