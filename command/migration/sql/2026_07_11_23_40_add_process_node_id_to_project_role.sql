# up
alter table `project_role` add column `process_node_id` bigint not null default 0 after `description`;

# down
alter table `project_role` drop column `process_node_id`;
