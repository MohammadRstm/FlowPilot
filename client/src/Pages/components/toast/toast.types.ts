export type ToastType = "success" | "error" | "info";

export const ToastMessage = {
  SUCCESS: "success",
  INFO : "info",
  ERROR:"error"
} as const;

export type ToastMessage =
  typeof ToastMessage[keyof typeof ToastMessage];

export interface Toast {
  id: string;
  message: string;
  type: ToastType;
}
