import { useMutation } from "@tanstack/react-query";
import { ToastMessage } from "../../components/toast/toast.types";
import { useToast } from "../../../context/toastContext";
import { returnDataFormat } from "../../utils/returnApiDataFormat";
import { api } from "../../../api/client";
import type { SetPasswordPayload } from "../types";

export const useSetPassword = () => {
    const { showToast } = useToast();
    return useMutation({
        mutationFn: setPassword,
        onSuccess:() => showToast("Set new password successfully" , ToastMessage.SUCCESS)
    });
};

const setPassword = async (payload: SetPasswordPayload) => {
  const resp = await api.post("auth/setPassword", payload);
  return returnDataFormat(resp);
};