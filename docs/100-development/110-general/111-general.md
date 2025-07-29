# General

## Programming Language and Framework

The application is written in [PHP](https://www.php.net/) and uses the [Laravel](https://laravel.com) framework.
The framework makes common problems such as routing, security, authentication easy to handle. After you learn how Laravel works, it becomes very pleasant to work with. For a detailed documentation, view the [Laravel documentation](https://laravel.com/docs/11.x).
The frontend is written in [Vue.js](https://vuejs.org/) using [TypeScript](https://www.typescriptlang.org/) and uses [Inertia.js](https://inertiajs.com/) as api-layer to connect the frontend and backend.
The application is styled with [Tailwind CSS](https://tailwindcss.com/) and uses [Vite](https://vitejs.dev/) as build tool.
We use [FormKit](https://formkit.com/) to create forms.

## Language

The code and documentation **should be written in English**.
This ensures that all developers and external contributors can understand the code and documentation.

## MVC-Pattern

Portals uses the widely used Model-View-Controller-Pattern.
Models are used to read, store and change objects on the database.
Views are used to display objects and let users interact with them.
Interactions are sent as requests to controllers. Controllers handle these requests, access data via models and respond with a view or another fitting response (such as JSON).

Models can be found in `app/Models`.
The corresponding tables are defined in the migration files under `database/migrations`.
Views can be found in `resources/js`.
Controllers can be found in `app/Http/Controllers`.
