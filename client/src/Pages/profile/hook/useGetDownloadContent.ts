import { useMutation } from "@tanstack/react-query"
import { api } from "../../../api/client";

export const useDownloadHistory = () => {
  return useMutation({
    mutationFn: downloadHistoryRequest,
    onSuccess: (blobData) => {
      createDownloadFile(blobData)
    },
  });
};

const downloadHistoryRequest = async (url: string) => {
  const res = await api.get("auth/profile" + url, {
    responseType: "blob",
  });

  return res.data;
};

const createDownloadFile = (blobData : any) =>{
    const blob = new Blob([blobData], {
    type: "application/json",
    });

    const downloadUrl = URL.createObjectURL(blob);
    const a = document.createElement("a");

    a.href = downloadUrl;
    a.download = "history.json";
    document.body.appendChild(a);
    a.click();
    a.remove();

    URL.revokeObjectURL(downloadUrl);
}