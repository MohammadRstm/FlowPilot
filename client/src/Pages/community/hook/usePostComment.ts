import { useMutation, useQueryClient } from "@tanstack/react-query"
import type { PostCommentPayload } from "../types";
import { returnDataFormat } from "../../utils/returnApiDataFormat";
import { api } from "../../../api/client";

export const usePostComment = () =>{
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: ({ postId , content } : PostCommentPayload ) => postComment(postId , content),
        onMutate: async () => {
            await queryClient.cancelQueries({ queryKey: ["post-comments"] });
        },
        onSuccess: () => {
          queryClient.invalidateQueries({
            queryKey : ["post-comments"]
        })
        },
    });
}

const postComment =async  (postId : number , content : string) =>{
    const payload = {
        content 
    };
    const resp = await api.post(`auth/community/postComment/${postId}` , payload);
    return returnDataFormat(resp);
}