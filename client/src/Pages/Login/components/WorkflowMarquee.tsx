import wf3 from "../../../assets/workflows/wf3.webp";
import wf6 from "../../../assets/workflows/highly_complicated_wf.webp";
import wf7 from "../../../assets/workflows/production-ready-wf.avif";

const images = [
  {
    image : wf6,
    title : "Complicated Workflows"
  },
  {
    image : wf7,
    title : "Production Ready Wrokflows"
  },
  {
    image: wf3,
    title: "Huge Workflow"
  }
]

const WorkflowMarquee = () => {
  return (
    <div className="workflow-marquee">
      <div className="workflow-header">
        Start Generating
      </div>

      <div className="workflow-track-wrapper">
        <div className="workflow-track">
          {[...images, ...images].map((ob, index) => (
            <div className="workflow-item" key={index}>
              <div className="workflow-card">
                <div className="workflow-title">{ob.title}</div>
                <img
                  src={ob.image}
                  alt="workflow preview"
                  className="workflow-image"
                />
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
};

export default WorkflowMarquee;
