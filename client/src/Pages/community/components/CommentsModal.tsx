import React, { useEffect } from "react";
import PostCard from "./PostCard";
import { useFetchPostComments } from "../hook/useFetchPostComments";
import { usePostComment } from "../hook/usePostComment";
import CommentItem from "./CommentItem";

type Props = {
  post: any;
  isOpen: boolean;
  onClose: () => void;
};

const CommentsModal: React.FC<Props> = ({ post, isOpen, onClose }) => {
  const { data: comments, isLoading } = useFetchPostComments(post.id , isOpen);
  const createComment = usePostComment();

  useEffect(() => {
    const esc = (e: KeyboardEvent) => {
      if (e.key === "Escape") onClose();
    };
    document.addEventListener("keydown", esc);
    return () => document.removeEventListener("keydown", esc);
  }, [onClose]);

  if (!isOpen) return null;

  return (
    <div className="comments-modal-overlay" onClick={onClose}>
      <div
        className="comments-modal"
        onClick={(e) => e.stopPropagation()}
      >
        <div className="comments-modal-post">
          <PostCard post={post} />
        </div>

        <div className="comments-modal-comments">
          <div className="comments-list">
            {!isLoading && comments?.length === 0 && (
                <div className="empty-comments">No comments yet</div>
            )}

            {isLoading && <div>Loading comments…</div>}
            {comments?.map((comment: any) => (
              <CommentItem key={comment.id} comment={comment} />
            ))}
          </div>

          <form
            className="comment-input"
            onSubmit={(e) => {
              e.preventDefault();
              const form = e.currentTarget;
              const textarea = form.elements.namedItem(
                "content"
              ) as HTMLTextAreaElement;

              if (!textarea.value.trim()) return;

              createComment.mutate({
                postId : post.id ,
                content:  textarea.value
            });
              textarea.value = "";
            }}
          >
            <textarea
              name="content"
              placeholder="Write a comment…"
              rows={1}
              onInput={(e) => {
                const el = e.currentTarget;
                el.style.height = "auto";
                el.style.height = Math.min(el.scrollHeight, 160) + "px";
              }}
            />
            <button type="submit">Send</button>
          </form>
        </div>
      </div>
    </div>
  );
};

export default CommentsModal;
