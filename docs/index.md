# Welcome to Portals

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
