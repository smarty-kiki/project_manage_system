# up
alter table `business_process` add column `initiator_role_id` bigint not null default 0 after `description`;

# down
alter table `business_process` drop column `initiator_role_id`;
