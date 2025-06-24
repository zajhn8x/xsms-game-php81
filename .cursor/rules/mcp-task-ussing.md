## Using the Task Manager

### Creating a New Project

1. Create a task file for your project:
```python
await mcp.create_task_file(project_name="my-project")
```

2. Add tasks to your project:
```python
await mcp.add_task(
    project_name="my-project",
    title="Setup Development Environment",
    description="Configure the development environment with required tools",
    subtasks=[
        "Install dependencies",
        "Configure linters",
        "Set up testing framework"
    ]
)
```

3. Parse a PRD to create tasks automatically:
```python
await mcp.parse_prd(
    project_name="my-project",
    prd_content="# Your PRD content..."
)
```

### Managing Tasks

1. Update task status:
```python
await mcp.update_task_status(
    project_name="my-project",
    task_title="Setup Development Environment",
    subtask_title="Install dependencies",
    status="done"
)
```

2. Get the next task to work on:
```python
next_task = await mcp.get_next_task(project_name="my-project")
```

3. Expand a task into subtasks:
```python
await mcp.expand_task(
    project_name="my-project",
    task_title="Implement Authentication"
)
```

### Development Workflow

1. Generate a file template for a task:
```python
await mcp.generate_task_file(
    project_name="my-project",
    task_title="User Authentication"
)
```

2. Get task complexity estimate:
```python
complexity = await mcp.estimate_task_complexity(
    project_name="my-project",
    task_title="User Authentication"
)
```

3. Get suggestions for next actions:
```python
suggestions = await mcp.suggest_next_actions(
    project_name="my-project",
    task_title="User Authentication"
)
```

## Integration with MCP Clients

### SSE Configuration

To connect to the server using SSE transport, use this configuration:

```json
{
  "mcpServers": {
    "task-manager": {
      "transport": "sse",
      "url": "http://localhost:8086/sse"
    }
  }
}
```