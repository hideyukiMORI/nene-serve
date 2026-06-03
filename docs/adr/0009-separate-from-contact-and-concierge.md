# ADR 0009: Separate Domain from Contact and Concierge

## Status

accepted

## Decision

### Serve owns

- Placements, creatives, delivery plans, imp/click analytics, `serve.js`

### Serve does not own

- **Contact:** forms, `submission`, `embed.js`, inbox notifications for inquiries
- **Concierge:** scenarios, sessions, conversational actions

### Allowed integration

- Same publisher site may load `serve.js` and `embed.js` independently
- Concierge terminal action may call Serve **only** to log a conversion pixel — optional, documented separately; does not create Contact submissions

### Forbidden

- Unified "marketing inbox" table mixing submissions and clicks
- Contact MCP tools in Serve repo

## Related

- [`../explanation/scope-contract.md`](../explanation/scope-contract.md)
