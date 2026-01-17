import React from "react";
import "../../styles/Header.css";
import { Link } from "react-router-dom";

const Header: React.FC = () => {
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
          <Link to="/login" className="login">
            Login
          </Link>
        </nav>
      </div>
    </header>
  );
};

export default Header;
