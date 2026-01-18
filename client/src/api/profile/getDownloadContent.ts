import { api } from "../client";


export const downloadHistoryRequest = async (url: string) => {
  const res = await api.get(url, {
    responseType: "blob",
  });

  return res.data;
};
