# Commits

We are using [Conventional Commits](https://www.conventionalcommits.org/en/v1.0.0/) for our commits.
Every commit must follow this pattern.

The commit message is split into different parts:

- **Commit type**. (e.g. `feat`, `fix`, `chore`, `ci`, `test`, ...)
- **Changed module/component** in brackets. If you want to target a sub module/component you can add it after the module/component and separate it with a slash `/`.
  Example: `(devcontainer)` or `(tests/e2e)`. If no module/component fits, you can leave this part out completely.
  Please don't use `*` as module/component or add empty brackets `()`.
- **A colon `:`** to separate the type and module/component from the commit message.
- **Commit message**. All messages must be in english and follow the conventions of the conventional commits standard.
  They must be written in the imperative, present tense. Example: "change" not "changed" nor "changes". The first letter should not be capitalized.

The resulting commit message should look like this:

- `feat(devcontainer): add documentation`
- `fix: remove typo`
