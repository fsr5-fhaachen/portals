# Branch Structure

## main

The `main` branch is the primary branch for the project.
It contains the latest stable code that is ready for production and can be used to create releases.
**It should not be modified directly.**
The main branch should only be changed through a [Pull Request](./115-pull-requests.md) (PR) from the `dev` branch.

## dev

The `dev` branch is the development branch where all new features and changes are collected.
When the code in the `dev` branch is stable and ready for production, it can be merged into the `main` branch using a [Pull Request](./115-pull-requests.md).

## Feature Branches

Feature branches are used for developing new features, bug fixes, or other changes.
These branches **must be created from the `dev` branch** and should be named according to the feature or issue being worked on.

Example:

- `feat/new-login-system`
- `fix/login-error`

If you want you can add the issue number to the branch name, e.g. `feat/new-login-system-#123` or `fix/login-error-#456`.

<!-- TODO: merge info from CONTRIBUTING.md -->
