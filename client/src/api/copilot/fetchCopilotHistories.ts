import axios from "axios";
import { url, type CopilotHistoriesResponse, type CopilotHistory } from "./types";

export const fetchCopilotHistories = async (): Promise<CopilotHistory[]> => {
    const response = await axios.get<CopilotHistoriesResponse>(`${url}/histories`);
    return response.data.data.histories;
};
