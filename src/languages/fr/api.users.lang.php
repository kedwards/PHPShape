<?php
//
//	file: languages/fr/users.lang.php
//	author: Kevin Edwards - https://www.kncedwards.com/phpshape
//	begin: 28/09/2008
//	version: 0.0.3 - 01/08/2010
//	license: http://opensource.org/licenses/gpl-3.0.html GNU General Public License version 3 (GPLv3)
//

if ( !defined('SYS') )
{
	die('Not allowed');
}

$lang['user_login'] = 'Se connecter';
$lang['user_ident'] = 'Votre identifiant';
$lang['user_password'] = 'Votre mot de passe';
$lang['user_remember'] = 'Se rappeler de moi';

$lang['user_password_lost'] = 'J\'ai oublié mon mot de passe !';
$lang['user_password_renew'] = 'Recevoir un nouveau mot de passe';
$lang['user_password_renewed'] = 'Vous allez bientôt recevoir un email avec votre nouveau mot de passe.';

$lang['user_login_message'] = 'Vous êtes à présent connecté.';
$lang['user_logout_message'] = 'Vous êtes à présent déconnecté. Merci de votre visite.';

$lang['err_user_login_required'] = 'Merci d\'entrer votre code utilisateur et votre mot de passe.';
$lang['err_user_max_login'] = 'Le nombre maximal de tentatives de signature a été atteint. Merci de réessayer plus tard.';
$lang['err_user_login_not_found'] = 'Le code utilisateur et le mot de passe que vous avez entré ne correspondent à aucun utilisateur. Merci de réessayer.';

$lang['user_activation_message'] = 'Votre profil utilisateur a été activé, et vous êtes à présent connecté. Bienvenue !';
$lang['err_user_actkey_no_match'] = 'Le lien d\'activation que vous avez suivi ne correspond pas à vos informations de connexion.';
$lang['err_user_not_activated'] = 'Votre profil n\'a pas encore été activé. Un e-mail de confirmation avec ce lien d\'activation vous a été envoyé lors de votre enregistrement. Si vous n\'avez pas reçu cet e-mail, merci de le signaler en suivant ce lien : <a href="%s">Je n\'ai pas reçu de mail de confirmation</a>';
$lang['user_login_report_done'] = 'Le problème que vous rencontrez pour activer votre profil a été transmis à l\'administrateur.';
$lang['user_enable'] = 'Profil actif';
$lang['user_created_profile_inactive'] = 'Vous avez terminé l\'enregistrement. Cependant, votre profil est pour l\'instant inactivé. Un mail de confirmation vous a été envoyé à l\'adresse email que vous avez fournie, avec un lien pour activer votre profil utilisateur.';

$lang['user_profile'] = 'Vos informations de connexion';
$lang['user_edit'] = 'Information de connexion';
$lang['user_password_current'] = 'Votre mot de passe actuel';

$lang['user_password_change'] = 'Votre nouveau mot de passe';
$lang['user_password_onchange'] = 'Entrez un nouveau mot de passe uniquement si vous souhaitez en changer.';
$lang['user_password_new'] = 'Votre nouveau mot de passe';
$lang['user_password_confirm'] = 'Confirmez votre nouveau mot de passe';
$lang['user_password_create'] = 'Votre mot de passe';
$lang['user_password_confirm_create'] = 'Confirmez votre mot de passe';

$lang['user_informations'] = 'Qui êtes-vous?';
$lang['user_realname'] = 'Prénom &amp; Nom';
$lang['user_firstname'] = 'Prénom';
$lang['user_lastname'] = 'Nom';
$lang['user_email'] = 'E-mail';
$lang['user_phone'] = 'Téléphone';
$lang['user_location'] = 'Ville';
$lang['user_regdate'] = 'Enregistrement';
$lang['user_connection'] = 'Dernière connexion';

$lang['user_preferences'] = 'Vos préférences';
$lang['user_lang'] = 'Langue';
$lang['user_timeshift'] = 'Fuseau horaire';
$lang['user_timeshift_disable'] = 'Désactiver la détection du fuseau horaire';

$lang['user_updated_profile'] = 'Votre profil a été mis à jour.';
$lang['user_updated'] = 'Votre profil a été mis à jour.';
$lang['user_created_profile'] = 'Vous avez terminé l\'enregistrement. Vous pouvez maintenant vous connecter avec votre profil.';
$lang['user_created'] = 'Votre profil a été créé.';
$lang['user_deleted'] = 'Votre profil a été supprimé.';

$lang['user_never_connected'] = 'Jamais';

$lang['err_user_unknown'] = 'Utilisateur inconnu.';
$lang['err_user_self_delete'] = 'Vous ne pouvez pas supprimer votre propre profil.';
$lang['err_user_name_empty'] = 'L\'identifiant ne peut être vide';
$lang['err_user_name_not_valid'] = 'L\'identifiant que vous avez saisi n\'est pas valide.';
$lang['err_user_name_exists'] = 'L\'identifiant que vous avez saisi est déjà employé par un autre utilisateur.';

$lang['err_user_realname_empty'] = 'Vous devez saisir vos prénom et nom réels.';
$lang['err_user_realname_not_valid'] = 'Le prénom/nom que vous avez saisi n\'est pas valide.';
$lang['err_user_realname_exists'] = 'Un autre utilisateur est déjà identifié sous ce prénom et nom.';

$lang['err_user_password_empty'] = 'Vous devez saisir votre mot de passe actuel.';
$lang['err_user_password_mismatch'] = 'Le  mot de passe que vous avez entré ne correspond pas à votre profil.';
$lang['err_user_password_new_empty'] = 'Vous devez choisir un mot de passe.';
$lang['err_user_password_new_mismatch'] = 'Le  mot de passe de confirmation que vous avez saisi ne correspond pas à votre mot de passe actuel.';

$lang['err_user_email_empty'] = 'Vous devez saisir votre email.';
$lang['err_user_email_not_valid'] = 'L\'email que vous avez saisi est incorrect.';
$lang['err_user_email_exists'] = 'Un autre utilisateur est déjà enregistré avec cette adresse email.';

$lang['err_user_lang_not_available'] = 'La langue que vous avez choisie n\'est pas disponible.';
$lang['err_user_timeshift_not_available'] = 'Le fuseau horaire que vous avez choisi n\'est pas disponible.';

$lang['users_list_title'] = 'Utilisateurs';
$lang['user_root'] = 'Tous les utilisateurs';
$lang['user_search'] = 'Rechercher sur le nom';

$lang['backto_users'] = 'Retourner à l\'administration des utilisateurs';

$lang['cmd_user_create'] = 'Enregistrer un nouvel utilisateur';
$lang['cmd_user_edit'] = 'Modifier l\'utilisateur';
$lang['cmd_user_delete'] = 'Supprimer l\'utilisateur';

?>