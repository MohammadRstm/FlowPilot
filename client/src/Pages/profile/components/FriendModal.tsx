import React, { useState } from "react";

type Props = {
  isOpen: boolean;
  onClose: () => void;
};

const mockUsers = [
  { id: 1, name: "John Doe", username: "@john", avatar: "" },
  { id: 2, name: "Jane Smith", username: "@jane", avatar: "" },
  { id: 3, name: "Alex Hydra", username: "@hydra", avatar: "" },
];

const FriendsModal: React.FC<Props> = ({ isOpen, onClose }) => {
  const [search, setSearch] = useState("");

  if (!isOpen) return null;

  const filtered = mockUsers.filter(
    (u) =>
      u.name.toLowerCase().includes(search.toLowerCase()) ||
      u.username.toLowerCase().includes(search.toLowerCase())
  );

  return (
    <div className="comments-modal-overlay" onClick={onClose}>
      <div className="friends-modal" onClick={(e) => e.stopPropagation()}>
        <h3>Friends</h3>

        <input
          className="friends-search"
          placeholder="Search users..."
          value={search}
          onChange={(e) => setSearch(e.target.value)}
        />

        <div className="friends-list">
          {filtered.map((u) => (
            <div key={u.id} className="friend-item">
              <div className="friend-avatar">
                {u.avatar ? <img src={u.avatar} /> : u.name[0]}
              </div>

              <div className="friend-meta">
                <div className="friend-name">{u.name}</div>
                <div className="friend-username">{u.username}</div>
              </div>
            </div>
          ))}

          {filtered.length === 0 && (
            <div className="empty">No users found</div>
          )}
        </div>
      </div>
    </div>
  );
};

export default FriendsModal;
