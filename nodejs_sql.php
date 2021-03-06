CREATE TABLE `nodejs_presence` (
`uid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'The uid of the user.',
`login_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'The timestamp of when the user came online.',
UNIQUE KEY `uid` (`uid`),
KEY `login_time` (`login_time`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `nodejs_sessions` (
`uid` int(10) unsigned NOT NULL COMMENT 'The users.uid corresponding to a session, or 0 for anonymous user.',
`sid` varchar(128) NOT NULL COMMENT 'Session ID. The value is generated by session handler.',
`timestamp` int(11) NOT NULL DEFAULT '0' COMMENT 'The Unix timestamp when this session last requested a page. Old records are purged by PHP automatically.',
PRIMARY KEY (`sid`),
KEY `timestamp` (`timestamp`),
KEY `uid` (`uid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
