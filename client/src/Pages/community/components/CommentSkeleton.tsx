const CommentSkeleton = () => {
  return (
    <div className="comment-item skeleton">
      <div className="comment-avatar skeleton-avatar" />

      <div className="comment-body">
        <div className="comment-header">
          <div className="skeleton-line name" />
          <div className="skeleton-line username" />
        </div>

        <div className="comment-content">
          <div className="skeleton-line content" />
          <div className="skeleton-line content" />
        </div>
      </div>
    </div>
  );
};

export default CommentSkeleton;
