import "./toast.css";
import type { Toast } from "./toast.types";

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
