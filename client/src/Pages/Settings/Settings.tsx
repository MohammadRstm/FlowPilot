import { useContext, useState } from "react";
import {
  FiArrowLeft,
  FiLock,
  FiLink,
  FiLogOut,
  FiXCircle,
} from "react-icons/fi";
import "../../styles/Settings.css";
import { useUserAccount } from "./hook/useFetchAccountType";
import { AuthContext } from "../../context/AuthContext";
import { useSetPassword } from "./hook/useSetPassword";
import { useLinkN8nAccount } from "./hook/useLinkN8nAccount";
import { useUnlinkGoogleAccount } from "./hook/useUnlinkGoogleAccount";

type Tab = "password" | "n8n" | "unlink-google" | "logout";

const SettingsPage = () => {
  const [activeTab, setActiveTab] = useState<Tab>("password");

  const { data, isLoading } = useUserAccount();
  const { logout } = useContext(AuthContext);

  const [currentPassword, setCurrentPassword] = useState("");
  const [newPassword, setNewPassword] = useState("");
  const [confirmPassword, setConfirmPassword] = useState("");

  const [n8nBaseUrl, setN8nBaseUrl] = useState("");
  const [n8nApiKey, setN8nApiKey] = useState("");

  const setPasswordMutation = useSetPassword();
  const linkN8nMutation = useLinkN8nAccount();
  const unlinkGoogleMutation = useUnlinkGoogleAccount();

  if (isLoading) return null;

  const hasPassword = data?.normalAccount;
  const hasGoogle = data?.googleAccount;


  const handleSetPassword = () => {
    if (newPassword !== confirmPassword) return;

    setPasswordMutation.mutate({
      current_password: hasPassword ? currentPassword : undefined,
      new_password: newPassword,
      new_password_confirmation: confirmPassword,
    });
  };

  const handleLinkN8n = () => {
    if (!n8nBaseUrl || !n8nApiKey) return;

    linkN8nMutation.mutate({
      base_url: n8nBaseUrl,
      api_key: n8nApiKey,
    });
  };

  const handleUnlinkGoogle = () => {
    unlinkGoogleMutation.mutate();
  };


  return (
    <div className="settings-page">
      <button className="settings-back-btn" onClick={() => history.back()}>
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

          {hasGoogle && (
            <button
              className={`settings-tab ${
                activeTab === "unlink-google" ? "active" : ""
              }`}
              onClick={() => setActiveTab("unlink-google")}
            >
              <FiXCircle />
              Unlink Google
            </button>
          )}

          <button
            className={`settings-tab logout ${
              activeTab === "logout" ? "active" : ""
            }`}
            onClick={() => setActiveTab("logout")}
          >
            <FiLogOut />
            Logout
          </button>
        </aside>

        {/* Content */}
        <main className="settings-content">
          {/* SET PASSWORD */}
          {activeTab === "password" && (
            <section>
              <h2>Set Password</h2>

              {!hasPassword && (
                <p>
                  You signed up using Google. Set a password to enable email
                  login.
                </p>
              )}

              <div className="settings-form">
                {hasPassword && (
                  <input
                    type="password"
                    placeholder="Current password"
                    value={currentPassword}
                    onChange={(e) => setCurrentPassword(e.target.value)}
                  />
                )}

                <input
                  type="password"
                  placeholder="New password"
                  value={newPassword}
                  onChange={(e) => setNewPassword(e.target.value)}
                />

                <input
                  type="password"
                  placeholder="Confirm new password"
                  value={confirmPassword}
                  onChange={(e) => setConfirmPassword(e.target.value)}
                />

                <button
                  className="primary-btn"
                  onClick={handleSetPassword}
                  disabled={setPasswordMutation.isLoading}
                >
                  {setPasswordMutation.isLoading
                    ? "Saving..."
                    : hasPassword
                    ? "Update Password"
                    : "Set Password"}
                </button>
              </div>
            </section>
          )}

          {/* N8N */}
          {activeTab === "n8n" && (
            <section>
              <h2>Link n8n Account</h2>
              <p>Connect your n8n instance.</p>

              <div className="settings-form">
                <input
                  type="text"
                  placeholder="n8n Base URL"
                  value={n8nBaseUrl}
                  onChange={(e) => setN8nBaseUrl(e.target.value)}
                />
                <input
                  type="text"
                  placeholder="API Key"
                  value={n8nApiKey}
                  onChange={(e) => setN8nApiKey(e.target.value)}
                />

                <button
                  className="primary-btn"
                  onClick={handleLinkN8n}
                  disabled={linkN8nMutation.isLoading}
                >
                  {linkN8nMutation.isLoading ? "Connecting..." : "Link Account"}
                </button>
              </div>
            </section>
          )}

          {/* UNLINK GOOGLE */}
          {activeTab === "unlink-google" && (
            <section>
              <h2>Unlink Google Account</h2>
              <p>
                You will no longer be able to log in using Google.
                {!hasPassword && " Make sure to set a password first."}
              </p>

              <button
                className="danger-btn"
                disabled={!hasPassword || unlinkGoogleMutation.isLoading}
                onClick={handleUnlinkGoogle}
              >
                {unlinkGoogleMutation.isLoading
                  ? "Unlinking..."
                  : "Unlink Google Account"}
              </button>
            </section>
          )}

          {/* LOGOUT */}
          {activeTab === "logout" && (
            <section>
              <h2>Logout</h2>
              <p>You will be logged out from this device.</p>
              <button onClick={logout} className="danger-btn">
                Logout
              </button>
            </section>
          )}
        </main>
      </div>
    </div>
  );
};

export default SettingsPage;
