import { useMutation, useQueryClient } from "@tanstack/react-query";
import { ToastMessage } from "../../components/toast/toast.types";
import { useToast } from "../../../context/toastContext";
import { returnDataFormat } from "../../utils/returnApiDataFormat";
import { api } from "../../../api/client";

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


const unlinkGoogle = async () => {
  const resp = await api.put("auth/unlinkGoogleAccount");
  return returnDataFormat(resp);
};

