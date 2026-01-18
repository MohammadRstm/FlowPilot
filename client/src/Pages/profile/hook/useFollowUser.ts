import { useMutation } from "@tanstack/react-query"
import { followUser } from "../../../api/profile/followUser";


export const useFollowUser = () =>{
    return useMutation({
        mutationFn: (userId: number) => followUser(userId),
    })
}