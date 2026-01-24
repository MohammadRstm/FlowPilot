import { useMutation, useQueryClient } from "@tanstack/react-query";
import { api } from "../../../../api/client";

export const useDeleteCopilotHistoryMutation =() =>{
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (id: number) => deleteCopilotHistory(id),
    onSuccess: () =>{
      queryClient.invalidateQueries({ queryKey: ["copilot-histories"] });
    },
  });
};

const deleteCopilotHistory = async (id: number): Promise<void> => {
  await api.delete(`auth/copilot/histories/${id}`);
};