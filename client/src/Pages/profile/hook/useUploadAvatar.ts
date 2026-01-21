import { useMutation, useQueryClient } from "@tanstack/react-query";
import { uploadAvatar } from "../../../api/profile/uploadAvatar";

export const useUploadAvatar = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (file: File) => uploadAvatar(file),
    onSuccess: () => {
      // invalidate profile query to refetch the updated photo
      queryClient.invalidateQueries({ queryKey: ["profile"] });
    },
  });
};
