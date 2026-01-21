import "./login.css";
import Header from "../components/Header";
import { Link } from "react-router-dom";
import WorkflowMarquee from "./components/WorkflowMarquee";
import { useLogin } from "./hook/useLogin";
import { useForm } from "react-hook-form";
import { loginSchema, type LoginFormValues } from "./validation/login.schems";
import { zodResolver } from "@hookform/resolvers/zod";

const Login: React.FC = () => {

  const { handleLogin, loading } = useLogin();

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<LoginFormValues>({
    resolver: zodResolver(loginSchema),
  });

  return (
      <div className="auth-page">
        <Header />
        <main className="auth-layout">
          <section className="auth-left">
            <div className="auth-card">
              <h1>Welcome back</h1>
              <p className="auth-subtitle">
                Login to access your workflows and copilot.
              </p>
              <div id="google-login-btn" className="google-login-btn" />

              <div className="auth-divider">
                <span>or</span>
              </div>

              <form
                className="auth-form"
                onSubmit={handleSubmit(handleLogin)}
              >
                <label className="auth-label">
                  Email
                  <input
                    type="email"
                    placeholder="Enter your email"
                    className="auth-input"
                    {...register("email")}
                  />
                  {errors.email && (
                    <p className="auth-error">{errors.email.message}</p>
                  )}
                </label>

                <label className="auth-label">
                  Password
                  <input
                    type="password"
                    placeholder="Enter your password"
                    className="auth-input"
                    {...register("password")}
                  />
                  {errors.password && (
                    <p className="auth-error">{errors.password.message}</p>
                  )}
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
