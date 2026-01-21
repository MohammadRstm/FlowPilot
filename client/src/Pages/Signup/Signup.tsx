import React, { useState } from "react";
import Header from "../components/Header";
import { useNavigate, Link } from "react-router-dom";
import { register as registerRequest, setToken } from "../../api/auth";
import "../../styles/signup.css";

interface SignupForm{
  firstName:string,
  lastName:string,
  email:string,
  password:string,
  confirmPassword:string,
}

const Signup: React.FC = () =>{
  const navigate = useNavigate();
  const [form, setForm] = useState<SignupForm>({
    firstName: "",
    lastName: "",
    email: "",
    password: "",
    confirmPassword: "",
  });

  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const onChange = (key: keyof SignupForm) =>
    (e: React.ChangeEvent<HTMLInputElement>) => {
      setForm((prev) => ({ ...prev, [key]: e.target.value }));
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);

    if(form.password !== form.confirmPassword){
      setError("Passwords do not match");
      return;
    }

    setLoading(true);

    try{
      const { token } = await registerRequest({
        first_name: form.firstName,
        last_name: form.lastName,
        email: form.email,
        password: form.password,
      });

      setToken(token);
      navigate("/");
    }catch(err: any){
      setError(err.message || "Failed to create account");
    }finally{
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
                  value={form.firstName}
                  onChange={onChange("firstName")}
                  className="signup-input"
                  required
                />
              </label>

              <label className="signup-label">
                Last name
                <input
                  type="text"
                  value={form.lastName}
                  onChange={onChange("lastName")}
                  className="signup-input"
                  required
                />
              </label>
            </div>

            <label className="signup-label">
              Email
              <input
                type="email"
                value={form.email}
                onChange={onChange("email")}
                className="signup-input"
                required
              />
            </label>

            <label className="signup-label">
              Password
              <input
                type="password"
                value={form.password}
                onChange={onChange("password")}
                className="signup-input"
                required
              />
            </label>

            <label className="signup-label">
              Confirm password
              <input
                type="password"
                value={form.confirmPassword}
                onChange={onChange("confirmPassword")}
                className="signup-input"
                required
              />
            </label>

            <button className="signup-button" type="submit" disabled={loading}>
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
