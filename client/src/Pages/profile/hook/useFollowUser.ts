import { useMutation, useQueryClient } from "@tanstack/react-query"
import { followUser } from "../../../api/profile/followUser";
import { useToast } from "../../../context/toastContext";
import { ToastMessage } from "../../components/toast/toast.types";
import { handleApiError } from "../../utls/handleErrorMessage";


export const useFollowUser = () =>{
    const queryClient = useQueryClient();
    const { showToast } = useToast();
    return useMutation({
        mutationFn: (userId: number | undefined) => followUser(userId),
        onMutate: async (userId) => {
            await queryClient.cancelQueries({ queryKey: ["is-being-followed", userId] });

            const previous = queryClient.getQueryData(["is-being-followed", userId]);

            queryClient.setQueryData(["is-being-followed", userId], (old: any) => {
                if (!old) return old;
                return {
                ...old,
                isFollowing: !old.isFollowing,
                };
            });

            return { previous };
            },
        onError: (err, userId, context) => {
            queryClient.setQueryData(
                ["is-being-followed", userId],
                context?.previous
            );
            handleApiError(err , showToast);
        },
        onSuccess: (_, userId) => {
        if (!userId) return;

        queryClient.invalidateQueries({
            queryKey: ["is-being-followed", userId],
        });

        queryClient.invalidateQueries({
            queryKey: ["profile", userId],
        });
        
        showToast("Followed user successfully" , ToastMessage.SUCCESS)
        },
    })
}