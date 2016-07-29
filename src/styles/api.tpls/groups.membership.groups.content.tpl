<!-- IF {breadscrumb} && {CONTENT_ID} -->
<!-- PHP -->
{CPAGE_PREV_BLOCK} = false;
{CPAGE_NEXT_BLOCK} = false;
<!-- ENDPHP -->
<!-- IF {cpagination} --><!-- BEGIN cpagination -->
<!-- PHP -->
{CPAGE_PREV_BLOCK} = {cpagination.PAGE_CURRENT} > 1;
{CPAGE_NEXT_BLOCK} = {cpagination.PAGE_CURRENT} <  {cpagination.PAGE_TOTAL};
<!-- ENDPHP -->
<!-- END cpagination --><!-- ENDIF -->
<!-- IF !{pagination_content} -->
<div class="breadscrumb_actions">
	<div class="right">
		<div class="left" id="ucpageprev"<!-- IF !{CPAGE_PREV_BLOCK} --> style="display: none"<!-- ENDIF -->>
			<a class="linkable" title="%lang('sys_pagination_goto_first')"><img id="user_cage_first" src="%img('cmd_page_first')" alt="&laquo;" onclick="return usersContentTreeAction.pageFirst();" /></a>
			<a class="linkable" title="%lang('sys_pagination_goto_previous')"><img id="user_cage_previous" src="%img('cmd_page_previous')" alt="&lt;" onclick="return usersContentTreeAction.pagePrevious();" /></a>
		</div>
		<div class="left" id="ucpagesearch">&nbsp;
			<a class="linkable" title="%lang('user_search')"><img id="ctogglesearch" src="%img('cmd_search_open')" alt="?" onclick="return usersContentTreeAction.toggle();" /></a>&nbsp;
		</div>
		<div class="left" id="ucpagenext"<!-- IF !{CPAGE_NEXT_BLOCK} --> style="display: none"<!-- ENDIF -->>
			<a class="linkable" title="%lang('sys_pagination_goto_next')"><img id="user_cage_next" src="%img('cmd_page_next')" alt="&gt;" onclick="return usersContentTreeAction.pageNext();" /></a>
			<a class="linkable" title="%lang('sys_pagination_goto_last')"><img id="user_cage_last" src="%img('cmd_page_last')" alt="&raquo;" onclick="return usersContentTreeAction.pageLast();" /></a>
		</div>
		<!-- IF {CAN_CREATE} --><div class="left">
			<a href="%url(SYS_U_MODE, 'users.create', SYS_U_GROUP, {CONTENT_ID})" title="%lang('cmd_user_create')"><img src="%img('cmd_user_create')" alt="%lang('cmd_user_create')" /></a>
		</div><!-- ENDIF -->
		<div class="clearboth">&nbsp;</div>
	</div>
	<div id="ucsearchcontainer" class="right" style="display: none; clear: right;">
		<input id="ucsearch" name="ucsearch" type="text" value="{CONTENT_FILTER}" onkeypress="return usersContentTreeAction.onKey(event);" style="width: 100px;" />
	</div>
</div>
<ul class="breadscrumb"><!-- BEGIN breadscrumb -->
	<!-- IF {breadscrumb.ID} --><li class="breadscrumb">
		<a id="txt_group_breadscrumb_{breadscrumb.ID}" class="linkable" title="<!-- IF {breadscrumb.NAME_TRS} -->%lang({breadscrumb.NAME})<!-- ELSE -->{breadscrumb.NAME}<!-- ENDIF -->" href="%url(SYS_U_MODE, {MODE_BASE} . '.' . {MODE_SUB} . '.group.content', SYS_U_ITEM, {breadscrumb.ID})">
			<img id="img_group_breadscrumb_{breadscrumb.ID}" class="linkable" src="%img('cmd_group_breadscrumb')" alt="" />
			<!-- IF {breadscrumb.NAME_TRS} -->%lang({breadscrumb.NAME})<!-- ELSE -->%shorten({breadscrumb.NAME})<!-- ENDIF -->
		</a>
	</li><!-- ENDIF -->
<!-- END breadscrumb --></ul>
<div class="clearboth tree_root">&nbsp;</div>
<!-- ENDIF -->

<!-- ENDIF -->
<div id="tree_content_list">
	<!-- IF {cpagination} --><!-- BEGIN cpagination -->
	<input type="hidden" id="ctpage" name="ctpage" value="{cpagination.PAGE_TOTAL}" />
	<input type="hidden" id="cppage" name="cppage" value="{cpagination.PAGE_PPAGE}" />
	<input type="hidden" id="ccurpage" name="ccurpage" value="{cpagination.PAGE_CURRENT}" />
	<input type="hidden" id="cprevpage" name="cprevpage" value="{cpagination.PAGE_PREV}" />
	<input type="hidden" id="cnextpage" name="cnextpage" value="{cpagination.PAGE_NEXT}" />
	<!-- END cpagination --><!-- ELSE -->
	<input type="hidden" id="ctpage" name="ctpage" value="0" />
	<input type="hidden" id="cppage" name="cppage" value="0" />
	<input type="hidden" id="ccurpage" name="ccurpage" value="0" />
	<input type="hidden" id="cprevpage" name="cprevpage" value="0" />
	<input type="hidden" id="cnextpage" name="cnextpage" value="0" />
	<!-- ENDIF -->
	<input type="hidden" id="contenttype" name="contenttype" value="<!-- PHP echo SYS_U_GROUP; -->" />
	<input type="hidden" id="contentid" name="contentid" value="{CONTENT_ID}" />

	<div id="tree_content_root" class="treecontent"><!-- IF {content} -->
		<ul id="treecontent_list" class="treecontent"><!-- BEGIN content -->
			<li id="tree_user_content_{content.ID}" class="treecontent usercontent">
				<div id="node_user_content_{content.ID}" class="nodecontent">
						<div><img id="img_user_content_{content.ID}" class="catchable linkable" src="<!-- IF {content.IS_OWN} -->%img('cmd_user_content')<!-- ELSE -->%img('cmd_user_content_light')<!-- ENDIF -->" alt="" /></div>
						<fieldset class="nodecontent" style="height: 51px;">
							<!-- IF {content.CAN_EDIT} --><div class="content_left_actions"><a href="%url(SYS_U_MODE, 'users.edit', SYS_U_USER, {content.ID}, SYS_U_GROUP, {CONTENT_ID})" title="%lang('cmd_user_edit')"><img src="%img('cmd_user_edit')" alt="%lang('cmd_user_edit')" /></a></div><!-- ENDIF -->
							<!-- IF {content.CAN_DELETE} --><div class="content_right_actions"><a href="%url(SYS_U_MODE, 'users.delete', SYS_U_USER, {content.ID}, SYS_U_GROUP, {CONTENT_ID})" title="%lang('cmd_user_delete')"><img src="%img('cmd_user_delete')" alt="%lang('cmd_user_delete')" /></a></div><!-- ENDIF -->
							<div class="content_actions"><a id="txt_user_content_{content.ID}" class="catchable linkable" title="%realname({content.REALNAME})" href="%url(SYS_U_MODE, {MODE_BASE} . '.' . {MODE_SUB} . '.user.content', SYS_U_ITEM, {content.ID})">%shorten({content.REALNAME})</a></div>
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

	<!-- BEGIN cpagination -->
	<div id="user_cbpagination"<!-- IF !{CPAGE_PREV_BLOCK} && !{CPAGE_NEXT_BLOCK} --> style="display: none"<!-- ENDIF -->>
		<div class="clearboth tree_root">&nbsp;</div>
		<div id="user_cbprev" class="left"<!-- IF !{CPAGE_PREV_BLOCK} --> style="display: none"<!-- ENDIF -->>
			<a class="linkable" id="user_cbpfirst" title="%lang('sys_pagination_goto_first')"><img id="user_cbpage_first" src="%img('cmd_page_first')" alt="&laquo;" onclick="return usersContentTreeAction.pageFirst();" /></a>
			<a class="linkable" id="user_cbpprev" title="%lang('sys_pagination_goto_previous')"><img id="user_cbpage_previous" src="%img('cmd_page_previous')" alt="&lt;" onclick="return usersContentTreeAction.pagePrevious();" /></a>
		</div>
		<div id="user_cbnext" class="right"<!-- IF !{CPAGE_NEXT_BLOCK} --> style="display: none"<!-- ENDIF -->>
			<a class="linkable" id="user_cbpnext" title="%lang('sys_pagination_goto_next')"><img id="user_cbpage_next" src="%img('cmd_page_next')" alt="&gt;" onclick="return usersContentTreeAction.pageNext();" /></a>
			<a class="linkable" id="user_cbplast" title="%lang('sys_pagination_goto_last')"><img id="user_cbpage_last" src="%img('cmd_page_last')" alt="&raquo;" onclick="return usersContentTreeAction.pageLast();" /></a>
		</div>
		<div class="small center">%lang('sys_pagination_current', {cpagination.PAGE_CURRENT}, {cpagination.PAGE_TOTAL})</div>
	</div><!-- END cpagination -->
</div>