import { useMutation, useQueryClient } from "@tanstack/react-query";
import axios from "axios";

export const useUploadAvatar = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (file: File) =>,
    onSuccess: () => {
      // invalidate profile query to refetch the updated photo
      queryClient.invalidateQueries({ queryKey: ["profile"] });
    },
  });
};
