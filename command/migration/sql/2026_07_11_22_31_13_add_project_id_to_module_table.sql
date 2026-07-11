# up
alter table `module` add column `project_id` bigint not null default 0 after `id`;

# down
alter table `module` drop column `project_id`;
