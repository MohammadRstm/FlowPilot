import React, { useState } from "react";
import { useSearchForFriends } from "../hook/useSearchFriends";

type Props = {
  isOpen: boolean;
  onClose: () => void;
};

const FriendsModal: React.FC<Props> = ({ isOpen, onClose }) => {
  const [search, setSearch] = useState("");

  const {
    data: suggestions = [],
    isLoading,
  } = useSearchForFriends(search);

  if (!isOpen) return null;

  return (
    <div className="comments-modal-overlay" onClick={onClose}>
      <div className="friends-modal" onClick={(e) => e.stopPropagation()}>
        <h3>Find Friends</h3>

        <input
          className="friends-search"
          placeholder="Search users..."
          value={search}
          onChange={(e) => setSearch(e.target.value)}
        />

        <div className="friends-list">
          {/* Loading */}
          {isLoading && <div className="loading">Searching...</div>}

          {/* Results */}
          {suggestions.map((u: any) => (
            <div key={u.id} className="friend-item">
              <div className="friend-avatar">
                {u.photo_url ? (
                  <img src={`${import.meta.env.VITE_PHOTO_BASE_URL}/${u.photo_url}`} />
                ) : (
                  u.full_name?.[0]
                )}
              </div>

              <div className="friend-meta">
                <div className="friend-name">{u.full_name}</div>
              </div>
            </div>
          ))}

          {/* Empty state */}
          {!isLoading && suggestions.length === 0 && search !== "" && (
            <div className="empty">No users found</div>
          )}
        </div>
      </div>
    </div>
  );
};

export default FriendsModal;
