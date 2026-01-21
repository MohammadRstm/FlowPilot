import { useQuery } from "@tanstack/react-query"
import { returnDataFormat } from "../../utils/returnApiDataFormat";
import { api } from "../../../api/client";
import type { IsBeingFollowedParam } from "../types";


export const useIsBeingFollowedByUser = (userId?: IsBeingFollowedParam) => {
  return useQuery({
    queryKey: ["is-being-followed", userId],
    queryFn: () => isBeingFollowedByUser(userId),
    enabled: !!userId,
  });
};

const isBeingFollowedByUser = async (userId: IsBeingFollowedParam) =>{
    const response = await api.get(`auth/profile/isFollowed/${userId}`);

    return returnDataFormat(response);
}