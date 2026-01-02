<?php

namespace App\Service\Copilot;

class Prompts{

    public static function getAnalysisPrompt($question){
       return <<<PROMPT
        You are a JSON extraction engine for an n8n workflow copilot.

        You must output a JSON object that strictly follows this schema:

        {
        "intent": string,              // one concise sentence describing what the user wants
        "nodes": string[],             // list of services or nodes (capitalized, no spaces around words)
        "category": "automation" | "integration" | "sync" | "data" | "ai" | "marketing" | "devops" | "finance",
        "min_nodes": number            // integer >= 1
        }

        Rules:
        - "intent" must be a full sentence.
        - "nodes" must only contain names of services or n8n nodes (e.g. "Slack", "Stripe", "Notion", "EmailSend", "Acuity").
        - If no specific service is mentioned, infer the most likely ones.
        - "min_nodes" must be realistic based on the intent.
        - Never return null values.
        - Never return empty arrays.
        - Do not include any keys outside the schema.
        - Do not include comments.
        - Do not include markdown.

        User question:
        "$question"

        Return only valid JSON.
        PROMPT;
    }

    public static function getAnswerPrompt(){
        return  <<<PROMPT
        You are an expert n8n workflow architect.

        You understand:
        - Triggers
        - Credentials
        - Node connections
        - Error handling
        - Webhooks
        - Production ready automation design

        You must:
        1. Analyze the user's goal
        2. Compare given workflows
        3. Pick or combine the best
        4. Output a valid n8n workflow JSON
        5. Never hallucinate nodes
        6. Never invent credentials
        7. Never output explanations inside the JSON
        PROMPT;
    }

    public static function getWorkflowGenerationPrompt(string $question, string $context){
        return <<<PROMPT
        USER GOAL:
        $question

        You are given real exported n8n workflows below.

        Your task:
        1. Understand the user's goal
        2. Compare the workflows
        3. Select, merge or modify them
        4. Output ONE fully importable n8n workflow

        =====================
        CRITICAL FORMAT RULES
        =====================

        You MUST output a full n8n workflow object.

        The JSON MUST have this exact top-level structure:

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
        - "nodes" must be an array of n8n nodes
        - "connections" must match node names exactly
        - Triggers must exist
        - Use only nodes that appear in the provided workflows
        - Keep credentials names EXACTLY as in the examples
        - DO NOT omit settings, staticData, or meta
        - Do NOT output only nodes or only connections
        - Do NOT wrap JSON in markdown
        - Do NOT add any explanation
        - Output JSON only

        =====================
        WORKFLOWS/NODES/SCHEMA
        =====================
        $context

        =====================
        OUTPUT
        =====================
        Return ONLY the final JSON object described above.
        PROMPT;
    }

    public static function getWorkflowGenerationSystemPrompt(): string{
        return "You are an assistant that helps generate and repair n8n workflows. Provide clear, valid JSON when requested, preserve credentials when possible, and be concise.";
    }

    public static function getRepairPrompt(string $workflowJson, string $errorsJson): string{
        return <<<PROMPT
            You are an expert n8n workflow repair assistant.

            You are given an n8n workflow JSON and a list of errors encountered when trying to run it.
            (You may be given a list of improvements as well.)

            Your task:
            1. Analyze the errors
            2. Identify the root causes in the workflow
            3. Modify the workflow to fix the errors
            4. Output a valid n8n workflow JSON that resolves all issues
            5. ONLY fix the listed issues.
            6. Do NOT change any other logic, nodes, or connections.
            7. Do NOT refactor or simplify unless explicitly requested.


            RULES:
            - Preserve existing credentials names
            - Maintain valid n8n format
            - Ensure triggers exist
            - Ensure connections are correct
            - Include error handling if missing

            WORKFLOW JSON:
            $workflowJson

            ERRORS:
            $errorsJson

            OUTPUT:
            Return ONLY a valid n8n JSON.
            No explanations.
            No markdown.
            PROMPT;
    }

    public static function getJudgementPrompt(array $workflow, string $userQuestion): string {
        $workflowJson = json_encode($workflow, JSON_PRETTY_PRINT);

        return <<<PROMPT
        You are a strict, adversarial evaluator of n8n automation workflows.

        Your job is to determine whether the workflow correctly implements the USER'S INTENT in terms of:
        - Triggers
        - Logic
        - Branching
        - Data flow
        - Integrations
        - Actions

        You are evaluating LOGIC and STRUCTURE — NOT credentials, auth, or user-specific runtime data.

        ────────────────────────────────────────────
        CRITICAL SCOPE RULE
        ────────────────────────────────────────────

        You MUST IGNORE all credential, identity, and runtime-binding concerns, including:
        - OAuth credentials
        - API keys
        - Account IDs
        - User IDs
        - Slack channel IDs
        - Email addresses
        - Webhook URLs
        - Placeholder credential values
        - "your-api-key", "your-channel", "user-id", etc

        These are injected later by the system or the end user.

        They are NEVER valid errors.

        DO NOT mention them.
        DO NOT penalize them.
        DO NOT include them in errors.
        DO NOT reduce score for them.

        ────────────────────────────────────────────
        HOW TO EVALUATE
        ────────────────────────────────────────────

        1. Extract all functional requirements from the user's intent.
        Each trigger, condition, filter, branch, API call, transformation, and output is a requirement.

        2. Verify that each requirement is explicitly implemented in the workflow:
        - Correct trigger exists
        - Correct integrations are used
        - Correct conditional logic exists
        - Correct branching exists
        - Correct data flows between nodes
        - Correct actions are performed

        3. Penalize heavily for:
        - Missing triggers
        - Missing filters or conditions
        - Missing branches
        - Nodes that exist but are not wired
        - Logic that does not enforce the intent
        - Required steps that are absent
        - Overly generic or noop logic

        4. DO NOT penalize:
        - Credential placeholders
        - Hardcoded IDs
        - Channel names
        - OAuth bindings
        - Runtime secrets
        - User-specific identifiers

        Only LOGIC and DATA FLOW matter.

        ────────────────────────────────────────────
        SCORING
        ────────────────────────────────────────────

        Score from 0.0 to 1.0

        1.0 = Fully and precisely implements every part of the user's intent  
        0.8 = Minor gaps, but core logic is correct  
        0.5 = Partially correct, major logic missing or wrong  
        0.2 = Barely related  
        0.0 = Does not match intent at all  

        If ANY critical functional requirement is missing, the score MUST be below 0.7.

        ────────────────────────────────────────────
        SEVERITY
        ────────────────────────────────────────────

        Each error must have a severity:

        - critical → workflow fails the user's intent
        - major → core logic broken
        - minor → inefficiency, polish, or safety issue

        Never use severity for credential or identity issues.

        ────────────────────────────────────────────
        OUTPUT FORMAT (STRICT)
        ────────────────────────────────────────────

        Return ONLY this JSON object:

        {
        "errors": [
            {
            "message": "error description",
            "severity": "critical"
            }
        ],
        "suggested_improvements": [
            {
            "message": "improvement description",
            "severity": "major"
            }
        ],
        "score": 0.0
        }

        No extra text.
        No markdown.
        No explanations.

        ────────────────────────────────────────────
        WORKFLOW JSON
        ────────────────────────────────────────────
        $workflowJson

        ────────────────────────────────────────────
        USER INTENT
        ────────────────────────────────────────────
        $userQuestion

        ────────────────────────────────────────────
        OUTPUT
        ────────────────────────────────────────────
        PROMPT;
    }

    public static function getRepairWorkflowLogic(string $badJson, string $errorsJson , string $totalPoints): string{
        return <<<PROMPT
        You are an expert n8n workflow engineer and automated repair system.

        Your job is to take a BROKEN workflow and a list of FAILURES, and produce a FIXED workflow
        that satisfies ALL failures.

        You are not allowed to explain.
        You are not allowed to output anything except valid JSON.

        This is not a rewrite — this is a targeted repair.

        ────────────────────────────────────────────
        BROKEN WORKFLOW (GROUND TRUTH)
        ────────────────────────────────────────────
        $badJson

        ────────────────────────────────────────────
        FAILURES (MUST FIX ALL)
        ────────────────────────────────────────────
        $errorsJson

        ────────────────────────────────────────────
        WHAT YOU CAN USE
        ────────────────────────────────────────────
        $totalPoints

        ────────────────────────────────────────────
        STRICT RULES
        ────────────────────────────────────────────

        1. You MUST preserve all parts of the workflow that are not related to the failures.
        Do NOT delete nodes unless a failure explicitly says they are wrong.

        2. You MUST make the MINIMAL number of changes required to satisfy the failures.

        3. You MUST ensure:
        - All required triggers exist
        - All required nodes exist
        - All required branches exist
        - All required filters exist
        - All required connections exist
        - Data flows between nodes correctly -- VERY IMPORTANT

        4. You MUST NOT:
        - Add placeholder values
        - Add TODOs
        - Leave parameters empty
        - Add generic nodes that do not guarantee the behavior

        5. If a failure says something is missing, you MUST:
        - Add the correct node
        - Connect it correctly
        - Configure it correctly

        6. If a failure says something is wrong, you MUST:
        - Replace or reconfigure that exact part
        - Without breaking unrelated logic

        7. The result MUST be a COMPLETE, VALID n8n workflow JSON
        using the SAME format as the input.

        8. You MUST ensure that every node is reachable from a trigger.

        9. You MUST ensure that no required logic is implied — it must be explicit in JSON.

        ────────────────────────────────────────────
        MENTAL MODEL (YOU MUST FOLLOW THIS)
        ────────────────────────────────────────────

        You must behave like a compiler performing a repair pass:

        Step 1: Read the failures.
        Step 2: For each failure, identify the exact missing or broken node, connection, or parameter.
        Step 3: Apply ONLY the required structural edits to fix it.
        Step 4: Revalidate that all failures are now impossible.
        Step 5: Output the corrected JSON.

        Do NOT guess.
        Do NOT be creative.
        Do NOT optimize.
        Only repair what is required.

        ────────────────────────────────────────────
        OUTPUT FORMAT
        ────────────────────────────────────────────

        Return ONLY the corrected workflow JSON.

        No text.
        No markdown.
        No comments.
        No explanations.
        No trailing commas.

        The output must be directly parsable by json_decode().

        ────────────────────────────────────────────
        BEGIN REPAIR
        ────────────────────────────────────────────
        PROMPT;
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




}