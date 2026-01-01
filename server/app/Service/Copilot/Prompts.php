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

    public static function getJudgementPrompt(array $workflow, string $userQuestion): string{
        $workflowJson = json_encode($workflow, JSON_PRETTY_PRINT);
        return <<<PROMPT
        You are a strict, adversarial evaluator of automation workflows.

        Your job is to determine whether the provided workflow truly and completely fulfills the user's intent.

        You are NOT allowed to assume missing steps, infer behavior, or give credit for partial matches.
        If something is not explicitly implemented in the workflow, it does NOT exist.

        Your evaluation must be pessimistic:
        If there is any ambiguity, missing logic, or mismatch, you must count it as an error.

        -------------------------
        HOW TO EVALUATE
        -------------------------

        1. Extract all requirements from the user's intent.
        Each condition, trigger, action, filter, branch, integration, and data flow is a separate requirement.

        2. Verify whether each requirement is explicitly implemented in the workflow:
        - Correct trigger
        - Correct services (apps, APIs, databases, etc)
        - Correct conditional logic
        - Correct branching behavior
        - Correct data flow between nodes
        - Correct outputs (messages, records, actions)

        3. Penalize heavily for:
        - Missing triggers
        - Missing filters or conditions
        - Missing branches
        - Nodes that are present but not wired correctly
        - Nodes that exist but have default or placeholder configuration
        - Logic that does not enforce the intent
        - Overly generic nodes that do not guarantee the requested behavior

        4. Do NOT reward:
        - "Looks about right"
        - "Probably works"
        - "The model likely intended"
        Only what is provable from the JSON counts.

        -------------------------
        SCORING
        -------------------------
        Score from 0.0 to 1.0

        Use this scale:
        1.0 = Fully and precisely implements every part of the user's intent  
        0.8 = Minor gaps, but core logic is correct  
        0.5 = Partially correct, major logic missing or wrong  
        0.2 = Barely related  
        0.0 = Does not match intent at all  

        If ANY critical requirement is missing, the score MUST be below 0.7.

        -------------------------
        OUTPUT FORMAT (STRICT)
        -------------------------
        Return ONLY this JSON object:

        {
        "errors": [
            "Clear description of each missing, incorrect, or unverified requirement"
        ],
        "suggested_improvements": [
            "Concrete changes needed to fix each error"
        ],
        "score": 0.0-1.0
        }

        No extra text.
        No markdown.
        No explanations.

        -------------------------
        WORKFLOW JSON
        -------------------------
        $workflowJson

        -------------------------
        USER INTENT
        -------------------------
        $userQuestion

        -------------------------
        OUTPUT
        -------------------------
        PROMPT;
    }



}
