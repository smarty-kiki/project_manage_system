#!/bin/bash

ROOT_DIR="$(cd "$(dirname $0)" && pwd)"/../..

sudo docker run --rm -ti -p 80:80 -p 3306:3306 -p 12345:12345 -p 12346:12346 --name project_manage_system \
    -v $ROOT_DIR/:/var/www/project_manage_system \
    -v $ROOT_DIR/project/config/development/nginx/project_manage_system.conf:/etc/nginx/sites-enabled/default \
    -v $ROOT_DIR/project/config/development/supervisor/project_manage_system_queue_worker.conf:/etc/supervisor/conf.d/queue_worker.conf \
    -v $ROOT_DIR/project/config/development/supervisor/queue_job_watch.conf:/etc/supervisor/conf.d/queue_job_watch.conf \
    -v ~/.claude:/root/.claude \
    -v ~/.claude.json:/root/.claude.json \
    -e 'PRJ_HOME=/var/www/project_manage_system' \
    -e 'ENV=development' \
    -e 'TIMEZONE=Asia/Shanghai' \
    -e 'AFTER_START_SHELL=/var/www/project_manage_system/project/tool/development/after_env_start.sh' \
    registry.cn-shenzhen.aliyuncs.com/smarty/harness_engineering_php_env start
