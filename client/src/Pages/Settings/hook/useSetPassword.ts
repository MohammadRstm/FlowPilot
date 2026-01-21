import { useMutation } from "@tanstack/react-query";
import { setPassword } from "../../../api/settings/setPassword";
import { ToastMessage } from "../../components/toast/toast.types";
import { useToast } from "../../../context/toastContext";

export const useSetPassword = () => {
    const { showToast } = useToast();
    return useMutation({
        mutationFn: setPassword,
        onSuccess:() => showToast("Set new password successfully" , ToastMessage.SUCCESS)
    });
};
