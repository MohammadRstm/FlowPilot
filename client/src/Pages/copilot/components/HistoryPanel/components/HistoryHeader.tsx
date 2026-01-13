interface Props {
  searchOpen: boolean;
  toggleSearch: () => void;
  onNewChat: () => void;
}

export function HistoryHeader({ toggleSearch, onNewChat }: Props) {
  return (
    <div className="history-header">
      <div className="history-title-row">
        <h2>Histories</h2>

        <button
          type="button"
          className="history-search-button"
          onClick={toggleSearch}
          aria-label="Search histories"
        >
          🔍
        </button>
      </div>

      <button type="button" className="new-chat-button" onClick={onNewChat}>
        New chat
      </button>
    </div>
  );
}
