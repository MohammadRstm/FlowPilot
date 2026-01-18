import { api } from "../client";


export const downloadHistoryRequest = async (url: string) => {
  const res = await api.get("auth/" + url, {
    responseType: "blob",
  });

  return res.data;
};
