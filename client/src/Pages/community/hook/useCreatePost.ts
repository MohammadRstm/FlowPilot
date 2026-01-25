import { useMutation, useQueryClient } from "@tanstack/react-query";
import { api } from "../../../api/client";
import { returnDataFormat } from "../../utils/returnApiDataFormat";
import { useToast } from "../../../context/toastContext";
import { ToastMessage } from "../../components/toast/toast.types";
import { useAuth } from "../../../context/useAuth";
import type { PostDto } from "../types";

interface CreatePostInputs {
  title: string;
  description?: string;
  file?: File;
  image?: File;
}

export const useCreatePost = () => {
  const queryClient = useQueryClient();
  const { showToast } = useToast();
  const { user } = useAuth();

  return useMutation({
    mutationFn: async (inputs: CreatePostInputs) => createNewPost(inputs),

    onMutate: async (inputs: CreatePostInputs) => {
      await queryClient.cancelQueries({ queryKey: ["community-posts"] });

      const previousData = queryClient.getQueryData<any>(["community-posts"]);

      const optimisticPost: PostDto = {
        id: Date.now(), // Temporary ID
        author: `${user?.first_name} ${user?.last_name}`.trim(),
        username: user?.email || "User",
        avatar: user?.profile_pic || null,
        title: inputs.title,
        content: inputs.description || "",
        photo: "", // Will be updated after server response
        likes: 0,
        comments: 0,
        exports: 0,
        liked_by_me: false,
      };

      queryClient.setQueryData(
        ["community-posts"],
        prependOptimisticPost(optimisticPost)
      );

      return { previousData };
    },

    onError: (_err, _inputs, context: any) => {
      if(context?.previousData){
        queryClient.setQueryData(["community-posts"], context.previousData);
      }
      showToast("Failed to create post", ToastMessage.ERROR);
    },

    onSuccess: () => {
      // Invalidate to refetch fresh data from server
      showToast("Post released successfully", ToastMessage.SUCCESS);
    },
  });
};

const getFormData = (inputs : CreatePostInputs) =>{
    const formData = new FormData();
    formData.append("title", inputs.title);
    if (inputs.description) formData.append("description", inputs.description);
    if (inputs.file) formData.append("file", inputs.file);
    if (inputs.image) formData.append("image", inputs.image);

    return formData;
}

const createNewPost = async (inputs : CreatePostInputs) =>{
    const formData = getFormData(inputs);

    const resp = await api.post("/auth/community/createPost", formData, {
    headers: {
        "Content-Type": "multipart/form-data",
    },
    });

    return returnDataFormat(resp);
}


const prependOptimisticPost =
  (optimisticPost: PostDto) =>
  (old: any) => {
    if (!old) return old;

    return {
      ...old,
      pages: old.pages.map((page: any, index: number) =>
        index === 0
          ? {
              ...page,
              data: [optimisticPost, ...page.data],
            }
          : page
      ),
    };
  };
