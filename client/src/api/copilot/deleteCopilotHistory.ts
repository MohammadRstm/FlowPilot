import axios from "axios";
import { url } from "./types";

export const deleteCopilotHistory = async (id: number): Promise<void> => {
  await axios.delete(`${url}/histories/${id}`);
};