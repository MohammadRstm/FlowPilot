import type { AxiosError } from "axios";
import type { ToastType } from "../components/toast/toast.types";

export const handleApiError = (
  error: unknown,
  showToast: (message: string, type?: ToastType) => void
) => {
  const err = error as AxiosError<any>;

  const apiMessage = err?.response?.data?.success === false
    ? err.response.data.message
    : null;

  showToast(
    apiMessage ?? "Something went wrong. Please try again.",
    "error"
  );
};
