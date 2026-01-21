import "../../styles/Login.css";
import Header from "../components/Header";
import { Link } from "react-router-dom";
import { useLogin } from "../../hooks/mutations/Auth/useLogin";
import WorkflowMarquee from "./components/WorkflowMarquee";

const Login: React.FC = () => {
  const {
    email,
    password,
    loading,
    error,
    setEmail,
    setPassword,
    handleSubmit,
  } = useLogin();

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

            <div id="google-login-btn" className="google-login-btn" />

            <div className="auth-divider">
              <span>or</span>
            </div>

            <form className="auth-form" onSubmit={handleSubmit}>
              <label className="auth-label">
                Email
                <input
                  type="email"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  placeholder="Enter your email"
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
                  placeholder="Enter your password"
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
        <section className="auth-right">
          <WorkflowMarquee />
        </section>
      </main>
    </div>
  );
};

export default Login;
