import { useMutation } from "@tanstack/react-query";
import { googleLogin, setToken } from "../../../api/auth";

export const useLoginGoogleMutation = () => {
  return useMutation({
    mutationFn: (payload: any) => googleLogin(payload),
    onSuccess: (data) => {
      setToken(data.token);
      window.location.href = "/";
    },
  });
};
