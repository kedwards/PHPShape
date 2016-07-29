<!-- BEGIN user -->
<div class="tree_root smallbox">
	<img src="%img('cmd_user_root')" alt="%lang('menu_actor')" />
	<!-- IF {user.ID} === {ACTOR_ID} -->
	<a href="%url(SYS_U_MODE, 'profile')" title="%lang('menu_actor')">%realname({user.NAME})</a>
	<!-- ELSE -->
	<a title="%lang('user_realname')">%realname({user.NAME})</a>
	<!-- ENDIF --><br />
</div>
<!-- IF {user.EMAIL} --><dl class="form"><dt class="form"><label class="form" for="email">%lang('user_email')</label>:&nbsp;</dt><dd class="form" id="email"><a href="mailto:{user.EMAIL}" title="%lang('user_email')">{user.EMAIL}</a></dd></dl><!-- ENDIF -->
<!-- IF {user.PHONE} --><dl class="form"><dt class="form"><label class="form" for="phone">%lang('user_phone')</label>:&nbsp;</dt><dd class="form" id="phone">{user.PHONE}</dd></dl><!-- ENDIF -->
<!-- IF {user.LOCATION} --><dl class="form"><dt class="form"><label class="form" for="location">%lang('user_location')</label>:&nbsp;</dt><dd class="form" id="location">{user.LOCATION}</dd></dl><!-- ENDIF -->
<!-- IF {user.REGDATE} --><dl class="form"><dt class="form"><label class="form" for="regdate">%lang('user_regdate')</label>:&nbsp;</dt><dd class="form" id="regdate">%date({user.REGDATE}, 'short')</dd></dl><!-- ENDIF -->
<dl class="form"><dt class="form"><label class="form" for="connect">%lang('user_connection')</label>:&nbsp;</dt><dd class="form" id="connect"><!-- IF {user.LAST_CONNECT} -->%date({user.LAST_CONNECT}, 'short')<!-- ELSE -->%lang('user_never_connected')<!-- ENDIF --></dd></dl>
<input type="hidden" id="selected_id" name="selected_id" value="{user.ID}" />
<div class="clearboth">&nbsp;</div>
<!-- END user -->
<!-- IF {hidden_field} --><!-- INCLUDE form.hidden_fields --><!-- ENDIF -->
