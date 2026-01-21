import { api } from "../client";
import { returnDataFormat } from "../utils";

export const setPassword = async (payload: {
  current_password?: string;
  new_password: string;
  new_password_confirmation: string;
}) => {
  const resp = await api.post("auth/setPassword", payload);
  return returnDataFormat(resp);
};