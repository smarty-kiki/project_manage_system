# up
create table `project_role_module` (
    `id` bigint not null,
    `project_role_id` bigint not null default 0,
    `module_id` bigint not null default 0,
    primary key (`id`),
    key `idx_project_role_id` (`project_role_id`),
    key `idx_module_id` (`module_id`)
) engine=InnoDB default charset=utf8mb4;

# down
drop table `project_role_module`;
