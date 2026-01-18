import React from "react";
import Header from "../../components/Header";
import ProfileHeader from "./ProfileHeader";
import StatsCard from "./StatsCard";

type Props = {
  error: string;
  baseUser: any;
  initials: string;
  imgError: boolean;
  setImgError: (v: boolean) => void;
  isOwnProfile: boolean;
  followersCount: number;
  followingCount: number;
  totals: any;
  postsCount: number;
  tab: "posts" | "workflows";
  setTab: (t: "posts" | "workflows") => void;
  sortBy: "score" | "likes" | "comments" | "imports";
  setSortBy: (s: "score" | "likes" | "comments" | "imports") => void;
  onSettingsClick: () => void;
};

const ErrorPage: React.FC<Props> = ({
  error,
  baseUser,
  initials,
  imgError,
  setImgError,
  isOwnProfile,
  followersCount,
  followingCount,
  totals,
  postsCount,
  tab,
  setTab,
  sortBy,
  setSortBy,
  onSettingsClick,
}) => {
  return (
    <div className="profile-page">
      <Header />
      <main className="profile-main">
        <section className="profile-card wide-grid">
          <ProfileHeader
            baseUser={baseUser}
            initials={initials}
            imgError={imgError}
            setImgError={setImgError}
            isOwnProfile={isOwnProfile}
            followersCount={followersCount}
            followingCount={followingCount}
            onOpenModal={() => {}}
            onSettingsClick={onSettingsClick}
          />

          <StatsCard
            totals={totals}
            postsCount={postsCount}
            tab={tab}
            setTab={setTab}
            sortBy={sortBy}
            setSortBy={setSortBy}
          />

          <div
            className="profile-column content-column"
            style={{ gridArea: "content" }}
          >
            <div className="empty">{error}</div>
          </div>
        </section>
      </main>
    </div>
  );
};

export default ErrorPage;
