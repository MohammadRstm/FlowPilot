import { useQuery } from "@tanstack/react-query"
import { isBeingFollowedByUser } from "../../../api/profile/isBeingFollowedByUser";


export const useIsBeingFollowedByUser = (userId : number | undefined) =>{
    return useQuery({
        queryKey: ["is-being-followed"],
        queryFn: () => isBeingFollowedByUser(userId)
    });
}