import { useMutation, useQueryClient } from "@tanstack/react-query"
import { useToast } from "../../../context/toastContext";
import { ToastMessage } from "../../components/toast/toast.types";
import { handleApiError } from "../../utils/handleErrorMessage";
import { returnDataFormat } from "../../utils/returnApiDataFormat";
import { api } from "../../../api/client";
import type { FollowUserParam } from "../types";


export const useFollowUser = () =>{
    const queryClient = useQueryClient();
    const { showToast } = useToast();
    return useMutation({
        mutationFn: (param : FollowUserParam) => followUser(param),
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

const followUser = async (param: FollowUserParam) =>{
    const res = api.post(`auth/profile/follow/${param}`);

    return returnDataFormat(res);
}