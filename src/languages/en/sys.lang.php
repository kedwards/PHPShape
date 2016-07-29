<?php
//
//	file: languages/en/sys.lang.php
//	author: Kevin Edwards - https://www.kncedwards.com/phpshape
//	begin: 28/09/2008
//	version: 1.0.0 - 28/09/2008
//	license: http://opensource.org/licenses/gpl-3.0.html GNU General Public License version 3 (GPLv3)
//

if ( !defined('SYS') )
{
	die('Not allowed');
}

// sys.class
$lang['ENCODING'] = 'utf-8';

$lang['err_sys_db_layer_not_found'] = 'sys: the database layer was not found.';
$lang['err_sys_db_not_supported'] = 'sys: this database type is not supported.';
$lang['err_sys_db_not_connected'] = 'sys: connection to the database failed.';
$lang['err_sys_write'] = 'sys: not able to write file <strong>%s</strong>.';

$lang['sys_information'] = 'Information';

$lang['err_sys_redirection'] = 'If your browser does not support meta redirection please click <a href="%s" id="redirect_link">HERE</a> to be redirected.';

// rstats.class
$lang['sys_rstats_elapsed'] = 'Time: %0.4fs';
$lang['sys_rstats_location'] = 'In <strong>%s</strong> on line <strong>%s</strong>';
$lang['sys_rstats_title_location'] = '<strong>%s</strong> in <strong>%s</strong> on line <strong>%s</strong>';
$lang['sys_rstats_function_location'] = '%s() in <strong>%s</strong> on line <strong>%s</strong>';

$lang['sys_rstats_db'] = 'db requests: %d in %0.4fs';
$lang['sys_rstats_db_caches'] = 'cache requests: %d in %0.4fs';
$lang['sys_rstats_db_debug'] = 'SQL requests';
$lang['sys_rstats_cache_elapsed'] = 'Time on cache: %0.4fs';
$lang['sys_rstats_db_elapsed'] = 'on db: %0.4fs';

$lang['sys_rstats_script_debug'] = 'Backtraces';
$lang['sys_rstats_script_at_time'] = 'At time: %0.4fs';
$lang['sys_rstats_script_elapsed'] = 'Elapsed since previous: %0.4fs';

// error.mixed
$lang['error_warnings'] = 'Warnings';
$lang['error_error'] = 'Error';
$lang['error_warnings_additional'] = 'Additional warnings';

$lang['E_ERROR'] = '<strong>Fatal error:</strong> %s in <strong>%s</strong> on line <strong>%s</strong>';
$lang['E_WARNING'] = '<strong>Warning:</strong> %s in <strong>%s</strong> on line <strong>%s</strong>';
$lang['E_PARSE'] = '<strong>Parse Error:</strong> %s in <strong>%s</strong> on line <strong>%s</strong>';
$lang['E_NOTICE'] = '<strong>Notice:</strong> %s in <strong>%s</strong> on line <strong>%s</strong>';
$lang['E_CORE_ERROR'] = '<strong>Core Error:</strong> %s in <strong>%s</strong> on line <strong>%s</strong>';
$lang['E_CORE_WARNING'] = '<strong>Core Warning:</strong> %s in <strong>%s</strong> on line <strong>%s</strong>';
$lang['E_COMPILE_ERROR'] = '<strong>Compile Error:</strong> %s in <strong>%s</strong> on line <strong>%s</strong>';
$lang['E_STRICT'] = '<strong>Strict syntax Error:</strong> %s in <strong>%s</strong> on line <strong>%s</strong>';
$lang['E_RECOVERABLE_ERROR'] = '<strong>Recoverable Error:</strong> %s in <strong>%s</strong> on line <strong>%s</strong>';

$lang['err_error_exception_msg'] = 'Invalid context: error <strong>%s</strong> occurs without the environment set in <strong>%s</strong> on line <strong>%s</strong> with the following message: %s';
$lang['err_error_unknown_sysmsg'] = '<strong>Unknown error (%s)</strong> in <strong>%s</strong> on line <strong>%s</strong>: %s';

// tpl.class
$lang['err_tpl_empty'] = 'sys_tpl: The file <strong>%s</strong> is empty or not readable.';
$lang['err_tpl_not_exists'] = 'sys_tpl: There is no file named <strong>%s</strong> (plus the style extension) in any of the declared styles.';

// db.class
$lang['err_db_error'] = 'sys_db has reported the following error in <strong>%s</strong> on line <strong>%s</strong>:<br />Error <strong>%s</strong>: %s<br />';
$lang['err_db_error_sql'] = 'sys_db has reported the following error in <strong>%s</strong> on line <strong>%s</strong>:<br />Error <strong>%s</strong>: %s<br />Request: %s<br />';
$lang['err_db_no_values'] = 'No rows to insert.';

$lang['err_db_mysql_too_low'] = 'sys_db_mysql: mySQL version is too low. At least mySQL 3.23.0 is required to use mySQL database.';
$lang['err_db_pgsql_php_too_low'] = 'sys_db_pgsql: PHP version is too low. At least php 4.1.0 is required to use PostgreSQL database.';

// xml.class
$lang['err_xml_empty'] = 'sys_xml_parser: empty xml.';
$lang['err_xml_no_tags'] = 'sys_xml_parser: no tags found.';
$lang['err_xml_unmatched_tag'] = 'sys_xml_parser: un-matched tag #%d: %s.';
$lang['err_xml_cdata_mixed'] = 'sys_xml_parser: cdata mixed with childs at tag #%d: %s.';
$lang['err_xml_error'] = 'sys_xml_parser has encountered the following error: %s on line <strong>%d</strong> with the resource %s.';

// pagination.class
$lang['sys_pagination_current'] = 'Page <strong>%d</strong> of <strong>%d</strong>';
$lang['sys_pagination_previous'] = 'Previous';
$lang['sys_pagination_next'] = 'Next';
$lang['sys_pagination_goto_previous'] = 'Goto previous page';
$lang['sys_pagination_goto_next'] = 'Goto next page';
$lang['sys_pagination_goto_page'] = 'Goto page %d';
$lang['sys_pagination_goto_first'] = 'Goto first page';
$lang['sys_pagination_goto_last'] = 'Goto last page';

?>