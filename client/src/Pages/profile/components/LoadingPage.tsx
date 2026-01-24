import React from "react";
import Header from "../../components/Header";

const LoadingPage: React.FC = () => {
  return (
    <div className="profile-page">
      <Header />
      <main className="profile-main">
        <div className="profile-card wide-grid">
          <div
            className="profile-column profile-left"
            style={{ gridArea: "profile" }}
          >
            <div className="profile-avatar-loading" />
            <div className="profile-basic">
              <div className="skeleton title" />
              <div className="skeleton subtitle" />
            </div>
          </div>

          <div
            className="profile-column stats-column"
            style={{ gridArea: "stats" }}
          >
            <div className="stats-card">
              <div className="stats-inner">
                <div className="stat-circle skeleton-circle" />
                <div className="stat-circle skeleton-circle" />
                <div className="stat-circle skeleton-circle" />
              </div>
            </div>
          </div>

          <div
            className="profile-column content-column"
            style={{ gridArea: "content" }}
          >
            <div className="empty">Loading profile…</div>
          </div>
        </div>
      </main>
    </div>
  );
};

export default LoadingPage;
