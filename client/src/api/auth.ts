import { returnDataFormat } from "../Pages/utils/returnApiDataFormat";
import { api } from "./client";

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