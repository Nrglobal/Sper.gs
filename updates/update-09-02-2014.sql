ALTER TABLE  `Messages` ADD  `type` INT( 1 ) NOT NULL ;
ALTER TABLE  `Messages` ADD  `link_id` INT( 11 ) NULL AFTER `topic_id` ;
ALTER TABLE  `Messages` DROP FOREIGN KEY  `Messages_ibfk_1`;