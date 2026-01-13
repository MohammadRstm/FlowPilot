interface Props {
  open: boolean;
  query: string;
  onChange: (v: string) => void;
  onClose: () => void;
}

export function HistorySearch({ open, query, onChange, onClose }: Props) {
  return (
    <div className={`history-search-wrapper ${open ? "open" : ""}`}>
      <button className="search-cancel" onClick={onClose} aria-label="Cancel search">
        ✕
      </button>

      <input
        type="text"
        placeholder="Search histories…"
        value={query}
        onChange={e => onChange(e.target.value)}
        autoFocus={open}
      />
    </div>
  );
}
