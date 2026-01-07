import "../styles/Landing.css";


export default function LandingPage() {
  return (
    <div className="landing-root">

      {/* Hero */}
      <section className="hero">
        <div className="hero-content">
          <h1>AI Generated Workflows</h1>
          <p>You Think It. We Make It.</p>
          <button className="cta">Get Started</button>
        </div>
      </section>

      {/* Workflow image */}
      <section className="workflow-showcase">
        <h2>n8n Has Never Been This Easy</h2>
        <div className="workflow-card">
          <div className="fake-workflow" />
        </div>
      </section>

      {/* Chat With Your Data */}
      <section className="chat-section">
        <h2>Chat With Your Own Data</h2>
        <div className="chat-grid">
          <div className="chat-card">
            <h3>Ask Questions</h3>
            <p>Query your data across platforms.</p>
          </div>
          <div className="chat-card">
            <h3>Automate</h3>
            <p>Create workflows that act for you.</p>
          </div>
          <div className="chat-card">
            <h3>Connect</h3>
            <p>Integrate Slack, WhatsApp, Teams and more.</p>
          </div>
        </div>
      </section>

      {/* Community */}
      <section className="community">
        <h2>Join Our Community</h2>
        <p>Be a part of our circle</p>

        <div className="hub">
          <div className="center">FlowPilot</div>

          <div className="node node1">Share Workflows</div>
          <div className="node node2">Templates</div>
          <div className="node node3">Ask Experts</div>
          <div className="node node4">Plugins</div>

          <svg className="lines" viewBox="0 0 400 400">
            <path d="M200 200 C 200 80, 80 80, 80 140" />
            <path d="M200 200 C 320 80, 360 140, 320 200" />
            <path d="M200 200 C 320 320, 360 300, 300 320" />
            <path d="M200 200 C 80 320, 60 300, 100 260" />
          </svg>
        </div>
      </section>

      {/* Footer */}
      <footer>
        <div>
          <h3>FlowPilot</h3>
          <p>AI Powered n8n Automation</p>
        </div>
        <div>
          <p>Community</p>
          <p>Docs</p>
          <p>Contact</p>
        </div>
      </footer>

    </div>
  );
}
