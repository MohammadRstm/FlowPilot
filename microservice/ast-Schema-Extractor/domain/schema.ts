/**
 * Domain model for extracted n8n schemas.
 *
 * This file MUST NOT:
 * - import ts-morph
 * - read files
 * - write files
 *
 * It defines meaning, not mechanics.
 */

export interface FieldSchema {
  name?: string;
  displayName?: string;
  type?: string;
  required: boolean;
  description?: string;
}

export interface CredentialSchema {
  name?: string;
  displayName?: string;
  file: string;
}

export interface NodeSchema {
  name?: string;
  displayName?: string;
  description?: string;
  fields: FieldSchema[];
  credentials: CredentialSchema[];
  file: string;
  summary?: string;
  inputs: string[];
  outputs: string[];
}

/**
 * Factory helpers.
 * These protect you from half-built schemas.
 */

export function createFieldSchema(
  partial: Partial<FieldSchema>
): FieldSchema {
  return {
    required: false,
    ...partial,
  };
}

export function createCredentialSchema(
  partial: Partial<CredentialSchema>
): CredentialSchema {
  return {
    name: partial.name,
    displayName: partial.displayName,
    file: partial.file ?? "",
  };
}

export function createNodeSchema(
  partial: Partial<NodeSchema>
): NodeSchema {
  return {
    fields: [],
    credentials: [],
    file: "",
    inputs: [],
    outputs: [],
    ...partial,
  };
}
