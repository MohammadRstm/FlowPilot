import { api } from "./client";
import { returnDataFormat } from "./utils";

export interface AuthUser {
  id: number;
  first_name: string;
  last_name: string;
  email: string;
}

export interface AuthResponse {
  token: string;
  user: AuthUser;
}

export interface RegisterPayload{
  first_name: string;
  last_name: string;
  email: string;
  password: string;
}

export async function login({email , password} : { password : string , email : string}){
  const res = await  api.post<AuthResponse>("login" , { email , password});
  return returnDataFormat(res);
}

export async function googleLogin(response : any){
  const res = await api.post("google" , {idToken: response.credential});
  return returnDataFormat(res);
}

export async function register(payload: RegisterPayload): Promise<AuthResponse> {
  const res = await api.post<AuthResponse>("register" , payload);
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