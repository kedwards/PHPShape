<!-- INCLUDE page.header -->
<!-- IF {warnings} --><!-- INCLUDE page.warnings --><!-- ENDIF -->
<form id="post" method="post" action="%url()">
	<fieldset class="form"><legend class="form"><!-- IF {MODE} == 'groups.edit' -->%lang('groups_edit')<!-- ELSEIF {MODE} == 'groups.create' -->%lang('groups_create')<!-- ELSE -->%lang('groups_delete')<!-- ENDIF --></legend>
		<!-- IF {MODE} == 'groups.delete' -->
		<dl class="form"><dt class="form tab"><label class="form" for="group_name">%lang('group_name')</label>:&nbsp;</dt><dd class="form tab" id="group_name">{NAME}</dd></dl>
		<dl class="form"><dt class="form tab"><label class="form" for="group_desc">%lang('group_desc')</label>:&nbsp;</dt><dd class="form tab" id="group_desc">{DESC}</dd></dl>
		<!-- ELSE -->
		<dl class="form"><dt class="form tab"><label class="form" for="group_name">%lang('group_name')</label>:&nbsp;</dt><dd class="form tab"><input type="text" id="group_name" name="group_name" value="{NAME}" size="40" /></dd></dl>
		<dl class="form"><dt class="form tab"><label class="form" for="group_desc">%lang('group_desc')</label>:&nbsp;</dt><dd class="form tab"><input type="text" id="group_desc" name="group_desc" value="{DESC}" size="80" /></dd></dl>
		<!-- IF {langs} --><dl class="form"><dt class="form tab"><label class="form" for="group_lang">%lang('group_lang')</label>:&nbsp;</dt><dd class="form tab"><select id="group_lang" name="group_lang">
			<option value=""<!-- IF {LANG} === '' --> selected="selected"<!-- ENDIF -->>%lang('default_language')</option>
			<!-- BEGIN langs -->
			<option value="{langs.VALUE}"<!-- IF {langs.VALUE} == {LANG} --> selected="selected"<!-- ENDIF -->>{langs.DESC}</option>
		<!-- END langs --></select></dd></dl><!-- ENDIF -->
		<!-- ENDIF -->
		<div id="form_actions">&nbsp;
			<input type="submit" name="submit_form" value="<!-- IF {MODE} == 'groups.delete' -->%lang('cmd_delete')<!-- ELSE -->%lang('cmd_submit')<!-- ENDIF -->" />&nbsp;
			<input type="submit" name="cancel_form" value="%lang('cmd_cancel')" />&nbsp;
			<!-- IF {hidden_field} --><!-- INCLUDE form.hidden_fields --><!-- ENDIF -->
		</div>
	</fieldset>
</form>
<!-- INCLUDE page.footer -->