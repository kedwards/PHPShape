-- phpMyAdmin SQL Dump
-- version 3.3.8
-- http://www.phpmyadmin.net
--
-- mysql server version: 5.1.51
-- PHP version: 5.3.3

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

CREATE DATABASE engines DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
USE engines;

DROP TABLE IF EXISTS engines_cron_processus;
CREATE TABLE IF NOT EXISTS engines_cron_processes (
  process_id int(11) NOT NULL AUTO_INCREMENT,
  process_name varchar(255) NOT NULL DEFAULT '',
  process_step_last tinyint(3) NOT NULL DEFAULT '0',
  process_step_current tinyint(3) NOT NULL DEFAULT '0',
  process_time_start int(11) NOT NULL DEFAULT '0',
  process_time_current int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (process_id),
  UNIQUE KEY process_name (process_name,process_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS engines_groups;
CREATE TABLE IF NOT EXISTS engines_groups (
  group_id int(11) NOT NULL AUTO_INCREMENT,
  group_pid int(11) NOT NULL DEFAULT '0',
  group_lid int(11) NOT NULL DEFAULT '0',
  group_rid int(11) NOT NULL DEFAULT '0',
  group_name varchar(255) NOT NULL DEFAULT '',
  group_name_trs tinyint(1) NOT NULL DEFAULT '0',
  group_desc varchar(255) NOT NULL DEFAULT '',
  group_lang varchar(255) NOT NULL DEFAULT '',
  user_id int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (group_id),
  KEY group_pid (group_pid,group_lid),
  KEY group_lid (group_lid,group_rid,group_pid,group_id)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

INSERT INTO engines_groups (group_id, group_pid, group_lid, group_rid, group_name, group_name_trs, group_desc, group_lang, user_id) VALUES
(1, 0, 1, 2, 'group_guest', 1, '', '', 0),
(2, 0, 3, 6, 'group_members', 1, '', '', 0),
(3, 2, 4, 5, 'group_owners', 1, '', '', 0);

DROP TABLE IF EXISTS engines_groups_auths;
CREATE TABLE IF NOT EXISTS engines_groups_auths (
  group_id int(11) NOT NULL DEFAULT '0',
  obj_id int(11) NOT NULL DEFAULT '0',
  auth_type char(1) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  auth_name varchar(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (group_id,auth_type,obj_id,auth_name),
  KEY auth_type (auth_type,group_id,obj_id),
  KEY obj_id (obj_id,auth_type,auth_name,group_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS engines_sessions;
CREATE TABLE IF NOT EXISTS engines_sessions (
  session_id varchar(32) NOT NULL,
  session_ip varchar(8) NOT NULL,
  session_agent varchar(32) NOT NULL,
  session_start int(11) NOT NULL DEFAULT '0',
  session_time int(11) NOT NULL DEFAULT '0',
  session_data text,
  PRIMARY KEY (session_id),
  KEY session_time (session_time),
  KEY session_ip (session_ip,session_agent,session_time)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS engines_users;
CREATE TABLE IF NOT EXISTS engines_users (
  user_id int(11) NOT NULL AUTO_INCREMENT,
  user_name varchar(255) NOT NULL,
  user_ident varchar(255) NOT NULL,
  user_password varchar(255) NOT NULL,
  user_login_tries tinyint(2) NOT NULL DEFAULT '0',
  user_login_tries_last int(11) NOT NULL DEFAULT '0',
  user_realname varchar(255) NOT NULL,
  user_realname_ident varchar(255) NOT NULL DEFAULT '',
  user_email varchar(255) NOT NULL,
  user_phone varchar(255) NOT NULL,
  user_location varchar(255) NOT NULL,
  user_regdate int(11) NOT NULL DEFAULT '0',
  user_lang varchar(255) NOT NULL DEFAULT '',
  user_timeshift varchar(7) NOT NULL DEFAULT '',
  user_timeshift_disable tinyint(1) NOT NULL DEFAULT '0',
  user_disabled tinyint(1) NOT NULL DEFAULT '0',
  user_actkey varchar(32) NOT NULL DEFAULT '',
  user_password_renew varchar(256) NOT NULL DEFAULT '',
  PRIMARY KEY (user_id),
  UNIQUE KEY user_realname_ident (user_realname_ident,user_id),
  UNIQUE KEY user_ident (user_ident,user_id),
  KEY user_email (user_email)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

INSERT INTO engines_users (user_id, user_name, user_ident, user_password, user_login_tries, user_login_tries_last, user_realname, user_realname_ident, user_email, user_phone, user_location, user_regdate, user_lang, user_timeshift, user_timeshift_disable, user_disabled, user_actkey, user_password_renew) VALUES
(1, 'admin', 'admin', '21232f297a57a5a743894a0e4a801fc3', 0, 0, 'Administrator', 'administrator', 'admin@kncedwards.com', '', '', 1204120769, '', '3600', 0, 0, '', '');

DROP TABLE IF EXISTS engines_users_groups;
CREATE TABLE IF NOT EXISTS engines_users_groups (
  group_id int(11) NOT NULL DEFAULT '0',
  user_id int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (group_id,user_id),
  KEY user_id (user_id,group_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

INSERT INTO engines_users_groups (group_id, user_id) VALUES
(3, 1);

DROP TABLE IF EXISTS engines_users_history;
CREATE TABLE IF NOT EXISTS engines_users_histo (
  user_id int(11) NOT NULL DEFAULT '0',
  session_id varchar(32) NOT NULL DEFAULT '',
  session_start int(11) NOT NULL DEFAULT '0',
  session_time int(11) NOT NULL DEFAULT '0',
  session_ip varchar(8) NOT NULL DEFAULT '',
  session_agent longtext,
  session_lang varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (session_start,user_id,session_id),
  UNIQUE KEY user_id (user_id,session_id,session_start)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS engines_users_logins;
CREATE TABLE IF NOT EXISTS engines_users_logins (
  user_id int(11) NOT NULL DEFAULT '0',
  login_id varchar(32) NOT NULL,
  login_time int(11) NOT NULL DEFAULT '0',
  login_agent varchar(32) NOT NULL,
  PRIMARY KEY (user_id,login_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS engines_users_tree;
CREATE TABLE IF NOT EXISTS engines_users_tree (
  user_id int(11) NOT NULL DEFAULT '0',
  session_id varchar(32) NOT NULL DEFAULT '',
  tree_type char(1) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  tree_id int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (user_id,session_id,tree_type,tree_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
