import { useMutation, useQueryClient } from "@tanstack/react-query";
import { toggleCommentLike } from "../../../api/community/toggleCommentLike";

export function useToggleCommentLike() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: toggleCommentLike,

    onMutate: async () => {
      await queryClient.cancelQueries({ queryKey: ["post-comments"] });
    },

    onSuccess: (data, commentId) => {
      queryClient.setQueryData(["post-comments"], (old: any) => {
        if (!old) return old;

        return {
          ...old,
          comments: old.comments.map((comment: any) => ({
            ...comment,
            data: comment.data.map((comment: any) =>
              comment.id === commentId
                ? { ...comment, likes: data.likes , liked_by_me: data.liked }
                : comment
            ),
          })),
        };
      });
    },
  });
}
