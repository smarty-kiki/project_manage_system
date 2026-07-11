# up
create table `process_node` (
    `id` bigint not null,
    `version` int not null default 0,
    `create_time` datetime not null,
    `update_time` datetime not null,
    `delete_time` datetime default null,
    `business_process_id` bigint not null default 0,
    `name` varchar(255) not null default '',
    `description` text default null,
    `sort_order` int not null default 0,
    primary key (`id`)
) engine=InnoDB default charset=utf8mb4;

# down
drop table `process_node`;
