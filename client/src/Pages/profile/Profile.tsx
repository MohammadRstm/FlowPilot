import React, { useContext, useMemo, useState } from "react";
import "./profile.css";
import Header from "../components/Header";
import { AuthContext } from "../../context/AuthContext";
import { useParams, useNavigate } from "react-router-dom";
import { ModalType, SortType, TabType, type UserLite } from "./types";
import ProfileHeader from "./components/ProfileHeader";
import StatsCard from "./components/StatsCard";
import PostsList from "./components/PostsList";
import FollowModal from "./components/FollowModal";
import { getCounts } from "./utils/postScoring";
import WorkflowsList from "./components/WorkflowList";
import LoadingPage from "./components/LoadingPage";
import { useProfileQuery } from "./hook/useFetchProfileDetails";
import { useDownloadHistory } from "./hook/useGetDownloadContent";
import { useFollowUser } from "./hook/useFollowUser";
import { useIsBeingFollowedByUser } from "./hook/useIsFollowedByUser";

const ProfilePage: React.FC = () => {
    const auth = useContext(AuthContext);
    const authUser = auth?.user;
    const { userId } = useParams<{ userId?: string }>();
    const navigate = useNavigate();

    const {
    data: profile,
    isPending,
    } = useProfileQuery(userId);

    const { mutate: downloadHistory, isPending: isDownloading, downloadingId } =
    useDownloadHistory();

    const { mutate: followUser } = useFollowUser();

    const [modalType, setModalType] = useState<ModalType | null>(null);
    const [tab, setTab] = useState<TabType>(TabType.POSTS);
    const [sortBy, setSortBy] = useState<SortType>(SortType.LIKES);
    const [imgError, setImgError] = useState(false);

    const isOwnProfile = !userId || Number(userId) === authUser?.id;

    const {data : isBeingFollowed}  =  useIsBeingFollowedByUser(
    isOwnProfile ? undefined : Number(userId)
    );

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

    if (isPending) {
        return <LoadingPage />
    }

  return (
    <div className="profile-page">
      <Header />
      <main className="profile-main">
        <section className="profile-card wide-grid">
          <ProfileHeader
            userId={Number(userId)}
            baseUser={baseUser}
            initials={initials}
            imgError={imgError}
            setImgError={setImgError}
            isOwnProfile={isOwnProfile}
            followersCount={followers.length}
            followingCount={following.length}
            isBeingFollowed={isBeingFollowed}
            followUser={followUser}
            onOpenModal={(t) => setModalType(t)}
            onSettingsClick={() => navigate("/settings")}
          />

          <StatsCard isOwnProfile={isOwnProfile} isBeingFollowed={isBeingFollowed} totals={totals as any} postsCount={posts.length} tab={tab} setTab={setTab} sortBy={sortBy} setSortBy={setSortBy} />

          <div className="profile-column content-column" style={{ gridArea: "content" }}>
            {tab === "posts" ? (
                <PostsList posts={posts} sortBy={sortBy} userId={userId} />
            ) : (
                isOwnProfile ? (
                <WorkflowsList
                    workflows={workflows}
                    onDownload={(url: string, id: string | number) => downloadHistory(url, id)}
                    isDownloading={isDownloading}
                    downloadingId={downloadingId}
                />
                ) : (
                    isBeingFollowed?.isFollowing && (
                        <WorkflowsList
                            workflows={workflows}
                            onDownload={(url: string, id: string | number) => downloadHistory(url, id)}
                            isDownloading={isDownloading}
                            downloadingId={downloadingId}
                        />
                    )
                )

            )}
            </div>

        </section>
      </main>

      {modalType && (
        <FollowModal
          title={modalType === ModalType.FOLLOWERS ? ModalType.FOLLOWERS: ModalType.FOLLOWING}
          users={modalType === ModalType.FOLLOWERS ? followers : following}
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
