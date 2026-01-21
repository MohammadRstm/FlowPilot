import { useMutation } from "@tanstack/react-query";
import { register as registerRequest, type RegisterPayload } from "../../../api/auth";
import { useToast } from "../../../context/toastContext";

export const useSignup = () =>{
    const { showToast } = useToast();
    return useMutation({
        mutationFn: (payload: RegisterPayload) => registerRequest(payload),
        onError: (err: any) => {
            if(err?.response?.data?.success === false){
                showToast(err.response.data.message, "error");
            }else{
                showToast("Something went wrong. Please try again.", "error");
            }
        },
    });
}
   
    
