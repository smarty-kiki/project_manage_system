# up
create table if not exists `team_member` (
    `id` bigint(20) unsigned not null,
    `version` int(11) not null default 0,
    `create_time` datetime default null,
    `update_time` datetime default null,
    `delete_time` datetime default null,
    `team_id` bigint(20) unsigned not null,
    `user_id` bigint(20) unsigned not null,
    `role` varchar(20) not null default 'member',
    `joined_time` datetime default null,
    primary key (`id`),
    unique key `idx_team_user` (`team_id`, `user_id`)
) engine=InnoDB default charset=utf8mb4;

# down
drop table if exists `team_member`;
