import { api } from "../client";
import { returnDataFormat } from "../utils";

export const linkN8n = async (payload: {
  base_url: string;
  api_key: string;
}) => {
  const resp = await api.post("auth/linkN8nAccount", payload);
  return returnDataFormat(resp);
};
