<?php
//
//	file: sys/sys.api.ini.php
//	author: Kevin Edwards - https://www.kncedwards.com/phpshape
//	begin: 31/01/2009
//	version: 0.0.1 - 31/01/2009
//	license: http://opensource.org/licenses/gpl-3.0.html GNU General Public License version 3 (GPLv3)
//

if ( !defined('SYS') )
{
	die('Not allowed');
}

$ini = array(

	// db
	'db' => array('class' => 'sys_db', 'file' => 'SYS/db/db.class'),
	'db.config' => array('file' => 'ROOT/dbconfig'),

	// xml parser
	'xml' => array('class' => 'sys_xml_parser', 'file' => 'SYS/xml.class'),

	// redirection time on feedback
	'feedback.time' => 5,

	// pagination
	'pagination' => array('class' => 'sys_pagination', 'file' => 'SYS/pagination.class'),
	'ppage.list' => 25, // rows per page in lists
);

?>