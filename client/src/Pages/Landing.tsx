import "../styles/Landing.css";
import Header from "./components/Header";
import n8nMediaImage from "../assets/n8n-media.png";
import FakeWorkflow from "./components/FakeWorkflow";

export default function LandingPage() {
  console.log(localStorage.getItem("flowpilot_token"));

  return (
    <div className="landing-root">

    <Header />
      <section className="hero">
        <div className="hero-content">
          <h1>AI Generated Workflows</h1>
          <p>You Think It. We Make It.</p>
          <button className="cta">Get Started</button>
        </div>
      </section>

      <section className="workflow-showcase">
        <h2>n8n Has Never Been This Easy</h2>
        <h3>Discover what n8n can truly do</h3>
        <div className="workflow-card">
         <FakeWorkflow />
        </div>
      </section>

     <section className="data-chat-section">
    <h2>Chat With Your Own Data</h2>

        <div className="data-chat-layout">

            <div className="data-chat-card">
            <h3>Ask your data questions</h3>
            <p>
                Automate tasks to monitor your data as it evolves. Seamlessly integrate and transmit your data across platforms such as SMS, WhatsApp, Teams, Slack, and more. We manage the process end-to-end, allowing you to focus on what matters most.
            </p>

            <div className="chat-example">
                <div className="chat-msg user">
                Who did last night’s reports?
                </div>

                <div className="chat-msg bot">
                On Friday, Joe handled all summary reports.  
                He logged off at 9:45 pm.
                </div>

                <div className="chat-msg user">
                Create a task in Asana...
                </div>
            </div>
            </div>

            <div className="data-network">
            <img
                src={n8nMediaImage}
                alt="n8n data network"
                className="network-image"
            />
            </div>

        </div>
    </section>

    <section className="community">
        <h2>Join the n8n Community</h2>
        <p>Where automation builders collaborate, share, and grow</p>

        <div className="hub">
            <div className="center">Community</div>

            <div className="node n1">Workflow Library</div>
            <div className="node n2">Template Hub</div>
            <div className="node n3">n8n Experts</div>
            <div className="node n4">Custom Nodes</div>
            <div className="node n5">Best Practices</div>
            <div className="node n6">Use-Case Gallery</div>
            <div className="node n7">Troubleshooting</div>
            <div className="node n8">Community Plugins</div>

            <svg className="lines" viewBox="0 0 480 480">
                <path d="M240 240 C 180 120, 60 120, 40 120" />
                <path d="M240 240 C 320 100, 360 90, 380 80" />
                <path d="M240 240 C 360 220, 410 230, 430 230" />
                <path d="M240 240 C 340 330, 330 380, 320 400" />
                <path d="M240 240 C 240 380, 220 420, 220 440" />
                <path d="M240 240 C 120 360, 80 360, 70 350" />
                <path d="M240 240 C 80 260, 50 260, 40 260" />
                <path d="M240 240 C 200 120, 190 80, 180 60" />
            </svg>
        </div>
    </section>

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
