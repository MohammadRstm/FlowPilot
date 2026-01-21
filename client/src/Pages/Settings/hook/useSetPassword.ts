import { useMutation } from "@tanstack/react-query";
import { setPassword } from "../../../api/settings/setPassword";

export const useSetPassword = () => {
  return useMutation({
    mutationFn: setPassword,
  });
};
