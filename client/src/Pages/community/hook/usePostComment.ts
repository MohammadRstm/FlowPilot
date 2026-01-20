import { useMutation, useQueryClient } from "@tanstack/react-query"
import { postComment } from "../../../api/community/postComment";

export const usePostComment = () =>{
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: ({ postId , content } : {postId : number , content : string}) => postComment(postId , content),
        onMutate: async () => {
            await queryClient.cancelQueries({ queryKey: ["post-a-comment"] });
        },
        onSuccess: () => {
          queryClient.invalidateQueries({
            queryKey : ["post-a-comment"]
        })
        },
    })
}