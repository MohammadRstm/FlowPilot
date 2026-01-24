import { useQuery } from "@tanstack/react-query";
import { returnDataFormat } from "../../utils/returnApiDataFormat";
import { api } from "../../../api/client";
import type { UserAccountType } from "../types";

export const useUserAccount = () => {
    return useQuery({
        queryKey: ["user-account-type"],
        queryFn: getUserAccount,
        staleTime: 5 * 60 * 1000, 
    });
};

const getUserAccount = async (): Promise<UserAccountType> => {
  const resp = await api.get("auth/account");
  return returnDataFormat(resp);
};
