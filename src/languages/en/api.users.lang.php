<?php
//
//	file: languages/en/users.lang.php
//	author: Kevin Edwards - https://www.kncedwards.com/phpshape
//	begin: 28/09/2008
//	version: 0.0.3 - 01/08/2010
//	license: http://opensource.org/licenses/gpl-3.0.html GNU General Public License version 3 (GPLv3)
//

if ( !defined('SYS') )
{
	die('Not allowed');
}

$lang['user_login'] = 'Login';
$lang['user_ident'] = 'Login';
$lang['user_password'] = 'Password';
$lang['user_remember'] = 'Always log me in';

$lang['user_password_lost'] = 'I forgot my password!';
$lang['user_password_renew'] = 'Get a new password';
$lang['user_password_renewed'] = 'You will soon receive an email with your new password.';

$lang['user_login_message'] = 'You are now logged in.';
$lang['user_logout_message'] = 'You are now logged out. Goodbye!';

$lang['err_user_login_required'] = 'Please enter your login and password.';
$lang['err_user_max_login'] = 'The maximum login attempts allowed has been reached. Please try against later.';
$lang['err_user_login_not_found'] = 'The login name and password you entered doesn\'t match any profile. Please retry.';

$lang['user_activation_message'] = 'Your profile has been activated, and you are now logged in. Welcome!';
$lang['err_user_actkey_no_match'] = 'The activation link you have followed does not match your login information.';
$lang['err_user_not_activated'] = 'Your profile has not been yet activated. A confirmation e-mail with the activation link was sent to you when you registered. If you have not received this e-mail, please click the following link: <a href="%s">I did not receive a confirmation e-mail</a>';
$lang['user_login_report_done'] = 'The problem you are encountering with your profile activation has been reported to the administrator.';
$lang['user_enable'] = 'User is active';
$lang['user_created_profile_inactive'] = 'You have completed your registration. However, your profile is currently inactive. A confirmation email has been sent to the email address you have provided, with a link to activate your profile.';

$lang['user_profile'] = 'Your login information';
$lang['user_edit'] = 'Login information';
$lang['user_password_current'] = 'Your current password';

$lang['user_password_change'] = 'New password';
$lang['user_password_onchange'] = 'Enter a new password only if you want to change the current one.';
$lang['user_password_new'] = 'New password';
$lang['user_password_confirm'] = 'Confirm your new password';
$lang['user_password_create'] = 'Password';
$lang['user_password_confirm_create'] = 'Confirm password';

$lang['user_informations'] = 'Who are you?';
$lang['user_realname'] = 'First &amp; last name';
$lang['user_firstname'] = 'First name';
$lang['user_lastname'] = 'Last name';
$lang['user_email'] = 'Email';
$lang['user_phone'] = 'Phone';
$lang['user_location'] = 'Location';
$lang['user_regdate'] = 'Registration';
$lang['user_connection'] = 'Last connection';

$lang['user_preferences'] = 'Your preferences';
$lang['user_lang'] = 'Language';
$lang['user_timeshift'] = 'Timezone';
$lang['user_timeshift_disable'] = 'Disable timezone detection warning';

$lang['user_updated_profile'] = 'Your profile has been updated.';
$lang['user_updated'] = 'The profile has been updated.';
$lang['user_created_profile'] = 'You have completed your registration. You can now log in with your profile.';
$lang['user_created'] = 'The profile has been created.';
$lang['user_deleted'] = 'The user has been deleted.';

$lang['user_never_connected'] = 'Never';

$lang['err_user_unknown'] = 'There is no such user.';
$lang['err_user_self_delete'] = 'You can not delete your own profile.';
$lang['err_user_name_empty'] = 'The login can not be empty';
$lang['err_user_name_not_valid'] = 'The login you entered is not a valid one.';
$lang['err_user_name_exists'] = 'The login you entered already exists.';

$lang['err_user_realname_empty'] = 'You have to provide your real name.';
$lang['err_user_realname_not_valid'] = 'The first and/or last name you entered is not a valid one.';
$lang['err_user_realname_exists'] = 'The first and last name are already used by another user.';

$lang['err_user_password_empty'] = 'You have to enter your current password.';
$lang['err_user_password_mismatch'] = 'The current password you have entered does not match your profile one.';
$lang['err_user_password_new_empty'] = 'You have to choose a password.';
$lang['err_user_password_new_mismatch'] = 'The confirmation password you have entered does not match the new one.';

$lang['err_user_email_empty'] = 'You have to provide your email.';
$lang['err_user_email_not_valid'] = 'The email you have provided is not a correct one.';
$lang['err_user_email_exists'] = 'The email you have provided is already in use.';

$lang['err_user_lang_not_available'] = 'The language you have selected is not available.';
$lang['err_user_timeshift_not_available'] = 'The timezone you have selected is not available.';

$lang['users_list_title'] = 'Users';
$lang['user_root'] = 'All users';
$lang['user_search'] = 'Search on name';

$lang['backto_users'] = 'Back to users administration';

$lang['cmd_user_create'] = 'Register a new user';
$lang['cmd_user_edit'] = 'Edit the user';
$lang['cmd_user_delete'] = 'Delete the user';

?>