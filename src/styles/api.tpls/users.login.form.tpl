<!-- INCLUDE page.header -->
<!-- IF {warnings} --><!-- INCLUDE page.warnings --><!-- ENDIF -->
<form id="post" method="post" action="%url()">
	<fieldset class="form"><legend class="form">%lang('user_login')</legend>
		<dl class="form"><dt class="form tab"><label class="form" for="user_ident">%lang('user_ident')</label>:&nbsp;</dt><dd class="form tab"><input type="text" id="user_ident" name="user_ident" value="{IDENT}" size="40" /></dd></dl>
		<dl class="form"><dt class="form tab"><label class="form" for="user_password">%lang('user_password')</label>:&nbsp;</dt><dd class="form tab"><input type="password" id="password_trap" name="password_trap" style="display: none" value="" /><input type="password" id="user_password" name="user_password" value="" /></dd></dl>
		<dl class="form"><dt class="form tab">&nbsp;</dt><dd class="form tab"><input type="checkbox" id="remember" name="remember" checked="checked" />&nbsp;<label class="form" for="remember">%lang('user_remember')</label></dd></dl>
		<!-- IF {PASSWORD_RENEW} -->
		<dl class="form"><dt class="form tab">&nbsp;</dt><dd class="form tab">
			<a href="%url(SYS_U_MODE, 'login.lost')" title="%lang('user_password_lost')">%lang('user_password_lost')</a><br />
		<!-- ENDIF --></dd></dl>

		<div id="form_actions">&nbsp;
			<input type="submit" name="submit_form" value="%lang('cmd_submit')" />&nbsp;
			<input type="submit" name="cancel_form" value="%lang('cmd_cancel')" />&nbsp;
			<!-- IF {hidden_field} --><!-- INCLUDE form.hidden_fields --><!-- ENDIF -->
		</div>
	</fieldset>
</form>
<!-- INCLUDE page.footer -->