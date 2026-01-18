import type { ProfileApiShape } from "../../Pages/profile/types";
import { api } from "../client";
import { returnDataFormat } from "../utils";

export const fetchProfile = async (userId?: string): Promise<ProfileApiShape> => {
  const res = await api.get("auth/profileDetails", {
    params: userId ? { user_id: userId } : undefined,
  });

  return returnDataFormat(res);
};