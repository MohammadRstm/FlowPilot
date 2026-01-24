import { QueryClient, useMutation, useQueryClient } from "@tanstack/react-query";
import { returnDataFormat } from "../../utils/returnApiDataFormat";
import { api } from "../../../api/client";

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

      optimisticCommentLikeUpdate(queryClient , postId , commentId);

      return { previousComments };
    },

    onError: (_err, _commentId, context) => {
      queryClient.setQueryData(
        ["post-comments", postId],
        context?.previousComments
      );
    },

    onSuccess: (data, commentId) => {
        liveUpdateOnSuccess(queryClient , postId , commentId , data);
    },
  });
}


const toggleCommentLike = async (commentId : number)=>{
    const response = await api.post(`auth/community/toggleCommentLike/${commentId}`);

    return returnDataFormat(response);
}

const optimisticCommentLikeUpdate = (queryClient : QueryClient , postId : number , commentId : number) =>{
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
}

const liveUpdateOnSuccess = (queryClient : QueryClient , postId : number , commentId:number , data:any) => {
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
}