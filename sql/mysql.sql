CREATE TABLE `owmigration` (                                                                                                                                                                                                       
  `class` varchar(100) NOT NULL,                                                                                                                                                                                           
  `method` varchar(10) NOT NULL,                                                                                                                                                                                           
  `date` datetime NOT NULL,                                                                                                                                                                                                                        
  `log` longtext COLLATE utf8_unicode_ci                                                                                                                                                                                                           
);

TRUNCATE owmigration;
ALTER TABLE owmigration ADD COLUMN extension varchar(100) NOT NULL;
ALTER TABLE owmigration ADD COLUMN version varchar(3) NOT NULL;
ALTER TABLE owmigration ADD COLUMN status varchar(10) NOT NULL;
ALTER TABLE owmigration DROP COLUMN class;
ALTER TABLE owmigration DROP COLUMN date;
ALTER TABLE owmigration DROP COLUMN log;
ALTER TABLE owmigration DROP COLUMN method;