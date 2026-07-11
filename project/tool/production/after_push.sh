#!/bin/bash

ROOT_DIR="$(cd "$(dirname $0)" && pwd)"/../../..

ln -fs $ROOT_DIR/project/config/production/caddy/project_manage_system.Caddyfile /etc/caddy/3.project_manage_system.Caddyfile
/usr/sbin/service caddy reload

/usr/bin/php $ROOT_DIR/public/cli.php migrate:install
/usr/bin/php $ROOT_DIR/public/cli.php migrate

ln -fs $ROOT_DIR/project/config/production/supervisor/project_manage_system_queue_worker.conf /etc/supervisor/conf.d/project_manage_system_queue_worker.conf
/usr/bin/supervisorctl update
/usr/bin/supervisorctl restart project_manage_system_queue_worker:*

chmod 777 /var/www/project_manage_system/view/blade
rm -rf /var/www/project_manage_system/view/blade/*.php
