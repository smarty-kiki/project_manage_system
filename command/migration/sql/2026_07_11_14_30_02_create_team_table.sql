# up
create table if not exists `team` (
    `id` bigint(20) unsigned not null,
    `version` int(11) not null default 0,
    `create_time` datetime default null,
    `update_time` datetime default null,
    `delete_time` datetime default null,
    `name` varchar(100) not null default '',
    `description` varchar(500) not null default '',
    `creator_id` bigint(20) unsigned not null,
    `status` tinyint not null default 1,
    primary key (`id`)
) engine=InnoDB default charset=utf8mb4;

# down
drop table if exists `team`;
