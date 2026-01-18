import { useQuery } from "@tanstack/react-query";
import { fetchProfile } from "../../../api/profile/fetchProfileDetails";

export const useProfileQuery = (userId?: string) => {
  return useQuery({
    queryKey: ["profile-details", userId ?? "me"],
    queryFn: () => fetchProfile(userId),
    enabled: true,
  });
};
