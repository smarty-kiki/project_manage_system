# up
create table `project` (
    `id` bigint not null,
    `version` int not null default 0,
    `create_time` datetime not null,
    `update_time` datetime not null,
    `delete_time` datetime default null,
    `team_id` bigint not null default 0,
    `name` varchar(255) not null default '',
    `description` varchar(1000) not null default '',
    `creator_id` bigint not null default 0,
    `status` tinyint not null default 1,
    primary key (`id`),
    index `idx_team_id` (`team_id`)
) engine=InnoDB default charset=utf8mb4;

# down
drop table `project`;
