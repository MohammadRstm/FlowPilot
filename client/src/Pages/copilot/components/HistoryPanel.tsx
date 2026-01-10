// copilot/components/HistoryPanel.tsx
import type { CopilotHistory } from "../../../api/copilot.api";

interface HistoryPanelProps {
  histories?: CopilotHistory[];
  currentHistoryId: number | null;
  onSelect: (history: CopilotHistory) => void;
  onDelete: (id: number) => void;
  onNewChat: () => void;
}

export function HistoryPanel({
  histories,
  currentHistoryId,
  onSelect,
  onDelete,
  onNewChat,
}: HistoryPanelProps) {
  return (
    <aside className="history-panel">
      <div className="history-header">
        <h2>Histories</h2>
        <button type="button" className="new-chat-button" onClick={onNewChat}>
          New chat
        </button>
      </div>

      <div className="history-list">
        {histories?.map((history) => (
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
              onClick={(e) => {
                e.stopPropagation();
                onDelete(history.id);
              }}
            >
              🗑
            </span>
          </button>
        ))}

        {(!histories || histories.length === 0) && (
          <p className="history-empty">No histories yet.</p>
        )}
      </div>
    </aside>
  );
}
