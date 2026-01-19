import { useMutation, useQueryClient } from "@tanstack/react-query";
import { fetchExport } from "../../../api/community/fetchExport";

export function useExportPost(){
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (postId : number) => fetchExport(postId),

     onMutate: async (postId) => {
      await queryClient.cancelQueries({ queryKey: ["community-posts"] });

      const previous = queryClient.getQueryData<any>(["community-posts"]);

      queryClient.setQueryData(["community-posts"], (old: any) => {
        if (!old) return old;

        return {
          ...old,
          pages: old.pages.map((page: any) => ({
            ...page,
            data: page.data.map((post: any) =>
              post.id === postId
                ? { ...post, exports: post.exports + 1 }
                : post
            ),
          })),
        };
      });

      return { previous };
    },

    onSuccess: (data, postId) => {
      const blob = new Blob([JSON.stringify(data, null, 2)], {
        type: "application/json",
      });

      const url = URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download = `post-${postId}.json`;
      a.click();
      URL.revokeObjectURL(url);
    },
    onError: (err) => {
      console.error("Export failed", err);
      alert("Failed to export post");
    },
  });
}
