#!/bin/bash

#
# MariaDB helper for macOS + Homebrew
#
# Usage:
#
#   ./mysqlctl.sh start
#   ./mysqlctl.sh stop
#   ./mysqlctl.sh restart
#   ./mysqlctl.sh status
#

ACTION=$1

MYSQL_SERVER=$(which mysql.server)

if [ -z "$MYSQL_SERVER" ]; then
    echo "mysql.server non trovato."
    echo "Installa MariaDB con:"
    echo ""
    echo "  brew install mariadb"
    exit 1
fi

case "$ACTION" in

    start)
        echo "Avvio MariaDB..."
        mysql.server start
        ;;

    stop)
        echo "Stop MariaDB..."
        mysql.server stop
        ;;

    restart)
        echo "Restart MariaDB..."
        mysql.server restart
        ;;

    status)
        mysql.server status
        ;;

    *)
        echo ""
        echo "Uso:"
        echo ""
        echo "  ./mysqlctl.sh start"
        echo "  ./mysqlctl.sh stop"
        echo "  ./mysqlctl.sh restart"
        echo "  ./mysqlctl.sh status"
        echo ""
        ;;
esac
