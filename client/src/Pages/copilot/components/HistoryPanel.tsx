import { useMemo, useState } from "react";
import type { CopilotHistory } from "../../../api/copilot.api";
import { Spinner } from "../../components/Spinner";

interface HistoryPanelProps {
  histories?: CopilotHistory[];
  loading? : boolean;
  currentHistoryId: number | null;
  onSelect: (history: CopilotHistory) => void;
  onDelete: (id: number) => void;
  onNewChat: () => void;
}

export function HistoryPanel({
  histories = [],
  loading,
  currentHistoryId,
  onSelect,
  onDelete,
  onNewChat,
}: HistoryPanelProps) {
  const [searchOpen, setSearchOpen] = useState(false);
  const [query, setQuery] = useState("");


  const normalizedQuery = query.trim().toLowerCase();

  const filteredHistories = useMemo(() => {
    if (!normalizedQuery) return histories;

    return [...histories]
      .map((history) => {
        const title =
          history.messages[0]?.user_message?.toLowerCase() ??
          `chat ${history.id}`;

        let score = 0;

        if (title.includes(normalizedQuery)) score += 3;
        if (title.startsWith(normalizedQuery)) score += 2;

        // simple fuzzy-ish scoring
        normalizedQuery.split(" ").forEach((word) => {
          if (title.includes(word)) score += 1;
        });

        return { history, score };
      })
      .filter((h) => h.score > 0)
      .sort((a, b) => b.score - a.score)
      .map((h) => h.history);
  }, [histories, normalizedQuery]);

  const listToRender =
    normalizedQuery.length > 0 ? filteredHistories : histories;

  return (
    <aside className="history-panel">
      <div className="history-header">
        <div className="history-title-row">
          <h2>Histories</h2>

          <button
            type="button"
            className="history-search-button"
            onClick={() => setSearchOpen((v) => !v)}
            aria-label="Search histories"
          >
            🔍
          </button>
        </div>

        <button type="button" className="new-chat-button" onClick={onNewChat}>
          New chat
        </button>
      </div>

      {/* Search input */}
      <div
        className={`history-search-wrapper ${
          searchOpen ? "open" : ""
        }`}
      >
        <button
          className="search-cancel"
          onClick={() => {
            setQuery("");
            setSearchOpen(false);
          }}
          aria-label="Cancel search"
        >
          ✕
        </button>

        <input
          type="text"
          placeholder="Search histories…"
          value={query}
          onChange={(e) => setQuery(e.target.value)}
          autoFocus={searchOpen}
        />
      </div>

      <div className="history-list">
        {loading ? (
            <div className="history-loading">
                <Spinner size={20} />
                <span>Loading histories…</span>
            </div>
        ): (
            <>
            {listToRender.map((history) => (
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

            {listToRender.length === 0 && (
            <p className="history-empty">No matching histories.</p>
            )}
            </>
        )}
      </div>
    </aside>
  );
}
