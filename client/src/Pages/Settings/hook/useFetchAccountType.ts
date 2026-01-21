import { useQuery } from "@tanstack/react-query";
import { getUserAccount } from "../../../api/settings/fetchAccountType";

export const useUserAccount = () => {
  return useQuery({
    queryKey: ["user-account-type"],
    queryFn: getUserAccount,
    staleTime: 5 * 60 * 1000, 
  });
};
