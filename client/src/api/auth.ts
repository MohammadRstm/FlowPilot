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

const BASE_URL = import.meta.env.VITE_BASE_URL;
const prefix = "auth";

export const authUrl = `${BASE_URL}/${prefix}`;

async function handleResponse(res: Response): Promise<AuthResponse> {
  const payload = await res.json().catch(() => null);

  if (!res.ok) {
    const message = payload?.message || "Request failed";
    throw new Error(message);
  }

  return payload.data as AuthResponse;
}

export async function login(email: string, password: string): Promise<AuthResponse> {
  const res = await fetch(`${authUrl}/login`, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({ email, password }),
  });

  return handleResponse(res);
}

export interface RegisterPayload {
  first_name: string;
  last_name: string;
  email: string;
  password: string;
}

export async function register(payload: RegisterPayload): Promise<AuthResponse> {
  const res = await fetch(`${authUrl}/register`, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify(payload),
  });

  return handleResponse(res);
}