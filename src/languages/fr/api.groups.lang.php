<?php
//
//	file: languages/fr/groups.lang.php
//	author: Kevin Edwards - https://www.kncedwards.com/phpshape
//	begin: 28/09/2008
//	version: 1.0.1 - 01/12/2008
//	license: http://opensource.org/licenses/gpl-3.0.html GNU General Public License version 3 (GPLv3)
//

if ( !defined('SYS') )
{
	die('Not allowed');
}


$lang['group_root'] = 'Groupes';
$lang['group_guest'] = 'Invités';
$lang['group_members'] = 'Membres';
$lang['group_owners'] = 'Administrateurs principaux';

$lang['group_list_title'] = 'Groupes';

$lang['cmd_group_create'] = 'Ajouter un groupe';
$lang['cmd_group_edit'] = 'Modifier le groupe';
$lang['cmd_group_delete'] = 'Supprimer le groupe';

$lang['groups_create'] = 'Ajouter un groupe';
$lang['groups_edit'] = 'Modifier le groupe';
$lang['groups_delete'] = 'Supprimer le groupe';

$lang['group_name'] = 'Nom du groupe';
$lang['group_desc'] = 'Description';
$lang['group_lang'] = 'Langue';

$lang['err_empty_group_name'] = 'Le nom du groupe est obligatoire.';
$lang['err_group_lang_not_available'] = 'La langue que vous avez sélectionnée n\'est pas disponible.';

$lang['group_created'] = 'Le groupe a été créé.';
$lang['group_updated'] = 'Le groupe a été mis à jour.';
$lang['group_deleted'] = 'Le groupe a été supprimé.';
$lang['backto_groups'] = 'Retourner aux groupes';

// membership
$lang['err_group_membership_select'] = 'Vous n\'avez sélectionné ni groupe ni utilisateur. Choisissez soit un groupe pour lui ajouter de nouveaux membres, soit un utilisateur pour l\'inscrire à de nouveaux groupes.';

// managers
$lang['err_group_managers_select'] = 'Vous n\'avez sélectionné ni groupe ni utilisateur. Choisissez soit un groupe pour lui attribuer des gestionnaires, soit un utilisateur pour lui attribuer la gestion de groupes.';

?>