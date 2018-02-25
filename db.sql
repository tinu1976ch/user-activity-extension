# Dump of table %aam_user_activity
# ------------------------------------------------------------

CREATE TABLE `%aam_user_activity` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user` int(11) NOT NULL,
  `created` datetime NOT NULL,
  `location` varchar(39) NOT NULL DEFAULT '',
  `hook` varchar(35) NOT NULL DEFAULT '',
  `metadata` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;