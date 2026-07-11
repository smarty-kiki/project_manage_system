# up
ALTER TABLE `team_account` CHANGE `nickname` `name` VARCHAR(255) NOT NULL DEFAULT '';

# down
ALTER TABLE `team_account` CHANGE `name` `nickname` VARCHAR(100) NOT NULL DEFAULT '';
