import Header from "../components/Header";
import { useNavigate, Link } from "react-router-dom";
import { register as setToken } from "../../api/auth";
import "../../styles/signup.css";
import { useSignup } from "./hook/useSignup.hook";
import { useForm } from "react-hook-form";
import { signupSchema, type SignupFormValues } from "../../validation/signup.schema";
import { zodResolver } from "@hookform/resolvers/zod";

const Signup: React.FC = () =>{
  const navigate = useNavigate();
  const signup = useSignup();

  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
  } = useForm<SignupFormValues>({
    resolver: zodResolver(signupSchema),
  });


  const onSubmit = async (data: SignupFormValues) => {
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
              {errors.password && (
                <span className="field-error">
                  {errors.password.message}
                </span>
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
