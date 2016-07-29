<!-- INCLUDE page.header -->
<!-- IF {warnings} --><!-- INCLUDE page.warnings --><!-- ENDIF -->
<form id="post" method="post" action="%url()">
	<fieldset class="form"><legend class="form">%lang('user_password_renew')</legend>
		<!-- IF !{EMAIL_UNIQ} -->
		<dl class="form"><dt class="form tab"><label class="form" for="name">%lang('user_ident')</label>:&nbsp;</dt><dd class="form tab"><input type="text" id="name" name="name" value="{NAME}" size="40" /></dd></dl>
		<!-- ENDIF -->
		<dl class="form"><dt class="form tab"><label class="form" for="email">%lang('user_email')</label>:&nbsp;</dt><dd class="form tab"><input type="text" id="email" name="email" value="{EMAIL}" size="50" /></dd></dl>

		<div id="form_actions">&nbsp;
			<input type="submit" name="submit_form" value="%lang('cmd_submit')" />&nbsp;
			<input type="submit" name="cancel_form" value="%lang('cmd_cancel')" />&nbsp;
			<!-- IF {hidden_field} --><!-- INCLUDE form.hidden_fields --><!-- ENDIF -->
		</div>
	</fieldset>
</form>
<!-- INCLUDE page.footer -->