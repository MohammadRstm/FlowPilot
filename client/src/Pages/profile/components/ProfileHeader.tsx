import React from "react";
import { FiSettings } from "react-icons/fi";
import type { UserLite } from "../types";

type Props = {
  userId: number | undefined;
  baseUser: Partial<UserLite> & { first_name?: string; last_name?: string };
  initials: string;
  imgError: boolean;
  setImgError: (v: boolean) => void;
  isOwnProfile: boolean;
  followersCount: number;
  followingCount: number;
  isBeingFollowed:  {
  isFollowing: boolean;
  isBeingFollowed: boolean;
} | undefined;
  followUser: (userId: number| undefined) => void; 
  onOpenModal: (type: "followers" | "following") => void;
  onSettingsClick: () => void;
};

const ProfileHeader: React.FC<Props> = ({
  userId,
  baseUser,
  initials,
  imgError,
  setImgError,
  isOwnProfile,
  followersCount,
  followingCount,
  isBeingFollowed,
  followUser,
  onOpenModal,
  onSettingsClick,
}) => {
  const fullName = `${baseUser.first_name ?? ""} ${baseUser.last_name ?? ""}`.trim();
  return (
    <div className="profile-column profile-left" style={{ gridArea: "profile" }}>
      {isOwnProfile && (
        <button className="profile-settings-btn" onClick={onSettingsClick} aria-label="Settings">
          <FiSettings size={20} />
        </button>
      )}

      <div className="profile-head">
        <div className="profile-avatar">
          {baseUser.photo_url && !imgError ? (
            <img src={baseUser.photo_url} alt={fullName || "User avatar"} onError={() => setImgError(true)} />
          ) : (
            <span className="profile-initials">{initials}</span>
          )}
        </div>

        <div className="profile-head-info">
          <div className="profile-basic left">
            <h2 className="profile-name">{fullName || "Your Profile"}</h2>
            <p className="profile-email">{baseUser.email}</p>
          </div>
        </div>
      </div>

      <div className="profile-follow-row">
        <button className="count-link" onClick={() => onOpenModal("followers")}>
          <strong>{followersCount}</strong>
          <span>Followers</span>
        </button>

        <button className="count-link" onClick={() => onOpenModal("following")}>
          <strong>{followingCount}</strong>
          <span>Following</span>
        </button>
      </div>
        {!isOwnProfile && isBeingFollowed && (
        <div className="profile-follow-cta">
            {!isBeingFollowed.isFollowing && (
            <button
                className={`follow-cta-btn ${
                isBeingFollowed.isBeingFollowed ? "secondary" : ""
                }`}
                onClick={() => followUser(userId)}
            >
                {isBeingFollowed.isBeingFollowed ? "Follow Back" : "Follow"}
            </button>
            )}

            {isBeingFollowed.isFollowing && (
            <button
                onClick={() => followUser(userId)}
                className="follow-cta-btn"
            >
                Unfollow
            </button>
            )}
        </div>
        )}
    </div>
  );
};

export default ProfileHeader;
