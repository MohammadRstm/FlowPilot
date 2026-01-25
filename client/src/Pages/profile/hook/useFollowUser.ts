import { useMutation, useQueryClient } from "@tanstack/react-query"
import { useToast } from "../../../context/toastContext";
import { handleApiError } from "../../utils/handleErrorMessage";
import { returnDataFormat } from "../../utils/returnApiDataFormat";
import { api } from "../../../api/client";
import type { FollowUserParam } from "../types";


export const useFollowUser = () =>{
    const queryClient = useQueryClient();
    const { showToast } = useToast();
    return useMutation({
        mutationFn: (userId : FollowUserParam) => followUser(userId),
        onMutate: async (userId) => {
            if (!userId) return;
            
            await queryClient.cancelQueries({ queryKey: ["is-being-followed", userId] });
            await queryClient.cancelQueries({ queryKey: ["profile-details", userId] });

            // Get previous data for rollback
            const previousFollowStatus = queryClient.getQueryData(["is-being-followed", userId]);
            const previousProfile = queryClient.getQueryData(["profile-details", userId]);

            // Update follow status
            queryClient.setQueryData(["is-being-followed", userId], (old: any) => {
                if (!old) return old;
                return {
                    ...old,
                    isFollowing: !old.isFollowing,
                };
            });

            // Update followers count in profile
            queryClient.setQueryData(["profile-details", userId], (old: any) => {
                if (!old) return old;
                const currentFollowStatus = queryClient.getQueryData<any>(["is-being-followed", userId]);
                const isNowFollowing = currentFollowStatus?.isFollowing;
                
                const followers = old.followers || [];
                const authUser = queryClient.getQueryData<any>(["profile-details", "me"])?.user;
                
                if (isNowFollowing && authUser) {
                    // Add current user to followers if following
                    return {
                        ...old,
                        followers: [
                            {
                                id: authUser.id,
                                full_name: `${authUser.first_name} ${authUser.last_name}`.trim(),
                                photo_url: authUser.photo_url,
                                email: authUser.email,
                            },
                            ...followers,
                        ],
                    };
                } else {
                    // Remove current user from followers if unfollowing
                    return {
                        ...old,
                        followers: followers.filter((f: any) => f.id !== authUser?.id),
                    };
                }
            });

            return { previousFollowStatus, previousProfile };
        },
        onError: (err, userId, context) => {
            if (!userId) return;
            queryClient.setQueryData(
                ["is-being-followed", userId],
                context?.previousFollowStatus
            );
            queryClient.setQueryData(
                ["profile-details", userId],
                context?.previousProfile
            );
            handleApiError(err , showToast);
        },
        onSuccess: (_, userId) => {
            if (!userId) return;

            queryClient.invalidateQueries({
                queryKey: ["is-being-followed", userId],
            });

            queryClient.invalidateQueries({
                queryKey: ["profile-details", userId],
            });
        },
    })
}

const followUser = async (userId: FollowUserParam) =>{
    if (!userId) throw new Error("User ID is required");
    const res = await api.post(`auth/profile/follow/${userId}`);

    return returnDataFormat(res);
}