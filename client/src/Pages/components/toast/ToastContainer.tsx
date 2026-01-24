import type { Toast } from "./toast.types";
import "../../../styles/Toast.css";

export const ToastContainer = ({ toasts }: { toasts: Toast[] }) => {
  return (
    <div className="toast-container">
      {toasts.map((toast) => (
        <div key={toast.id} className={`toast toast-${toast.type}`}>
          {toast.message}
        </div>
      ))}
    </div>
  );
};
