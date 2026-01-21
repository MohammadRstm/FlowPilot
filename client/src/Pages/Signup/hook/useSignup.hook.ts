import { useMutation } from "@tanstack/react-query";
import { useToast } from "../../../context/toastContext";
import { ToastMessage } from "../../components/toast/toast.types";
import { api } from "../../../api/client";
import { returnDataFormat } from "../../../api/utils";
import type { RegisterPayload } from "../types";
import type { AuthResponse } from "../../types";

export const useSignup = () =>{
    const { showToast } = useToast();
    return useMutation({
        mutationFn: (payload: RegisterPayload) => register(payload),
        onSuccess : () =>{
            showToast("Signup successfull" , ToastMessage.SUCCESS);
        }
    });
}

const register = async (payload: RegisterPayload) : Promise<AuthResponse> => {
  const res = await api.post<AuthResponse>("register" , payload);
  return returnDataFormat(res);
}

    
