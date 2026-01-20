import { api } from "../client";


export const downloadHistoryRequest = async (url: string) => {
  const res = await api.get("auth/profile" + url, {
    responseType: "blob",
  });

  return res.data;
};
