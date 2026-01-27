import { z } from "zod";

// shared sub schemas for both declarative and programmatic specs
const credentialFieldSchema = z.object({
  displayName: z.string(),
  name: z.string(),
  type: z.enum(["string", "number", "boolean", "options"]),
  required: z.boolean().optional(),
  description: z.string().optional(),
});

const credentialSchema = z.object({
  name: z.string(),
  displayName: z.string(),
  documentationUrl: z.string().optional(),
  properties: z.array(credentialFieldSchema),
});

const nodePropertySchema = z.object({
  displayName: z.string(),
  name: z.string(),
  type: z.string(), // options, string, number, boolean, collection, etc
  required: z.boolean().optional(),
  default: z.any().optional(),
  description: z.string().optional(),
  options: z.array(
    z.object({
      name: z.string(),
      value: z.string(),
      description: z.string().optional(),
    })
  ).optional(),
  routing: z.any().optional(), // validated later by custom validator
  displayOptions: z.any().optional(),
});

const requestDefaultsSchema = z.object({
  baseURL: z.string().url(),
  headers: z.record(z.string()).optional(),
});


const declarativeSpec = z.object({
  nodeType: z.literal("declarative"),

  node: z.object({
    displayName: z.string(),
    name: z.string(),
    description: z.string(),
    icon: z.string(),
    group: z.array(z.string()).default(["transform"]),
    version: z.number().default(1),
    subtitle: z.string().optional(),
    requestDefaults: requestDefaultsSchema,
  }),

  credentials: credentialSchema.optional(),

  properties: z.array(nodePropertySchema).min(1),
});


const programmaticSpec = z.object({
  nodeType: z.literal("programmatic"),

  node: z.object({
    displayName: z.string(),
    name: z.string(),
    description: z.string(),
    icon: z.string(),
    group: z.array(z.string()).default(["transform"]),
    version: z.number().default(1),
  }),

  credentials: credentialSchema.optional(),

  properties: z.array(nodePropertySchema).min(1),

  executeCode: z.string().min(50), // must contain full execute() body
});


// final union schema
export const NodeSpecSchema = z.discriminatedUnion("nodeType", [
  declarativeSpec,
  programmaticSpec,
]);

export type NodeSpec = z.infer<typeof NodeSpecSchema>;
