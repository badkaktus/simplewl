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
cd /var/www/html
echo "Running migrations..."
php artisan migrate --force
