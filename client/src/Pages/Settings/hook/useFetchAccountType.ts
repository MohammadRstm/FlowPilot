import { useQuery } from "@tanstack/react-query";
import { getUserAccount } from "../../../api/settings/fetchAccountType";
import { handleApiError } from "../../utls/handleErrorMessage";
import { useToast } from "../../../context/toastContext";

export const useUserAccount = () => {
    const { showToast } = useToast();
    return useQuery({
        queryKey: ["user-account-type"],
        queryFn: getUserAccount,
        staleTime: 5 * 60 * 1000, 
        onError:(err:any) =>   handleApiError(err , showToast)

    });
};
