import { useMutation } from "@tanstack/react-query";
import { login } from "../../../api/auth";
import { setToken } from "../../../api/auth";

export const useLoginMutation = () => {
  return useMutation({
    mutationFn: (payload: any) => login(payload),
    onSuccess: (data) => {
      setToken(data.token);
      window.location.href = "/";
    },
  });
};
