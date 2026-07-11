# up
drop table if exists `project_role_system`;

# down
create table `project_role_system` (
    `id` bigint not null,
    `version` int not null default 0,
    `create_time` datetime not null,
    `update_time` datetime not null,
    `delete_time` datetime default null,
    `project_role_id` bigint not null default 0,
    `system_id` bigint not null default 0,
    primary key (`id`)
) engine=InnoDB default charset=utf8mb4;
add key `idx_project_role_id` (`project_role_id`);
add key `idx_system_id` (`system_id`);
