import React from "react";
import { Loader2, Download } from "lucide-react";

type Props = {
  workflows: any[];
  onDownload: (url: string, id: string | number) => void;
  isDownloading?: boolean;
  downloadingId?: string | number | null;
};

const WorkflowsList: React.FC<Props> = ({ workflows, onDownload , isDownloading, downloadingId }) => {
  if (!workflows || workflows.length === 0) {
    return <div className="empty">No workflows / history available.</div>;
  }

  return (
    <section className="workflows-list">
      <ul>
        {workflows.map((w: any) => {
          const workflowId = w.id ?? w.filename ?? `${Math.random()}`;
          const isThisItemDownloading = downloadingId === workflowId && isDownloading;
          
          return (
            <li key={workflowId} className="workflow-item">
              <div className="wf-left">
                <span className="wf-id">{workflowId}</span>
                <span className="wf-separator">-</span>
                <span className="wf-date">{new Date(w.created_at ?? w.date ?? "").toLocaleString()}</span>
              </div>

              <button
                className="btn-download"
                onClick={() =>
                  onDownload(w.download_url ?? w.file_url, workflowId)
                }
                disabled={isThisItemDownloading}
                title="Download JSON"
              >
              {isThisItemDownloading ? <Loader2 className="animate-spin" /> : <Download />}
              </button>
            </li>
          );
        })}
      </ul>
    </section>
  );
};

export default WorkflowsList;
