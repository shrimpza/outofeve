CREATE TABLE IF NOT EXISTS `apikey` (
  `id` int(11) NOT NULL auto_increment,
  `user_id` int(11) NOT NULL,
  `name` varchar(32) NOT NULL,
  `keyid` int(11) NOT NULL,
  `vcode` varchar(256) NOT NULL,
  `character_id` int(11) NOT NULL,
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
  `password` varchar(128) NOT NULL,
  `email` varchar(64) NOT NULL,
  `timezone` varchar(128) NOT NULL default 'GMT',
  `theme` varchar(32) NOT NULL,
  `proxy` varchar(256) NOT NULL,
  `level` tinyint(4) NOT NULL,
  `activetime` datetime NOT NULL,
  `char_apikey_id` int(11) NOT NULL,
  `corp_apikey_id` int(11) NOT NULL,
  `session_id` varchar(128) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `username` (`username`),
  KEY `session_id` (`session_id`)
);
