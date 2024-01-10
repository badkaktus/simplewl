# .fly/scripts/1_storage_init.sh

# Add this below the storage folder initialization snippet
FOLDER=/var/www/html/storage/database
DATABASE_FILE=$FOLDER/database.sqlite

if [ ! -d "$FOLDER" ]; then
    echo "$FOLDER is not a directory, initializing database"
    mkdir "$FOLDER"
    touch "$DATABASE_FILE"
elif [ ! -f "$DATABASE_FILE" ]; then
    echo "Creating $DATABASE_FILE in the existing directory"
    touch "$DATABASE_FILE"
fi

if [ -z "$RELEASE_COMMAND" ]; then
    # We are NOT in a temporary VM, run as normal...
    cd /var/www/html
    php artisan migrate --force
else
    # We are in the temporary VM created
    # for release commands...
fi
