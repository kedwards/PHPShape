<!-- IF {CAN_EDIT} || {CAN_DELETE} --><div class="breadscrumb_actions">
	<!-- IF {CAN_EDIT} --><a href="%url(SYS_U_MODE, 'users.edit', SYS_U_USER, {CONTENT_ID})" title="%lang('cmd_user_edit')"><img src="%img('cmd_user_edit')" alt="%lang('cmd_user_edit')" /></a>&nbsp;<!-- ENDIF -->
	<!-- IF {CAN_DELETE} --><a href="%url(SYS_U_MODE, 'users.delete', SYS_U_USER, {CONTENT_ID})" title="%lang('cmd_user_delete')"><img src="%img('cmd_user_delete')" alt="%lang('cmd_user_delete')" /></a><!-- ENDIF -->
</div><!-- ENDIF -->

<ul class="breadscrumb"><li class="breadscrumb">
	<a id="txt_user_breadscrumb_{CONTENT_ID}" title="%realname({CONTENT_NAME})" href="%url(SYS_U_MODE, {MODE_BASE} . '.' . {MODE_SUB} . '.user.content', SYS_U_ITEM, {CONTENT_ID})" class="linkable">
		<img class="linkable catchable" id="img_user_breadscrumb_{CONTENT_ID}" src="%img('cmd_user_root')" alt="" />
		{CONTENT_NAME}
	</a>
</li></ul>
<div class="clearboth tree_root">&nbsp;</div>

<input type="hidden" name="contenttype" id="contenttype" value="<!-- PHP echo SYS_U_USER; -->" />
<input type="hidden" name="contentid" id="contentid" value="{CONTENT_ID}" />

<div id="tree_content_root" class="treecontent innertree"><!-- IF {content} -->
	<ul id="treecontent_list" class="treecontent"><!-- BEGIN content -->
		<li class="tree" id="tree_group_content_{content.ID}">
			<div class="nodetree" id="node_group_content_{content.ID}">
				<a id="txt_group_content_{content.ID}" title="<!-- IF {content.NAME_TRS} -->%lang({content.NAME})<!-- ELSE -->{content.NAME}<!-- ENDIF -->" class="linkable" href="%url(SYS_U_MODE, {MODE_BASE} . '.' . {MODE_SUB} . '.group.content', SYS_U_ITEM, {content.ID})">
					<!-- IF {content.IS_PARENT} -->
					<img id="img_group_content_{content.ID}" src="%img('cmd_group_disabled')" alt="" />
					<!-- ELSEIF {content.IS_OWN} -->
					<img class="catchable" id="img_group_content_{content.ID}" src="%img('cmd_group_empty')" alt="" />
					<!-- ELSE -->
					<img class="catchable" id="img_group_content_{content.ID}" src="%img('cmd_group_light')" alt="" />
					<!-- ENDIF -->
					<!-- IF {content.NAME_TRS} -->%lang({content.NAME})<!-- ELSE -->{content.NAME}<!-- ENDIF -->
				</a>
			</div>
			<!-- BEGIN content.close --></li></ul><!-- END content.close -->
		<!-- IF {content.IS_OPENED} --><ul class="tree" id="childs_group_content_{content.ID}"><!-- ELSE --></li><!-- ENDIF -->
	<!-- END content -->
		<li class="tree" id="tree_content_more">&nbsp;</li>
		<li class="clearboth">&nbsp;</li>
	</ul><!-- ENDIF -->
	<div class="clearboth">&nbsp;</div>
	<!-- IF !{content} --><div id="tree_content_add" class="treecontentadd">&nbsp;</div><!-- ENDIF -->
</div>