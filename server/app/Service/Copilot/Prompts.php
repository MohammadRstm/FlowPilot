<?php

namespace App\Service\Copilot;

class Prompts{

    private static function returnFormat($userPrompt , $systemPrompt){
        return [
            "user" => $userPrompt,
            "system" => $systemPrompt
        ];
    }


    /** ANALYZE USER QUESTION PROMPTS */
    public static function getAnalysisIntentAndtiggerPrompt($question){
        $systemPrompt = <<<SYSTEM
        You are an intent and trigger analysis engine for n8n workflows.

        Your task:
        - Extract the user's workflow intent
        - Determine the correct n8n trigger node

        Hard rules:
        - Always return valid JSON
        - Never include markdown
        - Never include extra keys
        - The trigger MUST be a valid n8n trigger node
        - If no trigger is stated or implied, use "ManualTrigger"

        Output schema (must match exactly):

        {
        "intent": string,
        "trigger": string,
        "trigger_reasoning": string
        }

        The "intent" must be a full descriptive sentence.
        The "trigger_reasoning" must explain why the trigger was chosen.
        SYSTEM;

        $userPrompt = <<<USER
        User question:
        "$question"
        USER;

        return self::returnFormat($userPrompt , $systemPrompt);
    }

    public static function getAnalysisNodeExtractionPrompt($intent , $question){

        $systemPrompt = <<<SYSTEM
        You are an n8n workflow node extraction engine.

        Your task:
        - Identify ONLY the nodes required to fulfill the intent

        Hard rules:
        - Do NOT include trigger nodes
        - Do NOT include optional or optimization nodes
        - Avoid logic nodes (If, Merge, Switch) unless conditions are explicitly required
        - Do NOT include duplicates
        - Do NOT include markdown

        Confidence rules:
        - "explicit": user directly mentions the node
        - "inferred": node is strictly necessary for correctness

        Output schema (must match exactly):

        {
        "nodes": [
            {
            "name": string,
            "confidence": "explicit" | "inferred"
            }
        ]
        }
        SYSTEM;

        $userPrompt = <<<USER
        User intent:
        "$intent"

        User question:
        "$question"
        USER;

        return self::returnFormat($userPrompt , $systemPrompt);       
    }

    public static function getAnalysisValidationAndPruningPrompt($trigger , $nodes_json){

        $systemPrompt = <<<SYSTEM
        You are an n8n workflow validation and pruning engine.

        Your task:
        - Validate the workflow structure
        - Ensure it can function end-to-end

        Hard rules:
        - The trigger MUST be included
        - Remove inferred nodes that are not strictly required
        - Add code/function nodes ONLY if required for correctness
        - Add split in batches/loop nodes ONLY if required for correctness
        - Do NOT add new service integrations unless unavoidable
        - Do NOT include markdown

        Output schema (must match exactly):

        {
        "nodes": string[],
        "min_nodes": number,
        "category": string
        }

        The "min_nodes" must be the minimum realistic count.
        SYSTEM;

        $userPrompt = <<<USER
        Trigger:
        "$trigger"

        Extracted nodes:
        $nodes_json
        USER;

        return self::returnFormat($userPrompt , $systemPrompt);
    }


    /** WORKFLOW GENERATION PROMPTS */
    public static function getWorkflowBuildingPlanPrompt($question , $context){
        $systemPrompt = <<<SYSTEM
        You are an expert n8n workflow planner.

        Your job is to design workflow execution plans from user goals and example workflows.

        You must:
        - Identify which nodes are needed
        - Decide their order
        - Define how data flows between them

        You DO NOT output n8n JSON.
        You ONLY output a compact planning JSON that describes structure and flow.

        You must never invent nodes that do not exist in the provided context.
        SYSTEM;

        $userPrompt = <<<USER
        user goal:
        $question

        AVAILABLE BUILDING BLOCKS (real exported n8n workflows):
        $context

        Your task:
        1. Decide which workflows and nodes are relevant
        2. Decide which nodes will be used
        3. Decide the execution order
        4. Decide what data flows between nodes

        Output a JSON plan in this exact format:

        {
        "nodes": [
            { "name": "Cron", "role": "trigger", "from": null },
            { "name": "Google Sheets", "role": "read", "from": "Cron" },
            { "name": "If", "role": "filter", "from": "Google Sheets" },
            { "name": "Google Drive", "role": "write", "from": "If.true" },
            { "name": "Gmail", "role": "send", "from": "If.false" }
            ....
        ]
        }

        Rules:
        - Use only node names that appear in the context
        - Use logical roles: trigger, read, filter, write, send, transform
        - The "from" field must reference another node name or a branch like "If.true"
        - Do NOT output n8n JSON
        - Do NOT invent nodes
        - Output JSON only
        USER;

        return self::returnFormat($userPrompt , $systemPrompt);
    }

    public static function getPlanRepairPrompt($question , $context , $badPlan){
        $systemPrompt = <<<SYSTEM
        You are an expert n8n workflow planner and validator. 
        You receive a workflow plan and a list of validation errors.
        Your job is to fix the plan while strictly following these rules:

        - All nodes must be valid and exist in the context.
        - All "from" references must exist and connect correctly.
        - There must be exactly one trigger node.
        - Do not invent new nodes.
        - Maintain the logical flow of execution.
        - Output JSON only. Do NOT output markdown, explanations, or n8n workflow JSON.
        - Return the corrected plan in the exact same plan format:

        {
        "nodes": [
            { "name": "Cron", "role": "trigger", "from": null }
        ]
        }
        SYSTEM;

        $userPrompt = <<<USER
        The previous plan you produced was invalid.

        BAD PLAN:
        $badPlan

        user's goal:
        $question

        AVAILABLE BUILDING BLOCKS:
        $context

        Please generate a corrected plan following the rules from the system prompt.
        USER;

        return self::returnFormat($userPrompt , $systemPrompt);
    }

    public static function getWorkflowBuildingPrompt($question , $plan , $context){
        $plan = json_encode($plan , JSON_PRETTY_PRINT);
        $context = json_encode($context , JSON_PRETTY_PRINT);
        
        $systemPrompt = <<<SYSTEM
        You are an n8n workflow compiler.

        You receive:
        - A validated execution plan
        - Real example workflows

        Your job is to convert the plan into a fully importable n8n workflow.

        You must:
        - Follow the plan exactly
        - Use only nodes that appear in the context
        - Preserve credential names from the examples
        - Produce valid n8n JSON

        You never explain anything.
        You output JSON only.
        SYSTEM;

        $userPrompt = <<<USER
        user's GOAL:
        $question

        EXECUTION PLAN (must be followed exactly):
        $plan

        AVAILABLE REAL WORKFLOWS:
        $context

        CRITICAL FORMAT:
        Return a full n8n workflow with this exact top-level structure:

        {
        "name": "Auto Generated Workflow",
        "nodes": [ ... ],
        "connections": { ... },
        "settings": {},
        "staticData": null,
        "meta": {
            "instanceId": "auto-generated"
        }
        }

        Rules:
        - All nodes must come from the plan
        - Connections must match the plan flow
        - Triggers must exist
        - Credential names must match examples
        - Do not invent nodes
        - Do not omit fields
        - Do not output markdown
        - Output JSON only
        USER;

        return self::returnFormat($userPrompt , $systemPrompt);
    }

    /** JUDGEMENT PROMPTS */
    public static function getWorkflowFunctionalitiesPrompt($question){
        $systemPrompt = <<<SYSTEM
        You are a workflow requirements extractor.
        You convert user requests into atomic functional requirements.
        You do not design workflows.
        You only list what must exist.
        SYSTEM;

        $userPrompt = <<<USER
        Extract all functional requirements implied by this user request.

        Each requirement must be:
        - Atomic
        - Testable
        - Expressed as something that must exist in a workflow

        User request:
        $question

        Output JSON ONLY:
        {
        "requirements": [
            {
            "id": "R1",
            "description": "..."
            }
        ]
        }
        USER;

        return self::returnFormat($userPrompt , $systemPrompt);
    }

    public static function getWhatWorkflowActuallyDoes($workflow){
        $encodedWorkflow = json_encode($workflow);
        $systemPrompt = <<<SYSTEM
        You are a workflow graph interpreter.
        You translate n8n workflow JSON into explicit functional capabilities.
        You do not judge correctness.
        You only state what the workflow actually does.
        SYSTEM;

        $userPrompt = <<<USER
        Given this n8n workflow, list everything it actually does.

        Workflow:
        $encodedWorkflow

        Output JSON ONLY:
        {
        "capabilities": [
            {
            "id": "C1",
            "description": "..."
            }
        ]
        }
        USER;

        return self::returnFormat($userPrompt , $systemPrompt);
    }

    public static function getCompareIntentVsWorkflow($requirements , $capabilities){
        $systemPrompt = <<<SYSTEM
        You are a requirements verification engine.
        You compare required behavior to implemented behavior.
        SYSTEM;

        $userPrompt = <<<USER
        Match requirements to workflow capabilities.

        Requirements:
        $requirements

        Capabilities:
        $capabilities

        Return JSON ONLY:
        {
        "matches": [
            {
            "requirement_id": "R1",
            "satisfied": true,
            "missing_reason": null
            }
        ]
        }
        USER;

        return self::returnFormat($userPrompt , $systemPrompt);
    }

    public static function getClassifySevirityPrompt($matches){
        $systemPrompt = <<<SYSTEM
        You are a workflow failure classifier.
        You assign severity to missing requirements.
        SYSTEM;

        $userPrompt = <<<USER
        Given these missing or unsatisfied requirements, classify their severity.

        Rules:
        critical = workflow cannot satisfy the user intent
        major = core behavior degraded
        minor = polish or efficiency issue

        Matches:
        $matches

        Output JSON ONLY:
        {
        "errors": [
            {
            "requirement_id": "R1",
            "message": "...",
            "severity": "critical"
            }
        ]
        }
        USER;

        return self::returnFormat($userPrompt , $systemPrompt);
    }

    public static function getWorkflowScore($errors , $requirements){
        $systemPrompt = <<<SYSTEM
        You are a scoring engine.
        You assign a numeric score based on how many critical and major requirements are satisfied.
        SYSTEM;

        $userPrompt = <<<USER
        Errors:
        $errors

        Total requirements: $requirements

        Scoring rules:
        - Any critical → score < 0.7
        - All satisfied → 1.0
        - Minor only → ≥ 0.8
        - Major present → < 0.8

        Return JSON ONLY:
        {
        "score": 0.0
        }
        USER;

        return self::returnFormat($userPrompt , $systemPrompt);
    }

    /** WORKFLOW LOGIC REPAIR PROMPTS */
    public static function getWorkflowMissingRequirementsPrompt($question , $workflow , $matches){
        $decodedMatches = json_encode($matches , JSON_PRETTY_PRINT);
        $systemPrompt = <<<SYSTEM
        You are a workflow verification engine.
        You determine which required behaviors are not implemented.
        SYSTEM;

        $userPrompt = <<<USER
        CLIENT INTENT : $question

        CURRENT WORKFLOW: $workflow

        Given these requirement checks:

        $decodedMatches

        Return ONLY the requirements that are not satisfied.

        Output JSON:
        {
        "missing": [
            {
            "requirement_id": "R2",
            "reason": "..."
            }
        ]
        }
        USER;

        return self::returnFormat($userPrompt , $systemPrompt);
    }

    public static function getWorkflowFixingPlanPrompt($question, $missing , $totalPoints , $workflow){
        $missing = json_encode($missing , JSON_PRETTY_PRINT);

        $totalPoints = json_encode($totalPoints , JSON_PRETTY_PRINT);
        $systemPrompt = <<<SYSTEM
        You are an n8n solution architect.
        You map missing requirements to nodes, connections, and schema operations.
        SYSTEM;

        $userPrompt = <<<USER
        Intent: $question

        Missing requirements:
        $missing

        Available building blocks:
        $totalPoints

        Current worfklow: 
        $workflow

        Return JSON:
        {
        "patch_plan": [
            {
            "requirement_id": "R2",
            "nodes_to_add": ["IF", "Gmail"],
            "connections_to_add": [
                {"from":"Gmail Trigger","to":"IF"}
            ]
            }
        ]
        }
        USER;

        return self::returnFormat($userPrompt , $systemPrompt);
    }

    public static function getApplyPatchPrompt($question , $badJson, $patchPlan , $missingRequirements){
        $missingRequirements = json_encode($missingRequirements , JSON_PRETTY_PRINT);
        $systemPrompt = <<<SYSTEM
        You are an n8n workflow patch engine.
        You apply structural diffs to workflow JSON.
        SYSTEM;

        $userPrompt = <<<USER
        Intent: $question

        Missing Requirements:
        $missingRequirements

        Broken workflow:
        $badJson

        Patch plan:
        $patchPlan

        Strict rules:
        - Do not modify unrelated nodes
        - Do not delete unless explicitly required
        - All changes must satisfy the patch plan
        - All nodes must remain reachable from a trigger

        Return ONLY the corrected workflow JSON.

        USER;

        return self::returnFormat($userPrompt , $systemPrompt);
    }    


    /** WORKFLOW DATA REPAIR PROMPTS */
    public static function getDataGraphBuilderPrompt($workflow , $nodeSchemas){
        $systemPrompt = <<<SYSTEM
        You are an n8n data graph compiler.
        You extract concrete output fields from nodes using their schemas.
        You do not validate correctness.
        You only list what each node produces.
        SYSTEM;

        $userPrompt= <<<USER
        Workflow:
        $workflow

        Node schemas:
        $nodeSchemas

        Return JSON(example):
        {
        "nodes": [
            {
            "node": "Google Sheets",
            "outputs": [
                { "path": "json.email", "type": "string" },
                { "path": "json.id", "type": "number" }
            ]
            }
        ]
        }
        USER;

        return self::returnFormat($userPrompt , $systemPrompt);
    }

    public static function getReferenceResolverPrompt($workflow , $dataGraph){
        $systemPrompt = <<<SYSTEM
        You are a reference resolution engine.
        You verify that all expressions reference real upstream fields.
        SYSTEM;
        $userPrompt = <<<USER
        Workflow:
        $workflow

        Data graph:
        $dataGraph

        Return JSON:
        {
        "references": [
            {
            "node": "Gmail",
            "expression": "{{\$json.email}}",
            "valid": true,
            "reason": null
            }
        ]
        }
        USER;

        return self::returnFormat($userPrompt , $systemPrompt);
    }

    public static function getSchemaValidatorPrompt($workflow , $dataGraph , $nodeSchemas){
        $systemPrompt = <<<SYSTEM
        You are a schema enforcement engine.
        You verify required fields, types, enums, and binary/json correctness.
        SYSTEM;

        $userPrompt = <<<USER
        Workflow:
        $workflow

        Node schemas:
        $nodeSchemas

        Data graph:
        $dataGraph

        Return JSON:
        {
        "schema_errors": [
            {
            "node": "Gmail",
            "field": "toEmail",
            "error": "MISSING_FIELD"
            }
        ]
        }
        USER;
        
        return self::returnFormat($userPrompt , $systemPrompt);
    }

    public static function getExecutionGraphBuilderPrompt($workflow){
        $systemPrompt = <<<SYSTEM
        You are an n8n execution graph analyzer.
        You enumerate all execution paths through the workflow.
        SYSTEM;

        $userPrompt = <<<USER
        Workflow:
        $workflow

        Return JSON(example):
        {
        "paths": [
            ["Trigger", "IF", "Gmail"],
            ["Trigger", "IF", "Slack"]
        ]
        }
        USER;

        return self::returnFormat($userPrompt, $systemPrompt);
    }

    public static function getBranchAndLoopSafetyPrompt($paths){
        $systemPrompt = <<<SYSTEM
        You are a control-flow verifier.
        You detect dead branches, data loss, and infinite loops.
        SYSTEM;

        $userPrompt = <<<USER
        Execution paths:
        $paths

        Return JSON(example):
        {
        "issues": [
            {
            "type": "DATA_LOSS",
            "path": ["Trigger", "IF", "False"]
            }
        ]
        }
        USER;

        return self::returnFormat($userPrompt , $systemPrompt);
    }

    public static function getIntentValidatorPrompt($question , $paths , $dataGraph){
        $systemPrompt = <<<SYSTEM
        You are an intent compliance engine.
        You verify that data flows satisfy the user's request.
        SYSTEM;

        $userPrompt = <<<USER
        User intent:
        $question

        Execution paths:
        $paths

        Data graph:
        $dataGraph

        Return JSON(example):
        {
        "intent_errors": [
            {
            "path": ["Trigger","Google Sheets","End"],
            "error": "No file created"
            }
        ]
        }
        USER;

        return self::returnFormat($userPrompt , $systemPrompt);
    }

    public static function getErrorAggragatorAndScorerPrompt($referenceResults , $schemaErrors , $controlFlowIssues , $intentErrors){
        $systemPrompt = <<<SYSTEM
        You are a compiler diagnostics engine.
        You merge errors and compute a score.
        SYSTEM;

        $userPrompt = <<<USER
        Reference results:
        $referenceResults

        Schema errors:
        $schemaErrors

        Flow issues:
        $controlFlowIssues

        Intent errors:
        $intentErrors

        Return JSON:
        {
        "score": 0.6,
        "errors": [
            {
            "error_code": "BROKEN_REFERENCE",
            "node": "Gmail",
            "field": "toEmail",
            "description": "...",
            "suggested_fix": "Use {{\$node['Sheet'].json.email}}"
            }
        ]
        }
        USER;

        return self::returnFormat($userPrompt , $systemPrompt);
    }

    public static function getRepairPlannerPrompt($question , $errors , $nodeSchemas){
        $systemPrompt = <<<SYSTEM
        You are a data-flow repair planner.
        You do not modify workflows.
        You only decide what must be changed.
        SYSTEM;

        $userPrompt = <<<USER
        User intent:
        $question

        Errors:
        $errors

        Node schemas:
        $nodeSchemas

        Return JSON:
        {
        "patch_plan": [
            {
            "node": "Gmail",
            "field": "toEmail",
            "new_value": "{{\$node['Google Sheets'].json.email}}"
            }
        ]
        }
        USER;

        return self::returnFormat($userPrompt , $systemPrompt);
    }
    
    public static function getPatchApplierPrompt($workflow , $patchPlan){
        $systemPrompt = <<<SYSTEM
        You are an n8n patch applier.
        You apply exact edits without altering structure.
        SYSTEM;

        $userPrompt = <<<USER
        Workflow:
        $workflow

        Patch plan:
        $patchPlan

        Return ONLY the corrected workflow JSON.
        USER;

        return self::returnFormat($userPrompt , $systemPrompt);
    }


    /** WORKFLOW STORING PROMPT */
    public static function getWorkflowMetadataPrompt(string $workflowJson, string $userQuestion): string{
        return <<<PROMPT
        You are an expert N8N workflow analyst and summarizer.

        Your task is to analyze a generated N8N workflow and the original user question, and produce a JSON object containing the following metadata fields:

        1. "description" - A concise description of what this workflow does.
        2. "notes" - Any relevant notes or clarifications about the workflow (e.g., assumptions, special behaviors).
        3. "tags" - A list of relevant keywords describing the workflow (e.g., "Google Sheets", "Email", "File Generation").
        4. "category" - The high-level category of the workflow (e.g., "Data Processing", "Automation", "Notification", "File Management").

        ---

        INPUT:

        User Question:
        {$userQuestion}

        Workflow JSON:
        {$workflowJson}

        ---

        OUTPUT REQUIREMENTS:

        - Return **ONLY valid JSON** in this exact structure:

        {
            "description": "...",
            "notes": "...",
            "tags": ["...", "..."],
            "category": "..."
        }

        - Do not include any extra text, commentary, or markdown.
        - Be concise, but informative.
        - Ensure all fields are filled; if unsure, use best judgment based on workflow content.
        - Tags must be short keywords relevant to nodes or actions in the workflow.
        - Category must reflect the main purpose of the workflow.

        PROMPT;
    }
}