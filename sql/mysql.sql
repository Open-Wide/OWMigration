#CREATE TABLE `owmigration` (                                                                                                                                                                                                       
#  `class` varchar(100) NOT NULL,                                                                                                                                                                                           
#  `method` varchar(10) NOT NULL,                                                                                                                                                                                           
#  `date` datetime NOT NULL,                                                                                                                                                                                                                        
#  `log` longtext                                                                                                                                                                                                           
#);

CREATE TABLE `owmigration_version` (
  `extension` varchar(100) NOT NULL,
  `version` varchar(3) NOT NULL,
  `status` varchar(20) NOT NULL
);