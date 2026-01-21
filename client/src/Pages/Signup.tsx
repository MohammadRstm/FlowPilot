import React, { useState } from "react";
import Header from "./components/Header";
import { useNavigate, Link } from "react-router-dom";
import { register as registerRequest } from "../api/auth";
import "../styles/signup.css";
const Signup: React.FC = () => {
  const navigate = useNavigate();
  const [firstName, setFirstName] = useState("");
  const [lastName, setLastName] = useState("");
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [confirmPassword, setConfirmPassword] = useState("");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);

    if (password !== confirmPassword) {
      setError("Passwords do not match");
      return;
    }

    setLoading(true);

    try {
      const { token } = await registerRequest({
        first_name: firstName,
        last_name: lastName,
        email,
        password,
      });

      localStorage.setItem("token", token);
      navigate("/");
    } catch (err: any) {
      setError(err.message || "Failed to create account");
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="signup-page">
      <Header />

      <main className="signup-main">
        <section className="signup-card">
          <h1>Create your account</h1>
          <p className="signup-subtitle">
            Join the FlowPilot community and start automating.
          </p>

          {error && <div className="signup-error">{error}</div>}

          <form className="signup-form" onSubmit={handleSubmit}>
            <div className="signup-row">
              <label className="signup-label">
                First name
                <input
                  type="text"
                  value={firstName}
                  onChange={(e) => setFirstName(e.target.value)}
                  className="signup-input"
                  required
                />
              </label>

              <label className="signup-label">
                Last name
                <input
                  type="text"
                  value={lastName}
                  onChange={(e) => setLastName(e.target.value)}
                  className="signup-input"
                  required
                />
              </label>
            </div>

            <label className="signup-label">
              Email
              <input
                type="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                className="signup-input"
                required
              />
            </label>

            <label className="signup-label">
              Password
              <input
                type="password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                className="signup-input"
                required
              />
            </label>

            <label className="signup-label">
              Confirm password
              <input
                type="password"
                value={confirmPassword}
                onChange={(e) => setConfirmPassword(e.target.value)}
                className="signup-input"
                required
              />
            </label>

            <button
              className="signup-button"
              type="submit"
              disabled={loading}
            >
              {loading ? "Creating account..." : "Sign up"}
            </button>
          </form>

          <p className="signup-footer-text">
            Already have an account? <Link to="/login">Login</Link>
          </p>
        </section>
      </main>
    </div>
  );
};

export default Signup;
