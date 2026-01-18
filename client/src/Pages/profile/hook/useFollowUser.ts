import { useMutation, useQueryClient } from "@tanstack/react-query"



export const useFollowUser = () =>{
    const queryClient = useQueryClient();
    return useMutation({
        mutationFn: (userId: number) => followUser(userId),
        onSuccess: () =>{
            
        }
    })
}