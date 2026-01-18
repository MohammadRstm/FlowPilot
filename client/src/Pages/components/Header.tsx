import React, { useContext } from "react";
import "../../styles/Header.css";
import { Link } from "react-router-dom";
import { AuthContext } from "../../context/AuthContext";

const Header: React.FC = () => {
  const auth = useContext(AuthContext);
  const user = auth?.user;

  const fullName = user
    ? `${user.first_name ?? ""} ${user.last_name ?? ""}`.trim()
    : "";

  const initials = user
    ? `${user.first_name?.[0] ?? "F"}${user.last_name?.[0] ?? "P"}`.toUpperCase()
    : "";

  return (
    <header className="header">
      <div className="header__container">
        <div className="header__logo">
          <Link to="/">
            <span className="logo-icon">⟡</span>
            <span className="logo-text">
                <strong>Flow</strong> Pilot
            </span>
          </Link>
        </div>

        <nav className="header__nav">
          <Link to="/community">Community</Link>
          <Link to="/copilot">Copilot</Link>
          <a href="#about">About Us</a>
          <Link to="/signup">Get Started</Link>

          {user ? (
            <Link to="/profile" className="header__user-chip">
              <div className="header__user-avatar">
                {user.photo_url ? (
                  <img src={user.photo_url} alt={fullName || "User avatar"} />
                ) : (
                  <span>{initials}</span>
                )}
              </div>
              <span className="header__user-name">{fullName}</span>
            </Link>
          ) : (
            <Link to="/login" className="login">
              Login
            </Link>
          )}
        </nav>
      </div>
    </header>
  );
};

export default Header;
