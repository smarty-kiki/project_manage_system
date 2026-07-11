# up
alter table `bug` add column `role_id` bigint not null default 0 after `requirement_id`;

# down
alter table `bug` drop column `role_id`;
