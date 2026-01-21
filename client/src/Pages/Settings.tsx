import { useState } from "react";
import { FiArrowLeft, FiLock, FiLink, FiLogOut } from "react-icons/fi";
import "../styles/Settings.css";

type Tab = "password" | "n8n" | "logout";

const SettingsPage = () => {
  const [activeTab, setActiveTab] = useState<Tab>("password");

  return (
    <div className="settings-page">
      {/* Back button */}
      <button className="settings-back-btn" onClick={() => window.history.back()}>
        <FiArrowLeft size={18} />
        <span>Back</span>
      </button>

      <div className="settings-layout">
        {/* Sidebar */}
        <aside className="settings-sidebar">
          <button
            className={`settings-tab ${activeTab === "password" ? "active" : ""}`}
            onClick={() => setActiveTab("password")}
          >
            <FiLock />
            Set Password
          </button>

          <button
            className={`settings-tab ${activeTab === "n8n" ? "active" : ""}`}
            onClick={() => setActiveTab("n8n")}
          >
            <FiLink />
            Link n8n Account
          </button>

          <button
            className={`settings-tab logout ${activeTab === "logout" ? "active" : ""}`}
            onClick={() => setActiveTab("logout")}
          >
            <FiLogOut />
            Logout
          </button>
        </aside>

        {/* Content */}
        <main className="settings-content">
          {activeTab === "password" && (
            <section>
              <h2>Set Password</h2>
              <p>Update your account password.</p>

              <div className="settings-form">
                <input type="password" placeholder="Current password" />
                <input type="password" placeholder="New password" />
                <input type="password" placeholder="Confirm new password" />

                <button className="primary-btn">Update Password</button>
              </div>
            </section>
          )}

          {activeTab === "n8n" && (
            <section>
              <h2>Link n8n Account</h2>
              <p>Connect your n8n instance to enable workflow syncing.</p>

              <div className="settings-form">
                <input type="text" placeholder="n8n Base URL" />
                <input type="text" placeholder="API Key" />

                <button className="primary-btn">Link Account</button>
              </div>
            </section>
          )}

          {activeTab === "logout" && (
            <section>
              <h2>Logout</h2>
              <p>You will be logged out from this device.</p>

              <button className="danger-btn">Logout</button>
            </section>
          )}
        </main>
      </div>
    </div>
  );
};

export default SettingsPage;
