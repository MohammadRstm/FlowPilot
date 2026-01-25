export const PostCardSkeleton = () =>{
       return (
        <div className="post-card skeleton">
            <div className="post-header">
            <div className="skeleton-avatar" />
            <div className="skeleton-lines">
                <div className="skeleton-line short" />
                <div className="skeleton-line tiny" />
            </div>
            </div>
            <br />

            <div className="skeleton-line title" />
            <div className="skeleton-line" />
            <div className="skeleton-line" />
            <div className="skeleton-line image" />
        </div>
        );
}