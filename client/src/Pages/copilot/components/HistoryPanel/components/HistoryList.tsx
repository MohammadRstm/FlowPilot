import type { CopilotHistory } from "../../../../../api/copilot/types";
import { Spinner } from "../../../../components/Spinner";

interface Props {
  histories: CopilotHistory[];
  loading?: boolean;
  currentHistoryId: number | null;
  onSelect: (h: CopilotHistory) => void;
  onDelete: (id: number) => void;
}

export function HistoryList({
  histories,
  loading,
  currentHistoryId,
  onSelect,
  onDelete,
}: Props) {
  if (loading) {
    return (
      <div className="history-list">
        <div className="history-loading">
          <Spinner size={20} />
          <span>Loading histories…</span>
        </div>
      </div>
    );
  }

  return (
    <div className="history-list">
      {histories.map(history => (
        <button
          key={history.id}
          type="button"
          className={`history-item ${
            currentHistoryId === history.id ? "active" : ""
          }`}
          onClick={() => onSelect(history)}
        >
          <div className="history-text">
            <span className="history-title">
              {history.messages[0]?.user_message?.slice(0, 40) ||
                `Chat #${history.id}`}
            </span>
            <span className="history-subtitle">
              {new Date(history.created_at).toLocaleString()}
            </span>
          </div>

          <span
            className="history-delete"
            onClick={e => {
              e.stopPropagation();
              onDelete(history.id);
            }}
          >
            🗑
          </span>
        </button>
      ))}

      {histories.length === 0 && (
        <p className="history-empty">No matching histories.</p>
      )}
    </div>
  );
}
