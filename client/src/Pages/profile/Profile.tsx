// src/pages/profile/ProfilePage.tsx
import React, { useContext, useMemo, useState } from "react";
import "../../styles/Profile.css";
import Header from "../components/Header";
import { AuthContext } from "../../context/AuthContext";
import { useParams, useNavigate } from "react-router-dom";
import type { UserLite } from "./types";
import ProfileHeader from "./components/ProfileHeader";
import StatsCard from "./components/StatsCard";
import PostsList from "./components/PostsList";
import FollowModal from "./components/FollowModal";
import { getCounts } from "./utils/postScoring";
import WorkflowsList from "./components/WorkflowList";
import LoadingPage from "./components/LoadingPage";
import ErrorPage from "./components/ErrorPage";
import { useProfileQuery } from "./hook/useFetchProfileDetails";
import { useDownloadHistory } from "./hook/useGetDownloadContent";
import { useFollowUser } from "./hook/useFollowUser";

const ProfilePage: React.FC = () => {
    const auth = useContext(AuthContext);
    const authUser = auth?.user;
    const { userId } = useParams<{ userId?: string }>();
    const navigate = useNavigate();

    const {
    data: profile,
    isLoading,
    isError,
    error,
    } = useProfileQuery(userId);

    
    const { mutate: downloadHistory, isLoading: isDownloading } =
    useDownloadHistory();

    const { mutate: followUser } = useFollowUser();


    const [modalType, setModalType] = useState<"followers" | "following" | null>(null);
    const [tab, setTab] = useState<"posts" | "workflows">("posts");
    const [sortBy, setSortBy] = useState<"score" | "likes" | "comments" | "imports">("likes");
    const [imgError, setImgError] = useState(false);

    const isOwnProfile = !userId || Number(userId) === authUser?.id;

    const baseUser = (profile?.user as any) ?? (authUser as any) ?? {};
    const initials = (
        (baseUser.first_name?.[0] ?? "") + (baseUser.last_name?.[0] ?? "")
    ).toUpperCase() || "U";

    const posts: any[] = profile?.posts?.items ?? [];
    const workflows: any[] = profile?.workflows?.items ?? [];

    const computedTotals = useMemo(() => {
        return posts.reduce(
        (acc: { likes: number; imports: number }, p: any) => {
            const { likes, imports } = getCounts(p);
            acc.likes += likes;
            acc.imports += imports;
            return acc;
        },
        { likes: 0, imports: 0 }
        );
    }, [posts]);

    const totals = profile?.totals ?? computedTotals;
    const followers = profile?.followers ?? [];
    const following = profile?.following ?? [];

    if (isLoading) {
        return <LoadingPage />
    }

    if (isError) {
        return (
            <ErrorPage
            error={(error as Error).message}
            baseUser={baseUser}
            initials={initials}
            imgError={imgError}
            setImgError={setImgError}
            isOwnProfile={isOwnProfile}
            followersCount={followers.length}
            followingCount={following.length}
            totals={totals}
            postsCount={posts.length}
            tab={tab}
            setTab={setTab}
            sortBy={sortBy}
            setSortBy={setSortBy}
            onSettingsClick={() => navigate("/settings")}
            />
        );

    }

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
            followersCount={followers.length}
            followingCount={following.length}
            onOpenModal={(t) => setModalType(t)}
            onSettingsClick={() => navigate("/settings")}
          />

          <StatsCard totals={totals as any} postsCount={posts.length} tab={tab} setTab={setTab} sortBy={sortBy} setSortBy={setSortBy} />

          <div className="profile-column content-column" style={{ gridArea: "content" }}>
            {tab === "posts" ? <PostsList posts={posts} sortBy={sortBy} /> : <WorkflowsList workflows={workflows} onDownload={(url : string) => downloadHistory(url)} isDownloading={isDownloading} />}
          </div>
        </section>
      </main>

      {modalType && (
        <FollowModal
          title={modalType === "followers" ? "Followers" : "Following"}
          users={modalType === "followers" ? followers : following}
          onClose={() => setModalType(null)}
          onUserClick={(u: UserLite) => {
            navigate(`/profile/${u.id}`);
            setModalType(null);
          }}
          followUser={followUser}
        />
      )}
    </div>
  );
};

export default ProfilePage;
