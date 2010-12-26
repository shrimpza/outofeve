CREATE TABLE IF NOT EXISTS `account` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(32) NOT NULL,
  `user_id` int(11) NOT NULL,
  `apiuser` varchar(16) NOT NULL,
  `apikey` varchar(256) NOT NULL,
  `character_id` varchar(16) NOT NULL,
  `precache`  tinyint default 0,
  PRIMARY KEY  (`id`),
  KEY `user_id` (`user_id`)
);

CREATE TABLE IF NOT EXISTS `mineralprice` (
  `id` int(11) NOT NULL auto_increment,
  `user_id` int(11) NOT NULL,
  `typeid` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `user_id` (`user_id`)
);

CREATE TABLE IF NOT EXISTS `user` (
  `id` int(11) NOT NULL auto_increment,
  `username` varchar(32) NOT NULL,
  `password` varchar(64) NOT NULL,
  `email` varchar(32) NOT NULL,
  `timezone` varchar(128) NOT NULL default 'GMT',
  `theme` varchar(64) NOT NULL,
  `proxy` varchar(256) NOT NULL,
  `level` tinyint(4) NOT NULL,
  `account_id` int(11) NOT NULL,
  `activetime` datetime NOT NULL,
  `smallicons` tinyint NOT NULL default 0,
  PRIMARY KEY  (`id`),
  KEY `username` (`username`,`password`)
);

CREATE TABLE IF NOT EXISTS `showmenus` (
  `id` int(11) NOT NULL auto_increment,
  `account_id` int(11) NOT NULL,
  `menu` varchar(64) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `account_id` (`account_id`)
)
