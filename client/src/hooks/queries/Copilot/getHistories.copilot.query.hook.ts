import { useQuery } from "@tanstack/react-query";
import { fetchCopilotHistories } from "../../../api/copilot.api";
import type { CopilotHistory } from "../../../api/copilot.api";

export const useCopilotHistoriesQuery = () => {
  return useQuery<CopilotHistory[]>({
    queryKey: ["copilot-histories"],
    queryFn: fetchCopilotHistories,
  });
};
