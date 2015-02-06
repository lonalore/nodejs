CREATE TABLE `nodejs_presence` (
`uid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'The uid of the user.',
`login_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'The timestamp of when the user came online.',
UNIQUE KEY `uid` (`uid`),
KEY `login_time` (`login_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='List of currently online users on a node.js server.';
