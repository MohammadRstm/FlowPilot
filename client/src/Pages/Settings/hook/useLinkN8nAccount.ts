import { useMutation } from "@tanstack/react-query";
import { linkN8n } from "../../../api/settings/linkN8nAccount";
import { ToastMessage } from "../../components/toast/toast.types";
import { useToast } from "../../../context/toastContext";

export const useLinkN8nAccount = () => {
    const { showToast } = useToast();

  return useMutation({
    mutationFn: linkN8n,
    onSuccess:() => showToast("Linked n8n account successfully" , ToastMessage.SUCCESS)
  });
};
