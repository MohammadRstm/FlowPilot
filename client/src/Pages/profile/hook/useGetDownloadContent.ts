import { useMutation } from "@tanstack/react-query"
import { api } from "../../../api/client";
import { useState } from "react";

export const useDownloadHistory = () => {
  const [downloadingId, setDownloadingId] = useState<string | number | null>(null);

  const mutation = useMutation({
    mutationFn: (data: { url: string; id: string | number }) => {
      setDownloadingId(data.id);
      return downloadHistoryRequest(data.url);
    },
    onSuccess: (blobData) => {
      createDownloadFile(blobData)
    },
    onSettled: () => {
      setDownloadingId(null);
    },
  });

  return {
    mutate: (url: string, id: string | number) => mutation.mutate({ url, id }),
    isPending: mutation.isPending,
    downloadingId,
  };
};

const downloadHistoryRequest = async (url: string) => {
  // Check if URL is absolute (contains http/https)
  const isAbsoluteUrl = url.startsWith('http://') || url.startsWith('https://');
  
  // If absolute URL, use it directly; otherwise, prepend the auth/profile path
  const endpoint = isAbsoluteUrl ? url : "auth/profile" + url;
  
  const res = await api.get(endpoint, {
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