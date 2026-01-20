import "../../styles/About.css";
import Header from "./components/Header";
import "../styles/AboutUs.css";

export default function AboutPage() {
  return (
    <div className="about-page">
      <Header />

      <section className="about-hero">
        <div className="about-hero-content">
          <h1>About FlowPilot</h1>
          <p>We turn intent into automation.</p>
        </div>
      </section>

      <section className="about-section">
        <div className="about-container">
          <h2>From ideas to workflows</h2>
          <p>
            FlowPilot removes the friction between what you want to automate and a
            production-ready n8n workflow. Describe the outcome, not the wiring.
            The system plans, reasons, and builds the workflow for you.
          </p>
        </div>
      </section>

      <section className="about-section alt">
        <div className="about-container">
          <h2>Transparent by design</h2>
          <p>
            FlowPilot does not hide behind a black box. As workflows are generated,
            you can see streaming traces that expose how the AI reasons about
            triggers, nodes, and data flow. Every decision is inspectable.
          </p>
          <p>
            This makes automation easier to trust, easier to debug, and easier to
            learn from.
          </p>
        </div>
      </section>

      <section className="about-section">
        <div className="about-container">
          <h2>Built specifically for n8n</h2>
          <p>
            FlowPilot is not a generic automation generator. It is deeply aligned
            with how n8n works, producing clean, import-ready workflows that follow
            best practices and remain fully customizable.
          </p>
          <ul className="about-list">
            <li>Native n8n workflow structure</li>
            <li>Readable, maintainable logic</li>
            <li>Scales from simple flows to complex systems</li>
          </ul>
        </div>
      </section>

      <section className="about-section alt">
        <div className="about-container">
          <h2>Human-first interaction</h2>
          <p>
            You do not need to think in nodes or expressions. FlowPilot lets you
            communicate intent in plain language while keeping you in full control
            of the final workflow.
          </p>
        </div>
      </section>

      <section className="about-section">
        <div className="about-container">
          <h2>A community of builders</h2>
          <p>
            FlowPilot is also a home for n8n builders. The community shares
            workflows, patterns, and expert insight. Profiles reflect real
            contributions, and reputation is built on usefulness.
          </p>
        </div>
      </section>

      <section className="about-section alt">
        <div className="about-container">
          <h2>Our philosophy</h2>
          <p>Automation should be understandable.</p>
          <p>AI should be collaborative, not opaque.</p>
          <p>Great tools grow stronger with community.</p>
        </div>
      </section>

      <section className="about-footer">
        <div className="about-container">
          <h3>FlowPilot</h3>
          <p>AI Powered n8n Automation</p>
        </div>
      </section>
    </div>
  );
}