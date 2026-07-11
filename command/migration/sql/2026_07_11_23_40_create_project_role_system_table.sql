# up
create table `project_role_system` (
    `id` bigint not null,
    `project_role_id` bigint not null default 0,
    `system_id` bigint not null default 0,
    primary key (`id`),
    key `idx_project_role_id` (`project_role_id`),
    key `idx_system_id` (`system_id`)
) engine=InnoDB default charset=utf8mb4;

# down
drop table `project_role_system`;
