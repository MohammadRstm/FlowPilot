import { useQuery } from "@tanstack/react-query";
import type { CopilotHistory } from "../../../api/copilot/types";
import { fetchCopilotHistories } from "../../../api/copilot/fetchCopilotHistories";

export const useCopilotHistoriesQuery = () => {
  return useQuery<CopilotHistory[]>({
    queryKey: ["copilot-histories"],
    queryFn: fetchCopilotHistories,
  });
};
