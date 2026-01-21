import { useMutation } from "@tanstack/react-query"
import { downloadHistoryRequest } from "../../../api/profile/getDownloadContent";

export const useDownloadHistory = () => {
  return useMutation({
    mutationFn: downloadHistoryRequest,
    onSuccess: (blobData) => {
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
    },
  });
};