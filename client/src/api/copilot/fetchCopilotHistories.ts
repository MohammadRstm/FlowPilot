import { url, type CopilotHistoriesResponse, type CopilotHistory } from "./types";
import { api } from "../client";

export const fetchCopilotHistories = async (): Promise<CopilotHistory[]> => {
    const response = await api.get<CopilotHistoriesResponse>(`${url}/histories`);
    return response.data.data.histories;
};
