import { useMutation } from "@tanstack/react-query";
import { linkN8n } from "../../../api/settings/linkN8nAccount";

export const useLinkN8nAccount = () => {
  return useMutation({
    mutationFn: linkN8n,
  });
};
