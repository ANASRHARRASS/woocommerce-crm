# AI Onboarding / How to help

What I want from the AI:
- Run a quick repo checklist (security, WP hooks, escaping, capability checks)
- Flag obvious bugs or missing checks
- Suggest WP/PHP best-practices and code improvements
- Propose test cases to add or strengthen existing tests
- Optionally provide patch/PR suggestions

How to act:
1. Read README.md and ARCHITECTURE.md.
2. Focus on these files first: src/Core.php, includes/rest-api.php, src/Integrations/*, src/Forms/*
3. Do not add secrets to the repo.
4. When suggesting changes, provide git patch or file diffs.

If you need more context, ask these questions:
- Which WordPress/WooCommerce/PHP versions are targeted?
- Should integrations be mocked in tests?