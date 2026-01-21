import { useMutation, useQueryClient } from "@tanstack/react-query";
import { api } from "../../../api/client";
import { returnDataFormat } from "../../utils/returnApiDataFormat";

export function useExportPost(){
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (postId : number) => fetchExport(postId),

     onMutate: async (postId) => {
      await queryClient.cancelQueries({ queryKey: ["community-posts"] });

      const previous = queryClient.getQueryData<any>(["community-posts"]);

      queryClient.setQueryData(["community-posts"], (old: any) => {
        if (!old) return old;

        return optimisticImportCountImport(old , postId);
      });

      return { previous };
    },

    onSuccess: (data, postId) => {
      startDownload(data , postId);
    },
  });
}

const fetchExport = async (postId : number) =>{
    const response = await api.get(`auth/community/export/${postId}`);
    return returnDataFormat(response);
}

const startDownload = (data :any , postId : number) =>{
   const blob = new Blob([JSON.stringify(data, null, 2)], {
      type: "application/json",
    });

    const url = URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = `post-${postId}.json`;
    a.click();
    URL.revokeObjectURL(url);
}

const optimisticImportCountImport = (old : any , postId : number) =>{
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
  }
}