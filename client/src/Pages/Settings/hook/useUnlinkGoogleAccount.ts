import { useMutation, useQueryClient } from "@tanstack/react-query";
import { unlinkGoogle } from "../../../api/settings/unlinkGoogleAccount";

export const useUnlinkGoogleAccount = () => {
  const qc = useQueryClient();

  return useMutation({
    mutationFn: unlinkGoogle,
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ["user-account-type"] });
      qc.invalidateQueries({ queryKey: ["profile"] });
    },
  });
};
