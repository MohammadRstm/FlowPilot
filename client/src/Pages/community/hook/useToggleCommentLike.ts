import { useMutation, useQueryClient } from "@tanstack/react-query";
import { toggleCommentLike } from "../../../api/community/toggleCommentLike";

export function useToggleCommentLike(postId: number) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: toggleCommentLike,

    onMutate: async (commentId: number) => {
      await queryClient.cancelQueries({
        queryKey: ["post-comments", postId],
      });

      const previousComments = queryClient.getQueryData<any[]>([
        "post-comments",
        postId,
      ]);

      queryClient.setQueryData(
        ["post-comments", postId],
        (old: any[] | undefined) =>
          old?.map((comment) =>
            comment.id === commentId
              ? {
                  ...comment,
                  liked_by_me: !comment.liked_by_me,
                  likes: comment.liked_by_me
                    ? comment.likes - 1
                    : comment.likes + 1,
                }
              : comment
          )
      );

      return { previousComments };
    },

    onError: (_err, _commentId, context) => {
      queryClient.setQueryData(
        ["post-comments", postId],
        context?.previousComments
      );
    },


    onSuccess: (data, commentId) => {
      queryClient.setQueryData(
        ["post-comments", postId],
        (old: any[] | undefined) =>
          old?.map((comment) =>
            comment.id === commentId
              ? {
                  ...comment,
                  likes: data.likes,
                  liked_by_me: data.liked,
                }
              : comment
          )
      );
    },
  });
}
