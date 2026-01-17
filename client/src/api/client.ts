import axios from "axios";
import { clearToken, getToken } from "./auth";

export const api = axios.create({
  baseURL: import.meta.env.VITE_BASE_URL,
  withCredentials: false, 
});

api.interceptors.request.use(
  (config) => {
    const token = getToken();

    if(token){
      config.headers.Authorization = `Bearer ${token}`;
    }

    return config;
  },
);

api.interceptors.response.use(
  (response) =>response,
  (error) => {
    if (error.response?.status === 401){// token expired/ unauthenticated
      clearToken();
    }
    return Promise.reject(error);
  }
);

export const returnDataFormat = (resp : any) =>{
  return resp.data.data;
}


