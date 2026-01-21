import { api } from "../client";
import { returnDataFormat } from "../utils";

export type UserAccountType = {
  normalAccount: boolean;
  googleAccount: boolean;
};

export const getUserAccount = async (): Promise<UserAccountType> => {
  const resp = await api.get("auth/account");
  return returnDataFormat(resp);
};
