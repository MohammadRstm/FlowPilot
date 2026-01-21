import { useMutation, useQueryClient } from "@tanstack/react-query";
import { unlinkGoogle } from "../../../api/settings/unlinkGoogleAccount";
import { ToastMessage } from "../../components/toast/toast.types";
import { useToast } from "../../../context/toastContext";

export const useUnlinkGoogleAccount = () => {
  const qc = useQueryClient();
  const { showToast } = useToast();

  return useMutation({
    mutationFn: unlinkGoogle,
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ["user-account-type"] });
      qc.invalidateQueries({ queryKey: ["profile"] });
      showToast("Linked n8n account successfully" , ToastMessage.SUCCESS)
    },
    
  });
};
