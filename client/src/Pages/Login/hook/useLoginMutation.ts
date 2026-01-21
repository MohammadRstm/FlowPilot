import { useMutation } from "@tanstack/react-query";
import { setToken } from "../../../api/auth";
import { api } from "../../../api/client";
import { returnDataFormat } from "../../utils/returnApiDataFormat";
import type { AuthResponse } from "../../types";

export const useLoginMutation = () => {
  return useMutation({
    mutationFn: (payload: any) => login(payload),
    onSuccess: (data) => {
      setToken(data.token);
      window.location.href = "/";
    },
  });
};


async function login({email , password} : { password : string , email : string}){
  const res = await api.post<AuthResponse>("login" , { email , password});
  return returnDataFormat(res);
}

