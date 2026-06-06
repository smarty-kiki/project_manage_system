#!/bin/bash

ROOT_DIR="$(cd "$(dirname $0)" && pwd)"/../../..

ln -fs $ROOT_DIR/project/config/production/caddy/php-vibe-coding-frame.Caddyfile /etc/caddy/3.php-vibe-coding-frame.Caddyfile
/usr/sbin/service caddy reload

/usr/bin/php $ROOT_DIR/public/cli.php migrate:install
/usr/bin/php $ROOT_DIR/public/cli.php migrate

ln -fs $ROOT_DIR/project/config/production/supervisor/php-vibe-coding-frame_queue_worker.conf /etc/supervisor/conf.d/php-vibe-coding-frame_queue_worker.conf
/usr/bin/supervisorctl update
/usr/bin/supervisorctl restart php-vibe-coding-frame_queue_worker:*

chmod 777 /var/www/php-vibe-coding-frame/view/blade
rm -rf /var/www/php-vibe-coding-frame/view/blade/*.php
