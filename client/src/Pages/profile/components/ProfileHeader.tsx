import React, { useRef } from "react";
import { FiSettings , FiCamera } from "react-icons/fi";
import type { UserLite } from "../types";
import { useUploadAvatar } from "../hook/useUploadAvatar";

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
  const fileInputRef = useRef<HTMLInputElement>(null);
  const uploadAvatar = useUploadAvatar();

  const handleAvatarClick = () => {
    if (fileInputRef.current) fileInputRef.current.click();
  };
  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.files && e.target.files[0]) {
      uploadAvatar.mutate(e.target.files[0]);
    }
  };
  return (
    <div className="profile-column profile-left" style={{ gridArea: "profile" }}>
      {isOwnProfile && (
        <button className="profile-settings-btn" onClick={onSettingsClick} aria-label="Settings">
          <FiSettings size={20} />
        </button>
      )}

      <div className="profile-head">
        <div className="profile-avatar-wrapper">
          <div className="profile-avatar">
            {baseUser.photo_url && !imgError ? (
              <img
                src={baseUser.photo_url}
                alt={fullName || "User avatar"}
                onError={() => setImgError(true)}
              />
            ) : (
              <span className="profile-initials">{initials}</span>
            )}
            {isOwnProfile && (
              <>
                <button className="avatar-camera-btn" onClick={handleAvatarClick} title="Change avatar">
                  <FiCamera size={20} />
                </button>
                <input
                  type="file"
                  ref={fileInputRef}
                  style={{ display: "none" }}
                  accept="image/*"
                  onChange={handleFileChange}
                />
              </>
            )}
          </div>
        </div>

        <div className="profile-head-info">
          <div className="profile-basic left">
            <h2 className="profile-name">{fullName || "Your Profile"}</h2>
            <p className="profile-email">{baseUser.email}</p>
          </div>
        </div>
      </div>

      {/* Follow counts */}
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

      {/* Follow/Unfollow buttons */}
      {!isOwnProfile && isBeingFollowed && (
        <div className="profile-follow-cta">
          {!isBeingFollowed.isFollowing && (
            <button
              className={`follow-cta-btn ${isBeingFollowed.isBeingFollowed ? "secondary" : ""}`}
              onClick={() => followUser(userId)}
            >
              {isBeingFollowed.isBeingFollowed ? "Follow Back" : "Follow"}
            </button>
          )}
          {isBeingFollowed.isFollowing && (
            <button onClick={() => followUser(userId)} className="follow-cta-btn">
              Unfollow
            </button>
          )}
        </div>
      )}
    </div>
  );
};

export default ProfileHeader;
