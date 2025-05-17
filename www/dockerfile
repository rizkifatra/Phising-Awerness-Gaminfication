FROM mattrayner/lamp:latest-1804

# Set default environment variables
ENV DB_NAME="PAG"
ENV DB_USER="admin" 
ENV DB_PASSWORD="ZYGR9TYQAf3U"

# Copy ALL website files (including subdirectories)
COPY . /var/www/html/

# Import SQL file to set up the database
# Create a setup script to import the database and configure MySQL
RUN echo '#!/bin/bash \n\
    # Wait for MySQL to start \n\
    until mysqladmin ping -h"localhost" --silent; do \n\
    sleep 1 \n\
    done \n\
    \n\
    # Create database and import schema \n\
    mysql -e "CREATE DATABASE IF NOT EXISTS PAG;" \n\
    mysql -e "CREATE USER IF NOT EXISTS \\"admin\\"@\\"localhost\\" IDENTIFIED BY \\"ZYGR9TYQAf3U\\";" \n\
    mysql -e "GRANT ALL PRIVILEGES ON PAG.* TO \\"admin\\"@\\"localhost\\";" \n\
    mysql -e "FLUSH PRIVILEGES;" \n\
    mysql PAG < /var/www/html/PAG.sql \n\
    \n\
    # Store database credentials for the application \n\
    echo "<?php \n\
    \\$DB_HOST = \\"localhost\\"; \n\
    \\$DB_NAME = \\"PAG\\"; \n\
    \\$DB_USER = \\"admin\\"; \n\
    \\$DB_PASS = \\"ZYGR9TYQAf3U\\"; \n\
    ?>" > /var/www/html/db_credentials.php \n\
    \n\
    # Fix permissions \n\
    chown -R www-data:www-data /var/www/html \n\
    ' > /startup.sh \
    && chmod +x /startup.sh

# Create a custom entrypoint script
RUN echo '#!/bin/bash \n\
    # Run our database setup script \n\
    /startup.sh & \n\
    \n\
    # Run the original entrypoint \n\
    exec /run.sh "$@" \n\
    ' > /custom-entrypoint.sh \
    && chmod +x /custom-entrypoint.sh

# Use our custom entrypoint
ENTRYPOINT ["/custom-entrypoint.sh"]

