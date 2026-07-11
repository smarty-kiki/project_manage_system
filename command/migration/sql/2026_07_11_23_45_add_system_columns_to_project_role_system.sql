# up
alter table `project_role_system`
    add column `version` int not null default 0 after `system_id`,
    add column `create_time` datetime not null after `version`,
    add column `update_time` datetime not null after `create_time`,
    add column `delete_time` datetime default null after `update_time`;

# down
alter table `project_role_system`
    drop column `delete_time`,
    drop column `update_time`,
    drop column `create_time`,
    drop column `version`;
