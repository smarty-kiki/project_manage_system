# up
alter table `requirement` add column `role_id` bigint not null default 0 after `module_id`;

# down
alter table `requirement` drop column `role_id`;
