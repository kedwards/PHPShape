<?php
//
//	file: languages/fr/sys.lang.php
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

$lang['err_sys_db_layer_not_found'] = 'sys: l\'interface de base de données n\'a pas été trouvée.';
$lang['err_sys_db_not_supported'] = 'sys: le type de la base de données n\'est pas supporté.';
$lang['err_sys_db_not_connected'] = 'sys: la connexion à la base de données a échoué.';
$lang['err_sys_write'] = 'sys: impossible d\'écrire le fichier <strong>%s</strong>.';

$lang['sys_information'] = 'Information';

$lang['err_sys_redirection'] = 'Si votre navigateur ne supporte pas les meta redirections, cliquez <a href="%s" id="redirect_link">ICI</a> pour être redirigé.';

// rstats.class
$lang['sys_rstats_elapsed'] = 'Durée: %0.4fs';
$lang['sys_rstats_location'] = 'Dans <strong>%s</strong> à la ligne <strong>%s</strong>';
$lang['sys_rstats_title_location'] = '<strong>%s</strong> dans <strong>%s</strong> à la ligne <strong>%s</strong>';
$lang['sys_rstats_function_location'] = '%s() dans <strong>%s</strong> à la ligne <strong>%s</strong>';

$lang['sys_rstats_db'] = 'requêtes bdd: %d en %0.4fs';
$lang['sys_rstats_db_caches'] = 'requêtes en cache: %d en %0.4fs';
$lang['sys_rstats_db_debug'] = 'requêtes SQL';
$lang['sys_rstats_cache_elapsed'] = 'Durée sur les cache: %0.4fs';
$lang['sys_rstats_db_elapsed'] = 'sur la bdd: %0.4fs';

$lang['sys_rstats_script_debug'] = 'Appelants';
$lang['sys_rstats_script_at_time'] = 'à: %0.4fs';
$lang['sys_rstats_script_elapsed'] = 'Ecoulé depuis le précédent: %0.4fs';

// error.mixed
$lang['error_warnings'] = 'Avertissements';
$lang['error_error'] = 'Erreur';
$lang['error_warnings_additional'] = 'Avertissement additionels';

$lang['E_ERROR'] = '<strong>Erreur fatale:</strong> %s dans <strong>%s</strong> à la ligne <strong>%s</strong>';
$lang['E_WARNING'] = '<strong>Avertissement:</strong> %s dans <strong>%s</strong> à la ligne <strong>%s</strong>';
$lang['E_PARSE'] = '<strong>Erreur d\'interprétation:</strong> %s dans <strong>%s</strong> à la ligne <strong>%s</strong>';
$lang['E_NOTICE'] = '<strong>Information:</strong> %s dans <strong>%s</strong> à la ligne <strong>%s</strong>';
$lang['E_CORE_ERROR'] = '<strong>Erreur interne:</strong> %s dans <strong>%s</strong> à la ligne <strong>%s</strong>';
$lang['E_CORE_WARNING'] = '<strong>Avertissement interne:</strong> %s dans <strong>%s</strong> à la ligne <strong>%s</strong>';
$lang['E_COMPILE_ERROR'] = '<strong>Erreur de compilation:</strong> %s dans <strong>%s</strong> à la ligne <strong>%s</strong>';
$lang['E_STRICT'] = '<strong>Erreur de syntaxe stricte:</strong> %s dans <strong>%s</strong> à la ligne <strong>%s</strong>';
$lang['E_RECOVERABLE_ERROR'] = '<strong>Erreur recouvrable:</strong> %s dans <strong>%s</strong> à la ligne <strong>%s</strong>';

$lang['err_error_exception_msg'] = 'Contexte incorrect: l\'erreur <strong>%s</strong> a eu lieu sans que l\'environnement soit initialisé dans <strong>%s</strong> à la ligne <strong>%s</strong> avec le message suivant: %s';
$lang['err_error_unknown_sysmsg'] = '<strong>Erreur inconnue (%s)</strong> dans <strong>%s</strong> à la ligne <strong>%s</strong>: %s';

// tpl.class
$lang['err_tpl_empty'] = 'sys_tpl: Le fichier <strong>%s</strong> est vide ou illisible.';
$lang['err_tpl_not_exists'] = 'sys_tpl: Il n\'y a pas de fichier nommé <strong>%s</strong> (plus l\'extension du style) dans aucun des styles déclarés.';

// db.class
$lang['err_db_error'] = 'sys_db a rapporté l\'erreur suivante dans <strong>%s</strong> à la ligne <strong>%s</strong>:<br />Erreur <strong>%s</strong>: %s<br />';
$lang['err_db_error_sql'] = 'sys_db a rapporté l\'erreur suivante dans <strong>%s</strong> à la ligne <strong>%s</strong>:<br />Erreur <strong>%s</strong>: %s<br />Requête: %s<br />';
$lang['err_db_no_values'] = 'No rows to insert.';

$lang['err_db_mysql_too_low'] = 'sys_db_mysql: la version de mySQL est trop basse. Le niveau minimal de version requis est 3.23.0 pour l\'utilisation de mySQL.';
$lang['err_db_pgsql_php_too_low'] = 'sys_db_pgsql: la version de PHP est trop basse. Le niveau minimal de version requis est 4.1.0 pour l\'utilisation de PostgreSQL.';

// xml.class
$lang['err_xml_empty'] = 'sys_xml_parser: xml vide.';
$lang['err_xml_no_tags'] = 'sys_xml_parser: pas de balises.';
$lang['err_xml_unmatched_tag'] = 'sys_xml_parser: balise orpheline #%d: %s.';
$lang['err_xml_cdata_mixed'] = 'sys_xml_parser: cdata mélangé à des enfants pour la balise #%d: %s.';
$lang['err_xml_error'] = 'sys_xml_parser a rencontré l\'erreur suivante: %s à la ligne <strong>%d</strong> pour la ressource %s.';

// pagination.class
$lang['sys_pagination_current'] = 'Page <strong>%d</strong> sur <strong>%d</strong>';
$lang['sys_pagination_previous'] = 'Précédente';
$lang['sys_pagination_next'] = 'Suivante';
$lang['sys_pagination_goto_previous'] = 'Aller à la page précédente';
$lang['sys_pagination_goto_next'] = 'Aller à la page suivante';
$lang['sys_pagination_goto_page'] = 'Aller à la page %d';
$lang['sys_pagination_goto_first'] = 'Aller à la première page';
$lang['sys_pagination_goto_last'] = 'Aller à la dernière page';

?>