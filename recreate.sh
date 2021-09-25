php artisan down
php artisan migrate:fresh

TUTOREN=tutoren.csv
if test -f "$TUTOREN"; then
    echo "Found $TUTOREN"
    php artisan db:seed --class=TutorenSeeder
fi

ROUTES=routes.csv
if test -f "$ROUTES"; then
    echo "Found $ROUTES"
    php artisan db:seed --class=RoutesSeeder
fi

NAMES=names.csv
if test -f "$NAMES"; then
    echo "Found $NAMES"
    php artisan db:seed --class=NamesSeeder
fi