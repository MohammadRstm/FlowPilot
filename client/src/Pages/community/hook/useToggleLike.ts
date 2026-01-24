import { useMutation, useQueryClient } from "@tanstack/react-query";
import { api } from "../../../api/client";
import { returnDataFormat } from "../../utils/returnApiDataFormat";

export function useToggleLike() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: toggleLike,

    onMutate: async () => {
      await queryClient.cancelQueries({ queryKey: ["community-posts"] });
    },

    onSuccess: (data, postId) => {
      queryClient.setQueryData(["community-posts"], (old: any) => {
        if (!old) return old;

        return liveUpdateOnSuccess(old , postId , data);
      });
    },
  });
}



const toggleLike = async (postId : number)=>{
    const response = await api.post(`auth/community/toggleLike/${postId}`);

    return returnDataFormat(response);
}


const liveUpdateOnSuccess = (old : any , postId : number , data : any) =>{
    return {
        ...old,
        pages: old.pages.map((page: any) => ({
        ...page,
        data: page.data.map((post: any) =>
            post.id === postId
            ? { ...post, likes: data.likes , liked_by_me: data.liked }
            : post
        ),
        })),
    }
}