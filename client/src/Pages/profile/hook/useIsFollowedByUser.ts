import { useQuery } from "@tanstack/react-query"
import { isBeingFollowedByUser } from "../../../api/profile/isBeingFollowedByUser";


export const useIsBeingFollowedByUser = (userId?: number) => {
  return useQuery({
    queryKey: ["is-being-followed", userId],
    queryFn: () => isBeingFollowedByUser(userId as number),
    enabled: !!userId,
  });
};
