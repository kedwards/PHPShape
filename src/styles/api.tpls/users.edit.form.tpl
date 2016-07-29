<!-- INCLUDE page.header -->
<!-- IF {warnings} --><!-- INCLUDE page.warnings --><!-- ENDIF -->
<form id="post" method="post" action="%url()">
<!-- IF {MODE_SUB} == 'delete' -->
	<fieldset class="form"><legend class="form">%lang('user_edit')</legend>
		<dl class="form"><dt class="form tab"><label class="form" for="name">%lang('user_ident')</label>:&nbsp;</dt><dd id="name" class="form tab">{NAME}</dd></dl>
		<dl class="form"><dt class="form tab"><label class="form" for="realname">%lang('user_realname')</label>:&nbsp;</dt><dd id="realname" class="form tab">%realname({REALNAME})</dd></dl>
		<dl class="form"><dt class="form tab"><label class="form" for="email">%lang('user_email')</label>:&nbsp;</dt><dd id="email" class="form tab">{EMAIL}</dd></dl>
		<dl class="form"><dt class="form tab"><label class="form" for="phone">%lang('user_phone')</label>:&nbsp;</dt><dd id="phone" class="form tab">{PHONE}</dd></dl>
		<dl class="form"><dt class="form tab"><label class="form" for="location">%lang('user_location')</label>:&nbsp;</dt><dd id="location" class="form tab"><pre>{LOCATION}</pre></dd></dl>
		<div id="form_actions">&nbsp;
			<input type="submit" name="submit_form" value="%lang('cmd_delete')" />&nbsp;
			<input type="submit" name="cancel_form" value="%lang('cmd_cancel')" />&nbsp;
			<!-- IF {hidden_field} --><!-- INCLUDE form.hidden_fields --><!-- ENDIF -->
		</div>
	</fieldset>
<!-- ELSE -->
	<fieldset class="form"><legend class="form"><!-- IF {SELF} -->%lang('user_profile')<!-- ELSE -->%lang('user_edit')<!-- ENDIF --></legend>
		<dl class="form"><dt class="form tab"><label class="form" for="name">%lang('user_ident')</label>:&nbsp;</dt><dd class="form tab"><input type="text" id="name" name="name" value="{NAME}" size="40" /></dd></dl>
		<!-- IF {activation} --><dl class="form"><dt class="form tab"><label class="form" for="enable">%lang('user_enable')</label>:&nbsp;</dt><dd class="form tab"><input type="checkbox" id="enable" name="enable"<!-- IF {ENABLE} --> checked="checked"<!-- ENDIF --> /></dd></dl><!-- ENDIF -->
	<!-- IF !{NEW} -->
		<dl class="form"><dt class="form tab"><label class="form" for="password_current">%lang('user_password_current')</label>:&nbsp;</dt><dd class="form tab"><input type="password" id="password_trap" name="password_trap" style="display: none" value="" /><input type="password" id="password_current" name="password_current" value="" /></dd></dl>
	</fieldset>
	<fieldset class="form"><legend class="form">%lang('user_password_change')</legend>
		<div class="small">%lang('user_password_onchange')</div>
	<!-- ENDIF -->
		<dl class="form"><dt class="form tab"><label class="form" for="password_new"><!-- IF {NEW} -->%lang('user_password_create')<!-- ELSE -->%lang('user_password_new')<!-- ENDIF --></label>:&nbsp;</dt><dd class="form tab"><input type="password" id="password_new" name="password_new" value="" /></dd></dl>
		<dl class="form"><dt class="form tab"><label class="form" for="password_confirm"><!-- IF {NEW} -->%lang('user_password_confirm_create')<!-- ELSE -->%lang('user_password_confirm')<!-- ENDIF --></label>:&nbsp;</dt><dd class="form tab"><input type="password" id="password_confirm" name="password_confirm" value="" /></dd></dl>
	</fieldset>
	<fieldset class="form"><legend class="form">%lang('user_informations')</legend>
		<dl class="form"><dt class="form tab"><label class="form" for="first_name">%lang('user_realname')</label>:&nbsp;</dt><dd class="form tab"><input type="text" id="first_name" name="first_name" value="{FIRST_NAME}" size="20" />&nbsp;<input type="text" id="last_name" name="last_name" value="{LAST_NAME}" size="40" /></dd></dl>
		<dl class="form"><dt class="form tab"><label class="form" for="email">%lang('user_email')</label>:&nbsp;</dt><dd class="form tab"><input type="text" id="email" name="email" value="{EMAIL}" size="50" /></dd></dl>
		<dl class="form"><dt class="form tab"><label class="form" for="phone">%lang('user_phone')</label>:&nbsp;</dt><dd class="form tab"><input type="text" id="phone" name="phone" value="{PHONE}" size="40" /></dd></dl>
		<dl class="form"><dt class="form tab"><label class="form" for="location">%lang('user_location')</label>:&nbsp;</dt><dd class="form tab"><textarea id="location" name="location" cols="80" rows="3">{LOCATION}</textarea></dd></dl>
	</fieldset>
	<!-- IF {langs} || {timeshifts} -->
	<fieldset class="form"><legend class="form">%lang('user_preferences')</legend>
		<!-- IF {langs} --><dl class="form"><dt class="form tab"><label class="form" for="lang">%lang('user_lang')</label>:&nbsp;</dt><dd class="form tab"><select id="lang" name="lang">
			<option value=""<!-- IF {LANG} === '' --> selected="selected"<!-- ENDIF -->>%lang('default_language')</option>
			<!-- BEGIN langs -->
			<option value="{langs.VALUE}"<!-- IF {langs.VALUE} == {LANG} --> selected="selected"<!-- ENDIF -->>{langs.DESC}</option>
		<!-- END langs --></select></dd></dl><!-- ENDIF -->
		<!-- IF {timeshifts} -->
		<dl class="form"><dt class="form tab"><label class="form" for="timeshift">%lang('user_timeshift')</label>:&nbsp;</dt><dd class="form tab"><select id="timeshift" name="timeshift"><!-- BEGIN timeshifts -->
			<option value="{timeshifts.VALUE}"<!-- IF {timeshifts.VALUE} === {TIMESHIFT} --> selected="selected"<!-- ENDIF -->><!-- IF {timeshifts.VALUE} === '' -->%lang('default_timezone')<!-- ELSE -->{timeshifts.DESC}<!-- ENDIF --></option>
		<!-- END timeshifts --></select></dd></dl>
		<dl class="form"><dt class="form tab"><label class="form" for="timeshift_disable">%lang('user_timeshift_disable')</label>:&nbsp;</dt><dd class="form tab"><input type="checkbox" id="timeshift_disable" name="timeshift_disable" value="1"<!-- IF {TIMESHIFT_DISABLE} --> checked="checked"<!-- ENDIF --> /></dd></dl>
		<!-- ENDIF -->
	</fieldset>
	<!-- ENDIF -->
	<fieldset class="form form_actions_box">
		<div id="form_actions">&nbsp;
			<input type="submit" name="submit_form" value="%lang('cmd_submit')" />&nbsp;
			<input type="submit" name="cancel_form" value="%lang('cmd_cancel')" />&nbsp;
			<!-- IF {hidden_field} --><!-- INCLUDE form.hidden_fields --><!-- ENDIF -->
		</div>
	</fieldset>
<!-- ENDIF -->
</form>
<!-- INCLUDE page.footer -->