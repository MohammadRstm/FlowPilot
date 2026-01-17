import React, { useState } from "react";
import "../styles/Auth.css";
import Header from "./components/Header";
import { useNavigate, Link } from "react-router-dom";
import { login as loginRequest } from "../api/auth";

const Login: React.FC = () => {
  const navigate = useNavigate();
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);
    setLoading(true);

    try {
      const { token } = await loginRequest(email, password);
      localStorage.setItem("flowpilot_token", token);
      navigate("/copilot");
    } catch (err: any) {
      setError(err.message || "Failed to login");
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="auth-page">
      <Header />
      <main className="auth-main">
        <section className="auth-card">
          <h1>Welcome back</h1>
          <p className="auth-subtitle">Login to access your workflows and copilot.</p>

          {error && <div className="auth-error">{error}</div>}

          <form className="auth-form" onSubmit={handleSubmit}>
            <label className="auth-label">
              Email
              <input
                type="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                className="auth-input"
                required
              />
            </label>

            <label className="auth-label">
              Password
              <input
                type="password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                className="auth-input"
                required
              />
            </label>

            <button className="auth-button" type="submit" disabled={loading}>
              {loading ? "Logging in..." : "Login"}
            </button>
          </form>

          <p className="auth-footer-text">
            Don't have an account? <Link to="/signup">Create one</Link>
          </p>
        </section>
      </main>
    </div>
  );
};

export default Login;