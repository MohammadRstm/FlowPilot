import { useMutation } from "@tanstack/react-query";
import { register as registerRequest, type RegisterPayload } from "../../../api/auth";

export const useSignup = () => {
    return useMutation({
        mutationFn: (payload: RegisterPayload) => registerRequest(payload)
    });
}
   
    
