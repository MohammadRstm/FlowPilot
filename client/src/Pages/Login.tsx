import React, { useState } from "react";
import "../styles/Auth.css";
import Header from "./components/Header";
import { useNavigate, Link } from "react-router-dom";
import { login as loginRequest } from "../api/auth";

import wf1 from "../assets/workflows/wf1.webp";
import wf2 from "../assets/workflows/wf2.png";
import wf3 from "../assets/workflows/wf3.webp";
import wf5 from "../assets/workflows/wf5.webp";

const workflowImages = [wf1, wf2, wf3, wf5];

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
      localStorage.setItem("token", token);
      navigate("/");
    } catch (err: any) {
      setError(err.message || "Failed to login");
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="auth-page">
      <Header />

      <main className="auth-layout">
        {/* LEFT */}
        <section className="auth-left">
          <div className="auth-card">
            <h1>Welcome back</h1>
            <p className="auth-subtitle">
              Login to access your workflows and copilot.
            </p>

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
          </div>
        </section>

        {/* RIGHT */}
        <section className="auth-right">

          <div className="workflow-marquee">
            <div className="workflow-track">
              {[...workflowImages, ...workflowImages].map((src, index) => (
                <div className="workflow-item" key={index}>
                  <img
                    src={src}
                    alt="workflow preview"
                    className="workflow-image"
                  />
                </div>
              ))}
            </div>
          </div>
        </section>
      </main>
    </div>
  );
};

export default Login;
