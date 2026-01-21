import { api } from "../client";
import { returnDataFormat } from "../utils";

export const unlinkGoogle = async () => {
  const resp = await api.put("auth/unlinkGoogleAccount");
  return returnDataFormat(resp);
};
