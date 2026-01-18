// src/pages/profile/components/FollowModal.tsx
import React, { useMemo, useState } from "react";
import type { UserLite } from "../types";

type FollowModalProps = {
  title: string;
  users: UserLite[];
  onClose: () => void;
  onUserClick: (user: UserLite) => void;
};

const FollowModal: React.FC<FollowModalProps> = ({ title, users, onClose, onUserClick }) => {
  const [query, setQuery] = useState("");
  const normalizedQuery = query.trim().toLowerCase();

  const scoredUsers = useMemo(() => {
    if (!normalizedQuery) return users;
    return [...users].sort((a, b) => {
      const score = (name: string) => {
        const n = name.toLowerCase();
        if (n.startsWith(normalizedQuery)) return 4;
        if (n.split(" ").some((w) => w.startsWith(normalizedQuery))) return 3;
        if (n.includes(normalizedQuery)) return 2;
        return 1;
      };
      return score(b.full_name) - score(a.full_name);
    });
  }, [users, normalizedQuery]);

  const isFollowingModal = title.toLowerCase().includes("following");

  const handleFollowBack = (e: React.MouseEvent, user: any) => {
    e.stopPropagation();
    // TODO: implement follow-back API
    console.log("Follow back clicked:", user);
  };

  return (
    <div className="modal-backdrop" onClick={onClose}>
      <div className="modal-card" onClick={(e) => e.stopPropagation()}>
        <div className="modal-header">
          <h3>{title}</h3>
          <button className="modal-close" onClick={onClose}>
            ✕
          </button>
        </div>

        <div className="modal-body">
          <div className="modal-search">
            <input type="text" placeholder="Search by name…" value={query} onChange={(e) => setQuery(e.target.value)} />
          </div>

          {scoredUsers.length === 0 ? (
            <div className="empty">No users found.</div>
          ) : (
            scoredUsers.map((u) => (
              <div key={u.id} className="follow-row" onClick={() => onUserClick(u)}>
                <div className="follow-left">
                  <div className="follow-avatar">{u.photo_url ? <img src={u.photo_url} alt={u.full_name} /> : <span>{u.full_name[0]}</span>}</div>

                  <div className="follow-info">
                    <div className="follow-name">{u.full_name}</div>
                    <div className="follow-email">{u.email ?? "—"}</div>
                  </div>
                </div>

                {isFollowingModal && (
                  <button className="follow-back-btn" onClick={(e) => handleFollowBack(e, u)}>
                    + Follow back
                  </button>
                )}
              </div>
            ))
          )}
        </div>
      </div>
    </div>
  );
};

export default FollowModal;
