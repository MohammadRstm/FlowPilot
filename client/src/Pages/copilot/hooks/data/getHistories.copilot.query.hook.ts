import { useQuery } from "@tanstack/react-query";
import { api } from "../../../../api/client";
import type { CopilotHistoriesResponse, CopilotHistory } from "./types";


export const useCopilotHistoriesQuery = () => {
  return useQuery<CopilotHistory[]>({
    queryKey: ["copilot-histories"],
    queryFn: fetchCopilotHistories,
  });
};

const fetchCopilotHistories = async (): Promise<CopilotHistory[]> => {
    const response = await api.get<CopilotHistoriesResponse>(`auth/copilot/histories`);
    return response.data.data.histories;
};
