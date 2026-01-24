<h1 align="center"><strong>FlowPilot</strong></h1>

<p align="center">
  <img src="./client/src/assets/header-logo-removebg-preview.png" alt="Copilot Logo" width="160" />
</p>

<p>
  <strong>
    Flowpilot is a one of a kind n8n workflow generator.Generation of production ready, deployable and testable workflows is what Flowpilot strives in.
  </strong>
</p>

<h2 align="center"><strong>Architecture</strong></h2>

<table align="center">
  <tr>
    <td align="center" width="50%">
      <img 
        src="https://upload.wikimedia.org/wikipedia/commons/a/a7/React-icon.svg" 
        alt="React Logo" 
        width="120"
      />
      <p><strong>Frontend</strong></p>
      <p>React</p>
    </td>
    <td align="center" width="50%">
        <img 
        src="https://upload.wikimedia.org/wikipedia/commons/9/9a/Laravel.svg" 
        alt="Laravel Logo" 
        width="120"
      />
      <p><strong>Backend</strong></p>
      <p>Laravel</p>
    </td>
  </tr>
</table>
<h3 align="center"><strong>Communication Flow</strong></h3>

<p align="center">
  <img 
    src="./client/src/assets/README-Diagrams/High-LevelWorkflow.png" 
    alt="FlowPilot Communication Flow Diagram"
    width="800"
  />
</p>

<p align="center">
  <em>
    High-level data flow between the frontend, backend and database.
  </em>
</p>

<p align="center">
  <img 
    src="./client/src/assets/README-Diagrams/GeneratorFlow.png" 
    alt="FlowPilot Communication Flow Diagram"
    width="800"
  />
</p>

<p align="center">
  <em>
    n8n Generation workflow between the frontend, backend, generation module, LLM, vector-db and an open SSE connection.
  </em>
</p>


<h2 align="center"><strong>System Design</strong></h2>


<h3>Sequence Diagram</h3>
<table>
  <tr>
    <td align="center" width="100%">
      <img 
        src="./client/src/assets/README-Diagrams/SD-1.png" 
        alt="React Logo" 
        width="120"
      />
      <p><strong>Frontend</strong></p>
      <p>React</p>
    </td>
    <td align="center" width="100%">
        <img 
        src="./client/src/assets/README-Diagrams/SD-2.png" 
        alt="Laravel Logo" 
        width="120"
      />
      <p><strong>Backend</strong></p>
      <p>Laravel</p>
    </td>
  </tr>
    <tr>
    <td align="center" width="100%">
      <img 
        src="./client/src/assets/README-Diagrams/SD-3.png" 
        alt="React Logo" 
        width="120"
      />
      <p><strong>Frontend</strong></p>
      <p>React</p>
    </td>
    <td align="center" width="100%">
        <img 
        src="./client/src/assets/README-Diagrams/SD-4.png" 
        alt="Laravel Logo" 
        width="120"
      />
      <p><strong>Backend</strong></p>
      <p>Laravel</p>
    </td>
  </tr>
</table>
<h3>ER Diagram</h3>
<p align="center">
  <img 
    src="./client/src/assets/README-Diagrams/ER-Diagram.png" 
    alt="FlowPilot Communication Flow Diagram"
    width="800"
  />
</p>