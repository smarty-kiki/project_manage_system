# up
alter table `process_node` add column `project_id` bigint not null default 0 after `id`,
    add key `idx_project_id` (`project_id`);

# down
alter table `process_node` drop key `idx_project_id`,
    drop column `project_id`;
