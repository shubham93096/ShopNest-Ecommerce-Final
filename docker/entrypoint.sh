#!/bin/bash
set -e

echo "===================================================="
echo "ShopNest - Initializing Container Environment"
echo "===================================================="

TARGET_DB_HOST="${DB_HOST:-localhost}"

if [ "$TARGET_DB_HOST" = "localhost" ] || [ "$TARGET_DB_HOST" = "127.0.0.1" ]; then
    echo "Local database mode selected (host: $TARGET_DB_HOST)."
    echo "Starting embedded MariaDB / MySQL server..."
    
    mkdir -p /var/run/mysqld /var/lib/mysql /tmp
    chown -R mysql:mysql /var/lib/mysql /var/run/mysqld 2>/dev/null || true
    
    # Initialize data dir if empty
    if [ ! -d "/var/lib/mysql/mysql" ]; then
        echo "Running mariadb-install-db..."
        mariadb-install-db --user=mysql --datadir=/var/lib/mysql >/dev/null 2>&1 || mysql_install_db --user=mysql --datadir=/var/lib/mysql >/dev/null 2>&1 || true
    fi

    # Start service
    if [ -x /etc/init.d/mariadb ]; then
        /etc/init.d/mariadb start || service mariadb start || mysqld_safe &
    elif [ -x /etc/init.d/mysql ]; then
        /etc/init.d/mysql start || service mysql start || mysqld_safe &
    else
        mysqld_safe &
    fi

    echo "Waiting for MySQL service to become ready..."
    MAX_TRIES=30
    TRIES=0
    until mysqladmin ping --silent || [ $TRIES -ge $MAX_TRIES ]; do
        sleep 1
        TRIES=$((TRIES + 1))
    done

    # Ensure socket symlink for PHP default /tmp/mysql.sock
    ln -sf /var/run/mysqld/mysqld.sock /tmp/mysql.sock 2>/dev/null || true
    chmod 777 /var/run/mysqld/mysqld.sock 2>/dev/null || true

    if [ $TRIES -ge $MAX_TRIES ]; then
        echo "Warning: MySQL ping timed out, continuing anyway..."
    else
        echo "MySQL service is running and healthy."
    fi

    if [ ! -f /var/lib/mysql/.db_initialized ]; then
        echo "First run detected! Setting up 'aws_ecommerce' database..."
        
        mysql -e "CREATE DATABASE IF NOT EXISTS \`aws_ecommerce\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
        
        mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED VIA mysql_native_password USING PASSWORD('');" 2>/dev/null || \
        mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED BY '';" 2>/dev/null || true
        
        mysql -e "CREATE USER IF NOT EXISTS 'root'@'127.0.0.1' IDENTIFIED BY '';" 2>/dev/null || true
        mysql -e "GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost' WITH GRANT OPTION;" 2>/dev/null || true
        mysql -e "GRANT ALL PRIVILEGES ON *.* TO 'root'@'127.0.0.1' WITH GRANT OPTION;" 2>/dev/null || true
        
        mysql -e "CREATE USER IF NOT EXISTS 'shopnest'@'%' IDENTIFIED BY 'shopnest_pass';" 2>/dev/null || true
        mysql -e "CREATE USER IF NOT EXISTS 'shopnest'@'localhost' IDENTIFIED BY 'shopnest_pass';" 2>/dev/null || true
        mysql -e "CREATE USER IF NOT EXISTS 'shopnest'@'127.0.0.1' IDENTIFIED BY 'shopnest_pass';" 2>/dev/null || true
        mysql -e "GRANT ALL PRIVILEGES ON \`aws_ecommerce\`.* TO 'shopnest'@'%';" 2>/dev/null || true
        mysql -e "GRANT ALL PRIVILEGES ON \`aws_ecommerce\`.* TO 'shopnest'@'localhost';" 2>/dev/null || true
        mysql -e "GRANT ALL PRIVILEGES ON \`aws_ecommerce\`.* TO 'shopnest'@'127.0.0.1';" 2>/dev/null || true
        mysql -e "FLUSH PRIVILEGES;" 2>/dev/null || true

        if [ -f /var/www/html/database/ecommerce.sql ]; then
            echo "Importing schema and sample catalog from database/ecommerce.sql..."
            mysql aws_ecommerce < /var/www/html/database/ecommerce.sql
            echo "Database schema imported successfully!"
        fi

        touch /var/lib/mysql/.db_initialized
        echo "Database initialization complete!"
    else
        echo "Database already initialized, skipping import."
    fi
else
    echo "External database configured (host: $TARGET_DB_HOST)."
    
    echo "Checking connection to remote MySQL database $TARGET_DB_HOST..."
    REMOTE_USER="${DB_USER:-root}"
    REMOTE_PASS="${DB_PASS:-${MYSQL_ROOT_PASSWORD:-}}"
    REMOTE_DB="${DB_NAME:-aws_ecommerce}"
    
    MAX_TRIES=15
    TRIES=0
    until mysql -h "$TARGET_DB_HOST" -u "$REMOTE_USER" -p"$REMOTE_PASS" -e "SELECT 1;" >/dev/null 2>&1 || [ $TRIES -ge $MAX_TRIES ]; do
        sleep 1
        TRIES=$((TRIES + 1))
        if [ $((TRIES % 3)) -eq 0 ]; then
            echo "Waiting for remote MySQL database $TARGET_DB_HOST ($TRIES/$MAX_TRIES)..."
        fi
    done
    
    if [ $TRIES -ge $MAX_TRIES ]; then
        echo "Warning: Database not ready after $MAX_TRIES seconds. Continuing startup..."
    else
        echo "Connected to MySQL database $TARGET_DB_HOST successfully!"
        if [ -f /var/www/html/database/ecommerce.sql ]; then
            TABLE_COUNT=$(mysql -h "$TARGET_DB_HOST" -u "$REMOTE_USER" -p"$REMOTE_PASS" -D "$REMOTE_DB" -sse "SELECT count(*) FROM information_schema.tables WHERE table_schema='$REMOTE_DB';" 2>/dev/null || echo "0")
            if [ "$TABLE_COUNT" -eq "0" ] 2>/dev/null || [ -z "$TABLE_COUNT" ]; then
                echo "Database '$REMOTE_DB' is empty. Initializing schema from database/ecommerce.sql..."
                mysql -h "$TARGET_DB_HOST" -u "$REMOTE_USER" -p"$REMOTE_PASS" -D "$REMOTE_DB" < /var/www/html/database/ecommerce.sql 2>/dev/null || true
                echo "Database schema initialized!"
            else
                echo "Database '$REMOTE_DB' already contains $TABLE_COUNT tables. Skipping schema import."
            fi
        fi
    fi
fi

mkdir -p /var/www/html/uploads /var/www/html/logs
chown -R www-data:www-data /var/www/html/uploads /var/www/html/logs
chmod -R 775 /var/www/html/uploads /var/www/html/logs

echo "===================================================="
echo "Starting Apache Web Server in Foreground..."
echo "===================================================="
exec apache2-foreground