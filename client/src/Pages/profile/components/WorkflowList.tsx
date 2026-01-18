// src/pages/profile/components/WorkflowsList.tsx
import React from "react";

type Props = {
  workflows: any[];
  downloadHistory: (url?: string) => void;
};

const WorkflowsList: React.FC<Props> = ({ workflows, downloadHistory }) => {
  if (!workflows || workflows.length === 0) {
    return <div className="empty">No workflows / history available.</div>;
  }

  return (
    <section className="workflows-list">
      <ul>
        {workflows.map((w: any) => (
          <li key={w.id ?? w.file_url ?? `${Math.random()}`} className="workflow-item">
            <div className="wf-left">
              <span className="wf-id">{w.id ?? w.filename ?? "history"}</span>
              <span className="wf-separator">-</span>
              <span className="wf-date">{new Date(w.created_at ?? w.date ?? "").toLocaleString()}</span>
            </div>

            <button className="btn-download" onClick={() => downloadHistory(w.download_url ?? w.file_url)} title="Download JSON">
              📄
            </button>
          </li>
        ))}
      </ul>
    </section>
  );
};

export default WorkflowsList;
