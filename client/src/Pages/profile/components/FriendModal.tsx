import React, { useState } from "react";
import { useNavigate } from "react-router-dom";
import { Search } from "lucide-react";
import { useSearchFriendsMutation } from "../hook/useSearchFriends";

type Props = {
  isOpen: boolean;
  onClose: () => void;
};

const FriendsModal: React.FC<Props> = ({ isOpen, onClose }) => {
  const [search, setSearch] = useState("");
  const [suggestions, setSuggestions] = useState<any[]>([]);

  const navigate = useNavigate();
  const searchMutation = useSearchFriendsMutation();

  const handleSubmit = () => {
    if (!search.trim()) return;

    searchMutation.mutate(search, {
      onSuccess: (data) => {
        setSuggestions(data);
      },
    });
  };

  if (!isOpen) return null;

  return (
    <div className="comments-modal-overlay" onClick={onClose}>
      <div className="friends-modal" onClick={(e) => e.stopPropagation()}>
        <h3>Find Friends</h3>

        {/* Search Bar */}
        <div className="friends-search-wrapper">
          <input
            className="friends-search"
            placeholder="Search users..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            onKeyDown={(e) => e.key === "Enter" && handleSubmit()}
          />

          <button
            className="friends-search-btn"
            onClick={handleSubmit}
            disabled={searchMutation.isPending}
            aria-label="Search"
          >
            <Search size={18} />
          </button>
        </div>

        <div className="friends-list">
          {searchMutation.isPending && (
            <div className="loading">Searching...</div>
          )}

          {suggestions.map((u: any) => (
            <div
              key={u.id}
              className="friend-item clickable"
              onClick={() => navigate(`/profile/${u.id}`)}
            >
              <div className="friend-avatar">
                {u.photo_url ? (
                  <img
                    src={`${import.meta.env.VITE_PHOTO_BASE_URL}/${u.photo_url}`}
                    alt={u.full_name}
                  />
                ) : (
                  u.full_name?.[0]
                )}
              </div>

              <div className="friend-meta">
                <div className="friend-name">{u.full_name}</div>
              </div>
            </div>
          ))}

          {!searchMutation.isPending &&
            suggestions.length === 0 &&
            search === "" &&(
              <div className="empty">No users found</div>
            )}
        </div>
      </div>
    </div>
  );
};

export default FriendsModal;
