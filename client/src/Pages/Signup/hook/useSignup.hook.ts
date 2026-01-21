import { useMutation } from "@tanstack/react-query";
import { register as registerRequest, type RegisterPayload } from "../../../api/auth";
import { useToast } from "../../../context/toastContext";
import { ToastMessage } from "../../components/toast/toast.types";

export const useSignup = () =>{
    const { showToast } = useToast();
    return useMutation({
        mutationFn: (payload: RegisterPayload) => registerRequest(payload),
        onSuccess : () =>{
            showToast("Signup successfull" , ToastMessage.SUCCESS);
        }
    });
}
   
    
