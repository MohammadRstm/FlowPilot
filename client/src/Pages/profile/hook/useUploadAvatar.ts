import { useMutation, useQueryClient } from "@tanstack/react-query";
import { ToastMessage } from "../../components/toast/toast.types";
import { useToast } from "../../../context/toastContext";
import { api } from "../../../api/client";
import { returnDataFormat } from "../../utils/returnApiDataFormat";

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


const uploadAvatar = async (file: File ) => {
    const formData = new FormData();
    formData.append("avatar", file);

    const resp = await api.post("auth/profile/avatar" , formData , {
        headers: { "Content-Type": "multipart/form-data" },
    });

    return returnDataFormat(resp);  
}