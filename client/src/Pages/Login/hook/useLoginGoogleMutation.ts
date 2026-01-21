import { useMutation } from "@tanstack/react-query";
import { setToken } from "../../../api/auth";
import { api } from "../../../api/client";
import { returnDataFormat } from "../../utils/returnApiDataFormat";

export const useLoginGoogleMutation = () => {
  return useMutation({
    mutationFn: (payload: any) => googleLogin(payload),
    onSuccess: (data) => {
      setToken(data.token);
      window.location.href = "/";
    },
  });
};


async function googleLogin(response : any){
  const res = await api.post("google" , {idToken: response.credential});
  return returnDataFormat(res);
}