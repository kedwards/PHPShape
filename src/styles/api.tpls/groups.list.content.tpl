<!-- IF {CAN_CREATE} --><div class="breadscrumb_actions">
	<a href="%url(SYS_U_MODE, 'groups.create', SYS_U_ITEM, {CONTENT_ID})" title="%lang('cmd_group_create')"><img src="%img('cmd_group_create')" alt="%lang('cmd_group_create')" /></a>
</div><!-- ENDIF -->
<!-- IF {breadscrumb} --><ul class="breadscrumb"><!-- BEGIN breadscrumb -->
	<li class="breadscrumb">
		<a id="txt_breadscrumb_{breadscrumb.ID}" class="linkable" title="<!-- IF {breadscrumb.NAME_TRS} -->%lang({breadscrumb.NAME})<!-- ELSE -->{breadscrumb.NAME}<!-- ENDIF -->" href="%url(SYS_U_MODE, 'groups.content', SYS_U_ITEM, {breadscrumb.ID})">
			<img id="img_breadscrumb_{breadscrumb.ID}" class="linkable" src="%img('cmd_group_breadscrumb')" alt="" />
			<!-- IF {breadscrumb.NAME_TRS} -->%lang({breadscrumb.NAME})<!-- ELSE -->%shorten({breadscrumb.NAME})<!-- ENDIF -->
		</a>
	</li>
<!-- END breadscrumb --></ul><!-- ENDIF -->
<!-- IF {CAN_CREATE} || {breadscrumb} --><div class="clearboth tree_root">&nbsp;</div><!-- ENDIF -->

<input type="hidden" name="contenttype" id="contenttype" value="<!-- PHP echo SYS_U_GROUP; -->" />
<input type="hidden" name="contentid" id="contentid" value="{CONTENT_ID}" />

<div id="tree_content_{CONTENT_ID}" class="treecontent" style="white-space: normal"><!-- IF {content} -->
	<ul id="treecontent_list" class="treecontent"><!-- BEGIN content -->
		<li id="content_{content.ID}" class="treecontent"><div id="node_content_{content.ID}" class="nodecontent">
			<span id="url_content_{content.ID}" class="linkable"><img id="img_content_{content.ID}" class="linkable catchable" src="%img('cmd_group_content')" alt="" /></span><br />
			<fieldset><div style="white-space: normal; height: 51px; overflow: hidden; margin: 0; padding: 0.3em;">
				<!-- IF {content.CAN_EDIT} --><div class="content_left_actions">
					<a href="%url(SYS_U_MODE, 'groups.edit', SYS_U_ITEM, {content.ID})" title="%lang('cmd_group_edit')"><img src="%img('cmd_group_edit')" alt="%lang('cmd_group_edit')" /></a>
				</div><!-- ENDIF -->
				<!-- IF {content.CAN_DELETE} --><div class="content_right_actions">
					<a href="%url(SYS_U_MODE, 'groups.delete', SYS_U_ITEM, {content.ID})" title="%lang('cmd_group_delete')"><img src="%img('cmd_group_delete')" alt="%lang('cmd_group_delete')" /></a>
				</div><!-- ENDIF -->
				<a id="txt_content_{content.ID}" class="linkable" title="<!-- IF {content.NAME_TRS} -->%lang({content.NAME})<!-- ELSE -->{content.NAME}<!-- ENDIF -->" href="%url(SYS_U_MODE, 'groups.content', SYS_U_ITEM, {content.ID})"><!-- IF {content.NAME_TRS} -->%lang({content.NAME})<!-- ELSE -->%shorten({content.NAME})<!-- ENDIF --></a>
				<div class="clearboth">&nbsp;</div>
			</div></fieldset>
		</div></li>
		<!-- END content -->
		<li id="tree_content_add" class="treecontentadd">&nbsp;</li>
		<li class="clearboth">&nbsp;</li>
	</ul><!-- ENDIF -->
	<div class="clearboth">&nbsp;</div>
	<!-- IF !{content} --><div id="tree_content_add" class="treecontentadd">&nbsp;</div><!-- ENDIF -->
</div>