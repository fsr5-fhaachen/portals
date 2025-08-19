# Backend

Portals uses the [Laravel](https://laravel.com) Framework, currently version 11.<br>
For the backend, the most important concepts are [Routing](https://laravel.com/docs/11.x/routing), [Middleware](https://laravel.com/docs/11.x/middleware) and [Controllers](https://laravel.com/docs/11.x/controllers).

To summarize: A user request first gets intercepted by middleware, which can for example check if a user is authenticated. The routing component then determines the action that should be performed based on the URL. Usually, the routing delegates to a function which is defined in a controller.<br>
Such functions read on the database via [models](https://laravel.com/docs/11.x/eloquent), perform server side logic, and then return a view or a JSON response.

For more detailed explanations as well as further concepts used in this project, refer to the official Laravel documentation.
