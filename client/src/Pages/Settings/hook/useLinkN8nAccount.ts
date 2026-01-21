import { useMutation } from "@tanstack/react-query";
import { ToastMessage } from "../../components/toast/toast.types";
import { useToast } from "../../../context/toastContext";
import { returnDataFormat } from "../../utils/returnApiDataFormat";
import { api } from "../../../api/client";
import type { N8nLinkPayload } from "../types";

export const useLinkN8nAccount = () => {
    const { showToast } = useToast();

  return useMutation({
    mutationFn: linkN8n,
    onSuccess:() => showToast("Linked n8n account successfully" , ToastMessage.SUCCESS)
  });
};


const linkN8n = async (payload: N8nLinkPayload ) => {
  const resp = await api.post("auth/linkN8nAccount", payload);
  return returnDataFormat(resp);
};
