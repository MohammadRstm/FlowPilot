import Header from "../components/Header";
import { useNavigate, Link } from "react-router-dom";
import "../../styles/signup.css";
import { useSignup } from "./hook/useSignup.hook";
import { useForm } from "react-hook-form";
import { signupSchema, type SignupFormValues } from "../../validation/signup.schema";
import { zodResolver } from "@hookform/resolvers/zod";
import { setToken } from "../../api/auth";
import { useMemo } from "react";
import zxcvbn from "zxcvbn";


const strengthLabels = ["Very weak", "Weak", "Fair", "Good", "Strong"];

const strengthColors = [
  "#e74c3c", // red
  "#e67e22", // orange
  "#f1c40f", // yellow
  "#2ecc71", // green
  "#27ae60", // dark green
];


const Signup: React.FC = () =>{
  const navigate = useNavigate();
  const signup = useSignup();

  const {
    register,
    handleSubmit,
    watch,
    formState: { errors, isSubmitting },
  } = useForm<SignupFormValues>({
    resolver: zodResolver(signupSchema),
  });
  const password = watch("password", "");

  const passwordStrength = useMemo(() => {
    if (!password) return null;
    return zxcvbn(password);
  }, [password]);

  const onSubmit = async (data: SignupFormValues) =>{
    const resp = await signup.mutateAsync(data);
    setToken(resp.token);
    navigate("/");
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

          <form className="signup-form" onSubmit={handleSubmit(onSubmit)}>
            <div className="signup-row">
              <label className="signup-label">
                First name
                <input {...register("firstName")} className="signup-input" />
                {errors.firstName && (
                  <span className="field-error">
                    {errors.firstName.message}
                  </span>
                )}
              </label>

              <label className="signup-label">
                Last name
                <input {...register("lastName")} className="signup-input" />
                {errors.lastName && (
                  <span className="field-error">
                    {errors.lastName.message}
                  </span>
                )}
              </label>
            </div>

            <label className="signup-label">
              Email
              <input {...register("email")} className="signup-input" />
              {errors.email && (
                <span className="field-error">{errors.email.message}</span>
              )}
            </label>

            <label className="signup-label">
              Password
              <input
                type="password"
                {...register("password")}
                className="signup-input"
              />
               {passwordStrength && (
                <div className="password-strength">
                  <div className="strength-bar">
                    <div
                      className="strength-bar-fill"
                      style={{
                        width: `${(passwordStrength.score + 1) * 20}%`,
                        backgroundColor: strengthColors[passwordStrength.score],
                      }}
                    />
                  </div>

                  <span
                    className="strength-text"
                    style={{ color: strengthColors[passwordStrength.score] }}
                  >
                    {strengthLabels[passwordStrength.score]}
                  </span>
                </div>
              )}

              {errors.password && (
                <span className="field-error">{errors.password.message}</span>
              )}
            </label>

            <label className="signup-label">
              Confirm password
              <input
                type="password"
                {...register("confirmPassword")}
                className="signup-input"
              />
              {errors.confirmPassword && (
                <span className="field-error">
                  {errors.confirmPassword.message}
                </span>
              )}
            </label>

            <button
              className="signup-button"
              type="submit"
              disabled={isSubmitting}
            >
              {isSubmitting ? "Creating account..." : "Sign up"}
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
