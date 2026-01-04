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

    public static function getPlanRepairPrompt($question , $context , $badPlan , $errors){
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
        {json_encode($badPlan, JSON_PRETTY_PRINT)}

        VALIDATION ERRORS:
        {implode("\n", $errors)}

        user's goal:
        $question

        AVAILABLE BUILDING BLOCKS:
        $context

        Please generate a corrected plan following the rules from the system prompt.
        USER;
    }

    public static function getWorkflowBuildingPrompt($question , $plan , $context){
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
        {json_encode($plan, JSON_PRETTY_PRINT)}

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

    


    public static function getCompleteDataFlowValidationPrompt(array $workflow , string $question , array $totalPoints){
        $encodedWorkflow = json_encode($workflow);
        $encodedPoints = json_encode($totalPoints);

        return <<<PROMPT
        You are an N8N DATA FLOW TYPE CHECKER.

        You must validate this workflow as if you were a compiler.

        Your job is to prove whether data used in every node actually exists, is correctly typed, and is produced by an upstream connected node.

        You are NOT allowed to guess.
        You are NOT allowed to assume.
        You must prove.

        --------------------------------
        USER INTENT
        --------------------------------
        {$question}

        --------------------------------
        WORKFLOW
        --------------------------------
        {$encodedWorkflow}

        --------------------------------
        NODE SCHEMAS (AUTHORITATIVE)
        --------------------------------
        {$encodedPoints}

        --------------------------------
        YOUR TASK
        --------------------------------

        You must perform ALL of the following:

        ### 1.BUILD THE DATA GRAPH
        For every node in execution order:
        - List every field it outputs
        - The type of each field (string, number, boolean, object, array, binary)
        - Which node produced it

        Use ONLY the node schemas + parameters.

        ---

        ### 2.TRACE EVERY REFERENCE
        For every expression like:
        {{\$json.email}}
        {{\$node["X"].json.id}}
        {{\$binary.data}}

        You must verify:
        - Does this field exist?
        - Is it produced by a connected upstream node?
        - Is the type correct for the destination field?
        - Is the path valid?

        If ANY of those fail → ERROR

        ---

        ### 3.ENFORCE SCHEMAS
        For every node:
        - Required fields must exist
        - Fields must be of correct type
        - Binary vs JSON must be correct
        - Enum values must be valid
        - Expressions must reference real fields

        ---

        ### 4.ENFORCE INTENT
        Check that the actual data path matches the user intent.

        Example:
        If user asked:
        "Create file from Google Sheet rows"

        Then:
        Google Sheets must produce row data
        That data must flow into the Google Drive node
        The file content must be built from sheet data

        If not → INTENT_MISMATCH

        ---

        ### 5.ABSOLUTELY FORBIDDEN
        You may NOT:
        - Assume a field exists
        - Assume a schema
        - Assume default values
        - Assume emails exist
        - Assume content exists

        If not proven → ERROR

        ### 6.BUILD THE EXECUTION GRAPH

        You must map every possible execution path:
        - Starting from triggers
        - Through IF, SWITCH, SPLIT, MERGE, LOOPS
        - Until termination

        For every node output index:
        - Verify it is either connected OR explicitly allowed to be empty

        ---

        ### 7.BRANCH COVERAGE

        For every conditional node:
        - All branches must be handled
        - True and False must lead to something
        - No branch may drop data unless user intent explicitly says so

        If any branch leads nowhere → ERROR (DATA_LOSS)

        ---

        ### 8.LOOP SAFETY

        If a loop exists:
        - Data must either progress toward termination
        - Or explicitly exit

        Infinite loops or closed cycles without state change → ERROR (INFINITE_LOOP)

        ---

        ### 9.TERMINAL VALIDATION

        For every possible path:
        Verify:
        - If intent requires output (file, email, API call), that output happens
        - No path silently ends without fulfilling intent

        Example:
        If user requested:
        "Create files OR send emails"
        Then:
        Every path must do one of those.

        If any path does nothing → INTENT_MISMATCH

        ---

        # ERROR FORMAT

        You must return an error if ANY failure exists.

        Each error must be:

        {
        "error_code": "MISSING_FIELD | INVALID_TYPE | BROKEN_REFERENCE | WRONG_SOURCE | SCHEMA_VIOLATION | INTENT_MISMATCH",
        "node": "node name",
        "field": "field or expression",
        "description": "what is wrong",
        "suggested_improvement": "exactly what must be fixed"
        }

        ---

        # SCORING

        Score is NOT subjective.

        Start at 1.0  
        Subtract:
        -0.3 per critical data error  
        -0.2 per broken reference  
        -0.2 per schema violation  
        -0.3 if intent broken  

        Minimum 0.0

        ---

        # OUTPUT (STRICT)

        Return ONLY valid JSON:

        {
        "score": <number>,
        "workflow": <original workflow>,
        "errors": [ ... ]
        }

        If ANY error exists:
        score MUST be < 1.0

        No markdown.  
        No commentary.  
        No explanations.

        PROMPT;
    }

    public static function getRepairWorkflowDataFlowLogic(string $badJson, string $errorsJson, string $totalPointsJson){
        return <<<PROMPT
        You are an N8N DATA FLOW REPAIR ENGINE.

        Your ONLY task is to fix data-flow, parameter, and schema errors in an existing n8n workflow.

        You are NOT allowed to:
        - Change the user intent
        - Redesign the workflow
        - Add new nodes
        - Remove nodes
        - Change control flow
        - Change node types
        - Invent schemas
        - Add new credentials

        You may ONLY:
        - Fix broken expressions
        - Fix missing or wrong fields
        - Correct invalid paths
        - Add required parameters that exist in the schema
        - Fix wrong field types (binary vs json)
        - Move data into correct fields
        - Fix broken references between already-connected nodes

        You are repairing a compiler error, not optimizing.

        --------------------------------
        WORKFLOW (BROKEN)
        --------------------------------
        $badJson

        --------------------------------
        ERRORS (AUTHORITATIVE)
        --------------------------------
        $errorsJson

        --------------------------------
        ALLOWED NODE SCHEMAS
        --------------------------------
        $totalPointsJson

        --------------------------------
        STRICT RULES
        --------------------------------

        1. You must fix ONLY the errors provided.
        2. You must NOT fix anything that is not explicitly listed.
        3. Every error must be resolved in the output.
        4. If you cannot fix an error because the workflow is structurally impossible, return the original workflow unchanged.
        5. You must preserve:
        - Node names
        - Node IDs
        - Connections
        - Trigger logic
        - Control flow

        6. If a field is missing:
        - You must fill it using upstream data if available
        - Otherwise use a placeholder like: "REQUIRED_USER_VALUE"

        7. If a reference is broken:
        - You must repoint it to a valid upstream field
        - You must not create fake data

        8. If binary/json mismatches exist:
        - You must correct the field type without changing the node

        --------------------------------
        WHAT YOU MUST PRODUCE
        --------------------------------

        Return ONLY the corrected workflow JSON.

        Do NOT:
        - Add commentary
        - Add markdown
        - Explain anything
        - Return errors
        - Return scores

        The output MUST be valid n8n workflow JSON.

        Your goal is to make the workflow pass data-flow validation with score = 1.0.

        PROMPT;
    }

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