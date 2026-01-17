import wf1 from "../../../assets/workflows/wf1.webp";
import wf2 from "../../../assets/workflows/wf2.png";
import wf3 from "../../../assets/workflows/wf3.webp";
import wf5 from "../../../assets/workflows/wf5.webp";

const images = [wf1, wf2, wf3, wf5];

const WorkflowMarquee = () => {
  return (
    <div className="workflow-marquee">
      <div className="workflow-track">
        {[...images, ...images].map((src, index) => (
          <div className="workflow-item" key={index}>
            <img
              src={src}
              alt="workflow preview"
              className="workflow-image"
            />
          </div>
        ))}
      </div>
    </div>
  );
};

export default WorkflowMarquee;
