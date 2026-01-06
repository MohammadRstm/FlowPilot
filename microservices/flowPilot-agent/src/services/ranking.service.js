export function rankResults(analysis, points) {
  return {
    workflows: rankWorkflows(analysis, points.workflows),
    nodes: rankNodes(analysis, points.nodes),
    schemas: rankSchemas(analysis, points.schemas)
  };
}

function rankWorkflows(analysis, hits) {
  const scored = hits.map(hit => {
    const p = hit.payload;
    const nodeMatchScore = nodeMatchScoreFn(analysis.nodes, p.nodes_used || []);
    const intentScore = intentScoreFn(analysis.intent, p.nodes_used || []);
    const complexityScore = complexityScoreFn(p.min_nodes || 0, (p.nodes_used || []).length);

    const score =
      (hit.score * 0.3) +
      (nodeMatchScore * 0.4) +
      (intentScore * 0.2) +
      (complexityScore * 0.1);

    return {
      score: parseFloat(score.toFixed(4)),
      workflow: p.workflow,
      nodes: p.nodes_used,
      raw: p.raw
    };
  });

  scored.sort((a, b) => b.score - a.score);

  // Keep only top 5 workflows
  return scored.slice(0, 5);
}

function rankNodes(analysis, hits) {
  const ranked = hits.map(hit => {
    const p = hit.payload;
    let score = hit.score;
    if (analysis.nodes?.some(n => n.toLowerCase() === p.key.toLowerCase())) {
      score += 0.5;
    }

    return {
      score: parseFloat(score.toFixed(4)),
      node: p.node,
      key: p.key,
      categories: p.categories
    };
  });

  ranked.sort((a, b) => b.score - a.score);

  return ranked.slice(0, 15);
}

function rankSchemas(analysis, hits) {
  const ranked = hits.map(hit => {
    const p = hit.payload;
    let score = hit.score;
    if (analysis.nodes?.some(n => n.toLowerCase() === p.node.toLowerCase())) {
      score += 0.4;
    }

    return {
      score: parseFloat(score.toFixed(4)),
      node: p.node,
      resource: p.resource,
      operation: p.operation,
      fields: p.fields
    };
  });

  ranked.sort((a, b) => b.score - a.score);

  return ranked.slice(0, 30);
}

function nodeMatchScoreFn(wanted, has) {
  if (!wanted?.length) return 0.5;
  const normalize = str => str.toLowerCase().replace(/[^a-z0-9]/g, "");
  const w = wanted.map(normalize);
  const h = has.map(normalize);
  const match = w.filter(x => h.includes(x));
  return match.length / w.length;
}

function intentScoreFn(intent, nodes) {
  // simple version; can expand with more logic
  return nodes.some(n => n.toLowerCase().includes("trigger")) ? 1.0 : 0.5;
}

function complexityScoreFn(minRequired, actual) {
  if (actual === 0 || actual < minRequired) return 0.0;
  if (actual <= minRequired * 1.5) return 1.0;
  if (actual <= minRequired * 2.5) return 0.8;
  return 0.6;
}
