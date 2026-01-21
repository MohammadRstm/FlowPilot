import { useMutation, useQueryClient } from "@tanstack/react-query";
import { api } from "../../../api/client";
import { returnDataFormat } from "../../utils/returnApiDataFormat";
import { useToast } from "../../../context/toastContext";
import { ToastMessage } from "../../components/toast/toast.types";

interface CreatePostInputs {
  title: string;
  description?: string;
  file?: File;
  image?: File;
}

export const useCreatePost = () => {
  const queryClient = useQueryClient();
  const { showToast } = useToast();

  return useMutation({
    mutationFn: async (inputs: CreatePostInputs) => createNewPost(inputs),

    onError: (_err, _newPost, context: any) => {
      if (context?.previousPosts) {
        queryClient.setQueryData(["posts"], context.previousPosts);
      }
    },

    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["posts"] });
      showToast("Post released successfully" , ToastMessage.SUCCESS);
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
