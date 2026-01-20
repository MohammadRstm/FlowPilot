import { useMutation, useQueryClient } from "@tanstack/react-query";
import { api } from "../../../api/client";
import { returnDataFormat } from "../../../api/utils";

interface CreatePostInputs {
  title: string;
  description?: string;
  file?: File;
  image?: File;
}

export const useCreatePost = () => {
  const queryClient = useQueryClient();

  return useMutation({
    // mutation function: sends data to the backend
    mutationFn: async (inputs: CreatePostInputs) => {
      const formData = new FormData();
      formData.append("title", inputs.title);
      if (inputs.description) formData.append("description", inputs.description);
      if (inputs.file) formData.append("file", inputs.file);
      if (inputs.image) formData.append("image", inputs.image);

      const resp = await api.post("/auth/community/createPost", formData, {
        headers: {
          "Content-Type": "multipart/form-data",
        },
      });

      return returnDataFormat(resp);
    },

    // optional: optimistic UI / caching
    onMutate: async (newPost: CreatePostInputs) => {
      // cancel any outgoing queries
      await queryClient.cancelQueries({ queryKey: ["posts"] });

      // snapshot previous posts
      const previousPosts = queryClient.getQueryData<any[]>(["posts"]);

      // optionally add the new post to cache immediately (optimistic)
      const optimisticPost = {
        id: Math.random(),
        title: newPost.title,
        description: newPost.description,
        file: newPost.file,
        image: newPost.image ? URL.createObjectURL(newPost.image) : null,
        created_at: new Date().toISOString(),
        user: { first_name: "You", last_name: "", profile_pic: null },
      };

      queryClient.setQueryData(["posts"], [optimisticPost, ...(previousPosts || [])]);

      return { previousPosts };
    },

    onError: (_err, _newPost, context: any) => {
      // rollback if error occurs
      if (context?.previousPosts) {
        queryClient.setQueryData(["posts"], context.previousPosts);
      }
    },

    onSuccess: () => {
      // refresh posts list from backend
      queryClient.invalidateQueries({ queryKey: ["posts"] });
    },
  });
};
