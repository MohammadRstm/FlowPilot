export function validateNodeSpec(spec: NodeSpec) {
  validateNames(spec);
  validateNoMaliciousCode(spec);
  validateRoutingSafety(spec);
}

function validateNames(spec: NodeSpec) {
  const validName = /^[a-zA-Z0-9]+$/;

  if (!validName.test(spec.node.name)) {
    throw new Error("Node name must be alphanumeric with no spaces");
  }

  if (spec.credentials && !validName.test(spec.credentials.name)) {
    throw new Error("Credential name must be alphanumeric");
  }
}


function validateNoMaliciousCode(spec: NodeSpec) {
  if (spec.nodeType !== "programmatic") return;

  const bannedPatterns = [
    "require('fs')",
    "require('child_process')",
    "process.env",
    "while(true)",
    "eval(",
  ];

  for (const pattern of bannedPatterns) {
    if (spec.executeCode.includes(pattern)) {
      throw new Error(`Disallowed code pattern detected: ${pattern}`);
    }
  }
}

function validateRoutingSafety(spec: NodeSpec) {
  if (spec.nodeType !== "declarative") return;

  for (const prop of spec.properties) {
    if (prop.routing?.request?.url?.includes("http")) {
      throw new Error("Routing URLs must be relative, baseURL is defined separately");
    }
  }
}
