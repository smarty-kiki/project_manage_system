# up
create table `business_process` (
    `id` bigint not null,
    `version` int not null default 0,
    `create_time` datetime not null,
    `update_time` datetime not null,
    `delete_time` datetime default null,
    `project_id` bigint not null default 0,
    `name` varchar(255) not null default '',
    `description` text default null,
    primary key (`id`)
) engine=InnoDB default charset=utf8mb4;

# down
drop table `business_process`;
