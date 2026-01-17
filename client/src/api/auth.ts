import axios from "axios";

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

export interface RegisterPayload {
  first_name: string;
  last_name: string;
  email: string;
  password: string;
}


const BASE_URL = import.meta.env.VITE_BASE_URL;
const prefix = "auth";

export const authUrl = `${BASE_URL}/${prefix}`;

export async function login(email: string, password: string){
  const res = await axios.post<AuthResponse>(
    `${authUrl}/login`,
    {
      email,
      password,
    }
  );
  console.log(res);
  return res.data;
}

export async function register(payload: RegisterPayload): Promise<AuthResponse> {
 const res = await axios.post<AuthResponse>(
    `${authUrl}/register`,
    payload 
  );


  return res.data.data;
}