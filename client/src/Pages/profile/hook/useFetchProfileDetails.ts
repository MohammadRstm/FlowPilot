import { useQuery } from "@tanstack/react-query";
import { returnDataFormat } from "../../utils/returnApiDataFormat";
import { api } from "../../../api/client";
import type { ProfileApiShape } from "../types";

export const useProfileQuery = (userId?: string) => {
  return useQuery({
    queryKey: ["profile-details", userId ?? "me"],
    queryFn: () => fetchProfile(userId),
    enabled: true,
  });
};

const fetchProfile = async (userId?: string): Promise<ProfileApiShape> => {
  const res = await api.get("auth/profile/profileDetails", {
    params: userId ? { user_id: userId } : undefined,
  });

  return returnDataFormat(res);
};