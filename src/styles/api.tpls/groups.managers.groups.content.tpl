<!-- IF {breadscrumb} && {CONTENT_ID} -->
<ul class="breadscrumb"><!-- BEGIN breadscrumb -->
	<!-- IF {breadscrumb.ID} --><li class="breadscrumb">
		<a id="txt_group_breadscrumb_{breadscrumb.ID}"<!-- IF {breadscrumb.CAN_MANAGE} --> class="linkable" href="%url(SYS_U_MODE, {MODE_BASE} . '.' . {MODE_SUB} . '.group.content', SYS_U_ITEM, {breadscrumb.ID})"<!-- ENDIF --> title="<!-- IF {breadscrumb.NAME_TRS} -->%lang({breadscrumb.NAME})<!-- ELSE -->{breadscrumb.NAME}<!-- ENDIF -->">
			<img id="img_group_breadscrumb_{breadscrumb.ID}" class="linkable" src="%img('cmd_group_breadscrumb')" alt="" />
			<!-- IF {breadscrumb.NAME_TRS} -->%lang({breadscrumb.NAME})<!-- ELSE -->%shorten({breadscrumb.NAME})<!-- ENDIF -->
		</a>
	</li><!-- ENDIF -->
<!-- END breadscrumb --></ul>
<div class="clearboth tree_root">&nbsp;</div>
<!-- ENDIF -->

<input type="hidden" name="contenttype" id="contenttype" value="<!-- PHP echo SYS_U_GROUP; -->" />
<input type="hidden" name="contentid" id="contentid" value="{CONTENT_ID}" />

<div id="tree_content_root" class="treecontent"><!-- IF {content} -->
	<ul id="treecontent_list" class="treecontent"><!-- BEGIN content -->
		<li id="tree_user_content_{content.ID}" class="treecontent usercontent">
			<div id="node_user_content_{content.ID}" class="nodecontent">
				<img id="img_user_content_{content.ID}" class="catchable linkable" src="%img('cmd_user_content')" alt="" /><br />
				<fieldset style="height: 51px;">
					<a id="txt_user_content_{content.ID}" class="catchable linkable" title="%realname({content.REALNAME})" href="%url(SYS_U_MODE, {MODE_BASE} . '.' . {MODE_SUB} . '.user.content', SYS_U_ITEM, {content.ID})">%shorten({content.REALNAME})</a>
				</fieldset>
			</div>
		</li>
		<!-- END content -->
		<li id="tree_content_add" class="treecontentadd">&nbsp;</li>
		<li class="clearboth">&nbsp;</li>
	</ul><!-- ENDIF -->
	<div class="clearboth">&nbsp;</div>
	<!-- IF !{content} --><div id="tree_content_add" class="treecontentadd">&nbsp;</div><!-- ENDIF -->
</div>