# up
create table if not exists `verification_code` (
    `id` bigint(20) unsigned not null,
    `version` int(11) not null default 0,
    `create_time` datetime default null,
    `update_time` datetime default null,
    `delete_time` datetime default null,
    `email` varchar(255) not null default '',
    `code` varchar(10) not null default '',
    `type` varchar(20) not null default '',
    `expire_time` datetime default null,
    `usage_time` datetime default null,
    `used` tinyint not null default 0,
    primary key (`id`),
    key `idx_email_type` (`email`, `type`, `used`)
) engine=InnoDB default charset=utf8mb4;

# down
drop table if exists `verification_code`;
