import { useMutation, useQueryClient } from "@tanstack/react-query";
import { deleteCopilotHistory } from "../../../api/copilot.api";

export const useDeleteCopilotHistoryMutation = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (id: number) => deleteCopilotHistory(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["copilot-histories"] });
    },
  });
};
