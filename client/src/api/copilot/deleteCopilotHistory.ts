import { url } from "./types";
import { api } from "../client";

export const deleteCopilotHistory = async (id: number): Promise<void> => {
  await api.delete(`${url}/histories/${id}`);
};