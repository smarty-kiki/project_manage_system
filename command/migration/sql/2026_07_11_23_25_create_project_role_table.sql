# up
create table `project_role` (
    `id` bigint not null,
    `version` int not null default 0,
    `create_time` datetime not null,
    `update_time` datetime not null,
    `delete_time` datetime default null,
    `project_id` bigint not null default 0,
    `name` varchar(255) not null default '',
    `description` varchar(1024) not null default '',
    primary key (`id`),
    key `idx_project_id` (`project_id`)
) engine=InnoDB default charset=utf8mb4;

# down
drop table `project_role`;
