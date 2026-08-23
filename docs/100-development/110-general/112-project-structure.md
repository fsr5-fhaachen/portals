# Project Structure

To understand the structure of the project, it is helpful to know where to find certain files and folders. The following is a brief overview of the most important directories and their contents.

!!! warning "Laravel Structure"

    Because we dont want to repeat the Laravel documentation, this section only contains a brief overview of the project structure and all "special" files in our case. For the full documentation of the Laravel structure, refer to the [Laravel documentation](https://laravel.com/docs/11.x/structure).

### `.devcontainer/`

Contains configuration files for the [DevContainer](https://code.visualstudio.com/docs/devcontainers/containers) used to develop the project in a consistent environment using docker containers. This is useful to ensure that all developers use the same versions of tools and libraries.
The devcontainer can be used with [Laravel Sail](https://laravel.com/docs/11.x/sail).

### `.github/`

Contains configuration files for GitHub, such as workflows for continuous integration (CI / [GitHub Actions](https://github.com/features/actions)), issue templates and so on.

### `deploy/`

Contains scripts and configuration files for deploying the project to a server.
Please refer to the [Deployment documentation](../200-deployment/) for more information.

### `docs/`

Contains this documentation.
It is written in [Markdown](https://www.markdownguide.org/).
The documentation is built using [MkDocs Material](https://squidfunk.github.io/mkdocs-material/), which is a static site generator that converts Markdown files into a website.

### `resources/js/components/`

Contains reusable UI components that can be used throughout the pages (views).
Refer to the [Vue.js documentation](https://vuejs.org/guide/essentials/component-basics) for more information on how to create and use components.

### `resources/js/composables/`

Contains reusable UI logic that can be used in components or pages.
Refer to the [Vue.js documentation](https://vuejs.org/guide/reusability/composables) for more information on how to create and use composables.

### `resources/js/layouts/`

Contains different website layouts that can be used to structure the pages.
Layouts are used to define the overall structure of a page, such as the header, footer and sidebar.

### `resources/js/pages/`

Contains the actual pages (views) of the website.
A page doesnt have to contain all the content of the page, it will be combined with the layout and components to create the final page.

### `resources/js/types/`

Contains different reusable types that can be used throughout the project.
This is useful to define the structure of objects and ensure type safety in TypeScript.
The types are **auto generated** using [scrumble-nl/laravel-model-ts-type](https://github.com/scrumble-nl/laravel-model-ts-type).

<!-- TODO: ref to docs where type generation is explained -->

### `resources/js/formkit.*.ts`

Contains the configuration and styling for [FormKit](https://formkit.com/).

### `docker-compose.sail.yaml`

Contains the docker compose configuration for the development environment using [Laravel Sail](https://laravel.com/docs/11.x/sail).

### `docker-compose.yaml`

Contains the docker compose configuration for the production environment.

### `Dockerfile`

Contains the steps to execute when creating a docker container for the project.
