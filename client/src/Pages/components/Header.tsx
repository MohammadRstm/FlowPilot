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
          <Link to="/">Community</Link>
          <Link to="/copilot">Copilot</Link>
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
