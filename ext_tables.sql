#
# Table structure for table 'tx_formhandler_log'
#
CREATE TABLE tx_formhandler_log (
	uid int(11) unsigned NOT NULL auto_increment,
	pid int(11) unsigned DEFAULT '0' NOT NULL,
	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,
	deleted int(11) unsigned DEFAULT '0' NOT NULL,
	ip tinytext,
	params mediumtext,
	is_spam int(11) unsigned DEFAULT '0',
	key_hash tinytext,
	unique_hash tinytext,
	PRIMARY KEY (uid),
	KEY parent (pid)
);
