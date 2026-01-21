import { useMutation, useQueryClient } from "@tanstack/react-query";
import { uploadAvatar } from "../../../api/profile/uploadAvatar";
import { ToastMessage } from "../../components/toast/toast.types";
import { useToast } from "../../../context/toastContext";

export const useUploadAvatar = () => {
    const queryClient = useQueryClient();
    const { showToast } = useToast();
    return useMutation({
        mutationFn: (file: File) => uploadAvatar(file),
        onSuccess: () => {
        queryClient.invalidateQueries({ queryKey: ["profile"] });
        showToast("Avatar updated" , ToastMessage.SUCCESS)
        },
    });
};
