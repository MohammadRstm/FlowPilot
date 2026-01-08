import React from "react";
import "../../styles/Header.css";

const Header: React.FC = () => {
  return (
    <header className="header">
      <div className="header__container">
        {/* Logo */}
        <div className="header__logo">
          <span className="logo-icon">⟡</span>
          <span className="logo-text">
            <strong>Flow</strong> Pilot
          </span>
        </div>

        {/* Navigation */}
        <nav className="header__nav">
          <a href="#community">Community</a>
          <a href="#copilot">Copilot</a>
          <a href="#about">About Us</a>
          <a href="#get-started">Get Started</a>
          <a href="#login" className="login">
            Login
          </a>
        </nav>
      </div>
    </header>
  );
};

export default Header;
