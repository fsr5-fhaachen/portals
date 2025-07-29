# Welcome to Portals

Portals is a web application for the Ersti-Woche and Ersti-Fahrt.<br>
Users can register for events. Events are divided into groups, into which users get distributed.<br>
Tutors can overview their groups and mark members as present or missing.

## Project layout

    .devcontainer/                          # DevContainer configuration files
    .github/                                # Github configuration files

    app/
        Helpers/                            # Helper code
            DivisionLogger.php              # Logger for group division
            GroupBalancedDivision.php       # Class for group division balanced by courses
            GroupCourseDivision.php         # Class for group division divided by courses
            GroupDivision.php               # General class for group division
            SlotAssignment.php              # Class to assign limited slots

        Http/
            Controllers/                    # Endpoints for routes, handle incoming requests
                Api/                        # Api-Controllers for communication via axios or with external services
            Middleware/                     # For inspecting and filtering requests

        Models/                             # Models are representations of objects in the database
        Providers/                          # Providers for configuring and booting the application

    bootstrap/                              # Providers for configuring and booting the application
    config/                                 # Several configuration files

    database/
        factories/                          # For creating randomized mass data
        migrations/                         # Changes to the database, ordered by timestamp
        seeders/                            # To fill an empty databse with usable data

    deploy/                                 # Configurations for server deployment
    docs/                                   # Documentation files
    lang/                                   # Translation files
    node_modules/                           # Javascript libraries
    public/                                 # Publicly accessible files like styling, images, sound effects

    resources/                              # Resources for displaying the website
        css/                                # General styling
        js/                                 # Javascript code
            components/                     # Reusable UI components
            composables/                    # Reusable UI logic
            layouts/                        # Different website layouts
            pages/                          # All website pages
            types/                          # Different reusable types
            app.js                          # Imports icons, executes inertia app
            bootstap.js                     # Sets up axios (for sending requests with javascript)
            formkit.config.ts               # Formkit configuration
            formkit.theme.ts                # Formkit styling

    routes/                                 # Definitions of routes to access the website and its functions
        api.php                             # Api-routes for communicating via axios or with external services
        console.php                         # Console commands
        web.php                             # Routes typically accessed via webbrowser

    storage/                                # Storage of different files
        logs/                               # Logs written with the Log-Facade

    tests/                                  # Tests for the website
        Feature/                            # Tests for website features
        Unit/                               # Tests for single units of code

    vendor/                                 # PHP libraries

    .env                                    # Environment variables for the server
    .env.example                            # Example env to copy

    composer.json                           # Required PHP-libraries
    composer.lock                           # Installed PHP-libraires

    docker-compose.sail.yaml                # Configuration for creating docker containers with laravel sail
    docker-compose.yaml                     # Configuration for creating docker containers
    Dockerfile                              # Steps to execute when creating a docker container

    mkdocs.yml                              # Docs configuration file

    package-lock.json                       # Installed javascript libraries
    package.json                            # Required javascript libraries

    tailwind.config.cjs                     # Configuration for tailwind CSS (styling)
    vite.config.js                          # Configuration for vite (combining and minimizing javascript and CSS files)

## How does portals work?

### Laravel

Portals is based on the Laravel web application framework. The framework makes common problems such as routing, security, authentication easy to handle. After you learn how Laravel works, it becomes very pleasant to work with. For a detailed documentation, view the [Laravel docs](https://laravel.com/docs/11.x).<br>
Portals does not use every Laravel feature. The most important features are explained in this documentation.

### MVC-Pattern

Portals uses the widely used Model-View-Controller-Pattern. Models are used to read, store and change objects on the database. Views are used to display objects and let users interact with them. Interactions are sent as requests to controllers. Controllers handle these requests, access data via models and respond with a view or another fitting response (such as JSON).

Models can be found in app/Models. The corresponding tables are defined in the migration files under database/migrations.<br>
Views can be found in resources/js.<br>
Controllers can be found in app/Http/Controllers.

## Further information

Refer to the [Readme "Install" section](../README.md#install) if you want to setup the project. To run the project, follow the "Usage" section below.<br>
For more details on how requests are handled, read [Requests](requests.md).<br>
To learn how models are accessed, read [Data Access](dataAccess.md).<br>
Read [User Interaction](userInteraction.md) to learn how views work.
