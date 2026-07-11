# up
create table if not exists `team_account` (
    `id` bigint(20) unsigned not null,
    `version` int(11) not null default 0,
    `create_time` datetime default null,
    `update_time` datetime default null,
    `delete_time` datetime default null,
    `email` varchar(255) not null default '',
    `password_hash` varchar(255) not null default '',
    `nickname` varchar(100) not null default '',
    `avatar` varchar(500) not null default '',
    `phone` varchar(20) not null default '',
    `status` tinyint not null default 1,
    `role` varchar(20) not null default 'user',
    primary key (`id`),
    unique key `idx_email` (`email`)
) engine=InnoDB default charset=utf8mb4;

# down
drop table if exists `team_account`;
