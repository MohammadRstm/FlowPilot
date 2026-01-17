import { api } from "./client";

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

export async function login(email: string, password: string){
  const res =await  api.post<AuthResponse>("auth/login" , { email , password});
  return res.data;
}

export async function googleLogin(response : any){
  const res = api.post("auth/google" , {idToken: response.credential});
  
  return res.data;
}

export async function register(payload: RegisterPayload): Promise<AuthResponse> {
  const res = await api.post<AuthResponse>("auth/register" , payload);
  return res.data;
}

export async function me(){
  const res = await api.get("auth/me");

  if(!res.ok){
    throw new Error("Unauthenticated");
  }
  return res.json();
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