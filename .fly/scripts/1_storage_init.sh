FOLDER=/var/www/html/storage/database
if [ ! -d "$FOLDER" ]; then
    echo "$FOLDER is not a directory, initializing database"
    mkdir /var/www/html/storage/database
    touch /var/www/html/storage/database/database.sqlite
fi

if [ -z "$RELEASE_COMMAND" ]; then
    # We are NOT in a temporary VM, run as normal...
    cd /var/www/html
    php artisan migrate --force
else
    # We are in the temporary VM created
    # for release commands...
fi
