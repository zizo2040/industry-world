# AI Assistant Module

This directory contains all backend code for the AI assistant embedded in the website.

## Structure

```
ai-assistant/
├── config/
│   └── assistant.php        # All assistant settings (API keys, model, limits)
├── services/
│   ├── ApiClient.php        # HTTP layer: cURL requests, error handling, response parsing
│   └── AssistantService.php # Core service: session, validation, rate limiting, chat()
├── handlers/
│   └── chat.php             # AJAX endpoint called by the frontend
└── data/                    # Reserved for knowledge base files (Phase 8)
```

## Phases

| Phase | Status | Description |
|-------|--------|-------------|
| 1 | ✅ Done | Project structure & basic files |
| 2 | ✅ Done | API connection layer (ApiClient + real responses) |
| 3 | ✅ Done | Full conversation system & history restoration |
| 4 | ✅ Done | Integration with site pages & responsive mobile full-screen |
| 5 | ✅ Done | Admin control panel settings & API connection test |
| 6 | ✅ Done | Assistant System Prompt & Brand Identity |
| 7 | ⏳ Pending | Navigation context & site links integration |
| ... | ... | ... |

## Endpoint

`POST /ai-assistant/handlers/chat.php`

### Actions

| Action | Description |
|--------|-------------|
| `send_message` | Send a user message, get real AI reply |
| `clear_session` | Clear conversation history |
| `ping` | Health check (returns api_key_set, model, endpoint) |
