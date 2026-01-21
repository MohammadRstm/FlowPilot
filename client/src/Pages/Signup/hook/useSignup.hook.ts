import { useMutation } from "@tanstack/react-query";
import { register as registerRequest, type RegisterPayload } from "../../../api/auth";
import { useToast } from "../../../context/toastContext";
import { handleApiError } from "../../utls/handleErrorMessage";

export const useSignup = () =>{
    const { showToast } = useToast();
    return useMutation({
        mutationFn: (payload: RegisterPayload) => registerRequest(payload),
        onError: (err: any) => {
          handleApiError(err , showToast);
        },
    });
}
   
    
