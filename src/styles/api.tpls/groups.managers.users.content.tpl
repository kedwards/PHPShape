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
					<!-- IF {content.IS_MANAGER} -->
					<img id="img_group_content_{content.ID}" class="catchable" src="%img('cmd_group_empty')" alt="" />
					<!-- ELSE -->
					<img id="img_group_content_{content.ID}" src="%img('cmd_group_disabled')" alt="" />
					<!-- ENDIF -->
					<!-- IF {content.NAME_TRS} -->%lang({content.NAME})<!-- ELSE -->{content.NAME}<!-- ENDIF -->
				</a>
			</div>
			<!-- BEGIN content.close --></li></ul><!-- END content.close -->
		<!-- IF {content.IS_OPENED} --><ul class="tree" id="childs_group_content_{content.ID}"><!-- ELSE --></li><!-- ENDIF -->
	<!-- END content -->
		<!-- IF {content} --><li class="tree" id="tree_content_more">&nbsp;</li><!-- ENDIF -->
		<li class="clearboth">&nbsp;</li>
	</ul><!-- ENDIF -->
	<div class="clearboth">&nbsp;</div>
	<!-- IF !{content} --><div id="tree_content_add" class="treecontentadd">&nbsp;</div><!-- ENDIF -->
</div>