<?php
//
//	file: api/api.ini.php
//	author: Kevin Edwards - https://www.kncedwards.com/phpshape
//	begin: 14/02/2008
//	version: 0.0.3 - 01/08/2010
//	license: http://opensource.org/licenses/gpl-3.0.html GNU General Public License version 3 (GPLv3)
//

// zlib is required for pclzip library

if ( !defined('SYS') )
{
	die('Not allowed');
}

// system groups
define('SYS_GROUP_GUESTS', 1);
define('SYS_GROUP_MEMBERS', 2);
define('SYS_GROUP_OWNERS', 3);

// url/object identifiers
define('SYS_U_MODE', 'mode');
define('SYS_U_START', 'start');
define('SYS_U_ITEM', 'id'); // generic
define('SYS_U_USER', 'u'); // user
define('SYS_U_GROUP', 'g'); // tree type
define('SYS_U_ACTKEY', 'ak'); // activation key

// temporary directory for pclzip
define('PCLZIP_TEMPORARY_DIR', $GLOBALS[SYS]->root . 'cache/');

// processes
$ini = array(
	// timezone constants
	'timezones' => array(
		'-43200' => 'UTC-12',
		'-39600' => 'UTC-11',
		'-36000' => 'UTC-10',
		'-34200' => 'UTC-9:30',
		'-32400' => 'UTC-9',
		'-28800' => 'UTC-8, DST-9',
		'-25200' => 'UTC-7, DST-8',
		'-21600' => 'UTC-6, DST-7',
		'-18000' => 'UTC-5, DST-6',
		'-16200' => 'UTC-4:30',
		'-14400' => 'UTC-4, DST-5',
		'-12600' => 'UTC-3:30',
		'-10800' => 'UTC-3, DST-4',
		'-9000' => 'UTC-2:30, DST-3:30',
		'-7200' => 'UTC-2, DST-3',
		'-3600' => 'UTC-1',
		'' => '',
		'0' => 'UTC+0, DST-1',
		'3600' => 'UTC+1, DST+0',
		'7200' => 'UTC+2, DST+1',
		'10800' => 'UTC+3, DST+2',
		'12600' => 'UTC+3:30',
		'14400' => 'UTC+4, DST+3',
		'16200' => 'UTC+4:30',
		'18000' => 'UTC+5, DST+4',
		'19800' => 'UTC+5:30',
		'20700' => 'UTC+5:45',
		'21600' => 'UTC+6',
		'23400' => 'UTC+6:30',
		'25200' => 'UTC+7, DST+6',
		'28800' => 'UTC+8, DST+7',
		'31500' => 'UTC+8:45',
		'32400' => 'UTC+9, DST+8',
		'34200' => 'UTC+9:30',
		'36000' => 'UTC+10, DST+9',
		'37800' => 'UTC+10:30, DST+9:30',
		'39600' => 'UTC+11, DST+10',
		'41400' => 'UTC+11:30, DST+10:30',
		'43200' => 'UTC+12, DST+11',
		'45900' => 'UTC+12:45',
		'46800' => 'UTC+13, DST+12',
		'49500' => 'UTC+13:45, DST+12:45',
		'50400' => 'UTC+14',
	),

	// debug time ?
//	'rstats' => '',

	// only registered people can access the main page
	'login.forced' => array('login' => 'login', 'except' => array('login', 'register', 'login.lost')), // guests will be redirected to login, except if mode=....
	'login.activation' => true, // profile activation required
	'login.email.unique' => true, // email must be unique within the user database
	'login.report' => array('email' => 'postmaster@foo.com', 'lang' => 'en'),
	'login.disable.password.renew' => false, // disable "I have forgot my password" on login form

	// add a blind carbon copy to the registration mail
//	'register.copy' => 'administrator@foo.com',

	// default timeshift for date (guests)
	'timeshift' => 1 * 3600, // 1 hour

	// session constants
	'session.cookie_name' => 'api', // default cooky prefix name
	'session.cookie_domain' => false, // cookies domain will be guessed
	'session.max_relog_time' => 86400 * 15, // duration of the auto-login cookie: 15 days
	'session.max_length' => 300, // duration of the sid cookie
	'session.sid_name' => 'sid', // session id url varname
	'session.ip_length' => 6, // number of hex-digit used to validate an ip

	// login constants
	'login.max_attempts' => 5, // number of loggin attempt before the profile is disabled for login
	'login.reset_time' => 30, // duration in minutes the profile is disabled for login
	'login.backto' => '', // backto array(text => array(parms)) on login: to see a single page once on login
	'login.delay' => 5, // post-login message duration prior auto-redirection

	// lists constants
	'users.tree.ppage' => 19, // number of users within a small block area (membership ie)
	'users.content.ppage' => 20, // number of users within a large list

	// hooks processor
	'hooks.processor' => array('class' => 'hooks', 'file' => 'ROOT/api/hooks.class'),

	// service: mailer
	'emailer' => array('file' => 'ROOT/api/emailer.class', 'class' => 'emailer'),
	'emailer.mail' => array('file' => 'ROOT/api/emailer.mail.class', 'class' => 'emailer_mail', 'layer' => 'emailer'),
	'emailer.smtp' => array('file' => 'ROOT/api/emailer.smtp.class', 'class' => 'emailer_smtp', 'layer' => 'emailer'),

	// service: actor timeshift
	'srv.time' => array('file' => 'ROOT/api/srv.time.class', 'class' => 'srv_time'),

	// service: cron processus handler
	'cron.processus' => array('file' => 'ROOT/api/cron.processus.class', 'class' => 'cron_processus'),

	// service: menus builder
	'api.menus' => array('file' => 'ROOT/api/api.menus.class', 'class' => 'api_menus'),

	// main objects
	'db.config' => array('file' => 'ROOT/dbconfig'),
	'mail.config' => array('file' => 'ROOT/mail.config'),
	'session' => array('file' => 'ROOT/api/session.class', 'class' => 'session'),
	'actor' => array('file' => 'ROOT/api/users.class', 'class' => 'actor', 'layer' => 'user'),
	'user' => array('file' => 'ROOT/api/users.class', 'class' => 'user'),
	'groups' => array('file' => 'ROOT/api/groups.class', 'class' => 'groups', 'layer' => 'tree.auths'),

	// tree management
	'tree' => array('class' => 'tree', 'file' => 'ROOT/api/tree.class'),
	'tree.list' => array('class' => 'tree_list', 'file' => 'ROOT/api/tree.list.class'),
	'tree.auths' => array('file' => 'ROOT/api/tree.auths.class', 'class' => 'tree_auths', 'layer' => 'tree'),
	'tree.auths.list' => array('file' => 'ROOT/api/tree.auths.list.class', 'class' => 'tree_auths_list', 'layer' => 'tree.list'),

	// actor/users
	'actor.login' => array('file' => 'ROOT/api/users.form.class', 'class' => 'actor_login'),
	'actor.login.lost' => array('file' => 'ROOT/api/users.form.class', 'class' => 'actor_login_lost'),
	'actor.login.report' => array('file' => 'ROOT/api/users.form.class', 'class' => 'actor_login_report'),
	'actor.logout' => array('file' => 'ROOT/api/users.form.class', 'class' => 'actor_logout'),
	'users.list' => array('file' => 'ROOT/api/users.list.class', 'class' => 'users_list'),
	'users.form' => array('file' => 'ROOT/api/users.form.class', 'class' => 'user_edit'),
	'users.thumb' => array('file' => 'ROOT/api/users.thumb.class', 'class' => 'user_thumb'),

	// groups
	'groups.list' => array('file' => 'ROOT/api/groups.list.class', 'class' => 'groups_list', 'layer' => 'tree.auths.list'),
	'groups.form' => array('file' => 'ROOT/api/groups.form.class', 'class' => 'groups_form'),

	'groups.managers' => array('file' => 'ROOT/api/groups.managers.class', 'class' => 'groups_managers'),
	'groups.managers.groups.list' => array('file' => 'ROOT/api/groups.managers.groups.list.class', 'class' => 'groups_managers_groups_list', 'layer' => 'groups.list'),
	'groups.managers.users' => array('file' => 'ROOT/api/groups.managers.users.class', 'class' => 'groups_managers_users', 'layer' => 'users.list'),

	'groups.membership' => array('file' => 'ROOT/api/groups.membership.class', 'class' => 'groups_membership'),
	'groups.membership.groups.list' => array('file' => 'ROOT/api/groups.membership.groups.list.class', 'class' => 'groups_membership_groups_list', 'layer' => 'groups.list'),
	'groups.membership.users' => array('file' => 'ROOT/api/groups.membership.users.class', 'class' => 'groups_membership_users', 'layer' => 'users.list'),

	// modes
	'default.mode' => '',
	'modes' => array(
		'login' => 'actor.login',
		'login.lost' => 'actor.login.lost',
		'login.problem' => 'actor.login.report',
		'logout' => 'actor.logout',
		'profile' => 'users.form',
		'register' => 'users.form',
		'users.edit' => 'users.form',
		'users.create' => 'users.form',
		'users.delete' => 'users.form',

		'groups' => 'groups.list',
		'groups.open' => 'groups.list',
		'groups.close' => 'groups.list',
		'groups.move' => 'groups.list',
		'groups.content' => 'groups.list',
		'groups.create' => 'groups.list',
		'groups.edit' => 'groups.list',
		'groups.delete' => 'groups.list',

		'groups.managers' => 'groups.managers',
		'groups.managers.group.open' => 'groups.managers',
		'groups.managers.group.close' => 'groups.managers',
		'groups.managers.group.content' => 'groups.managers',
		'groups.managers.group.move' => 'groups.managers',
		'groups.managers.group.remove' => 'groups.managers',
		'groups.managers.user.content' => 'groups.managers',
		'groups.managers.user.move' => 'groups.managers',
		'groups.managers.user.remove' => 'groups.managers',
		'groups.managers.user.filter' => 'groups.managers',

		'groups.membership' => 'groups.membership',
		'groups.membership.group.open' => 'groups.membership',
		'groups.membership.group.close' => 'groups.membership',
		'groups.membership.group.content' => 'groups.membership',
		'groups.membership.group.move' => 'groups.membership',
		'groups.membership.group.remove' => 'groups.membership',
		'groups.membership.group.filter' => 'groups.membership',
		'groups.membership.user.content' => 'groups.membership',
		'groups.membership.user.move' => 'groups.membership',
		'groups.membership.user.remove' => 'groups.membership',
		'groups.membership.user.filter' => 'groups.membership',

		// service
		'srv.time' => 'srv.time',
	),

	// hooks
	'hooks' => array(
		'menus' => array('api.menus'),
		'actor.display' => array('groups'),
		'user.delete' => array('groups', 'tree.auths'),
	),
);

?>