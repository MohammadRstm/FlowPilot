import { api } from "./client";
import { returnDataFormat } from "./utils";




export async function login({email , password} : { password : string , email : string}){
  const res = await  api.post<AuthResponse>("login" , { email , password});
  return returnDataFormat(res);
}

export async function googleLogin(response : any){
  const res = await api.post("google" , {idToken: response.credential});
  return returnDataFormat(res);
}


export async function me(){
  const res = await api.get("auth/me");
  return returnDataFormat(res);
}

export const getToken = () => {
  return localStorage.getItem("token");
};

export const setToken = (token: string) => {
  localStorage.setItem("token", token);
};

export const clearToken = () => {
  localStorage.removeItem("token");
};