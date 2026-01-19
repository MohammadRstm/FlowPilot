import { useMutation, useQueryClient } from "@tanstack/react-query";
import { toggleLike } from "../../../api/community/toggleLike";

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
        };
      });
    },
  });
}
