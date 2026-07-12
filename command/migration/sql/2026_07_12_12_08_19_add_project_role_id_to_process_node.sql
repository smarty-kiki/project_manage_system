# up
alter table `process_node` add column `project_role_id` bigint not null default 0 after `business_process_id`;

# down
alter table `process_node` drop column `project_role_id`;
