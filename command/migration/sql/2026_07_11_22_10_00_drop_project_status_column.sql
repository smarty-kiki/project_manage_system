# up
alter table `project` drop column `status`;

# down
alter table `project` add column `status` int not null default 1 after `creator_id`;
