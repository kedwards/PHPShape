<?php
//
//	file: languages/en/groups.lang.php
//	author: Kevin Edwards - https://www.kncedwards.com/phpshape
//	begin: 28/09/2008
//	version: 1.0.1 - 01/12/2008
//	license: http://opensource.org/licenses/gpl-3.0.html GNU General Public License version 3 (GPLv3)
//

if ( !defined('SYS') )
{
	die('Not allowed');
}


$lang['group_root'] = 'Groups';
$lang['group_guest'] = 'Guests';
$lang['group_members'] = 'Members';
$lang['group_owners'] = 'Main Administrators';

$lang['group_list_title'] = 'Groups';

$lang['cmd_group_create'] = 'Add group';
$lang['cmd_group_edit'] = 'Edit';
$lang['cmd_group_delete'] = 'Delete';

$lang['groups_create'] = 'Add Group';
$lang['groups_edit'] = 'Edit group';
$lang['groups_delete'] = 'Delete group';

$lang['group_name'] = 'Group name';
$lang['group_desc'] = 'Description';
$lang['group_lang'] = 'Language';

$lang['err_empty_group_name'] = 'The group name is mandatory.';
$lang['err_group_lang_not_available'] = 'The language you have selected is not available.';

$lang['group_created'] = 'The group has been created.';
$lang['group_updated'] = 'The group has been updated.';
$lang['group_deleted'] = 'The group has been deleted.';
$lang['backto_groups'] = 'Return to groups';

// membership
$lang['err_group_membership_select'] = 'There are no groups selected nor users. Please choose either a group to add its members, or a user to register her/him to groups.';

// managers
$lang['err_group_managers_select'] = 'There are no groups selected nor users. Please choose either a group to add its managers, or a manager to designate his groups.';

?>