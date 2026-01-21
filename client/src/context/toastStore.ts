type ToastFn = (message: string, type?: any) => void;

let toastFn: ToastFn | null = null;

export const toastStore = {
  register(fn: ToastFn) {
    toastFn = fn;
  },
  show(message: string, type?: any) {
    if (!toastFn) return;
    toastFn(message, type);
  },
};
