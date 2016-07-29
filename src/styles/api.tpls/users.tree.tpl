<!-- BEGIN user -->

<!-- PHP -->
{PAGE_PREV_BLOCK} = false;
{PAGE_NEXT_BLOCK} = false;
<!-- ENDPHP -->
<!-- IF {user.pagination} --><!-- BEGIN user.pagination -->
<!-- PHP -->
{PAGE_PREV_BLOCK} = {user.pagination.PAGE_CURRENT} > 1;
{PAGE_NEXT_BLOCK} = {user.pagination.PAGE_CURRENT} <  {user.pagination.PAGE_TOTAL};
<!-- ENDPHP -->
<!-- END user.pagination --><!-- ENDIF -->

<!-- IF !{AJAX} -->
<div id="usersearch_command">
	<div class="right">
		<div class="left" id="upageprev"<!-- IF !{PAGE_PREV_BLOCK} --> style="display: none"<!-- ENDIF -->>
			<a class="linkable" id="user_pfirst" title="%lang('sys_pagination_goto_first')"><img id="user_page_first" src="%img('cmd_page_first')" alt="&laquo;" onclick="return usersTreeAction.pageFirst();" /></a>
			<a class="linkable" id="user_pprev" title="%lang('sys_pagination_goto_previous')"><img id="user_page_previous" src="%img('cmd_page_previous')" alt="&lt;" onclick="return usersTreeAction.pagePrevious();" /></a>
		</div>
		<div class="left" id="upagesearch">&nbsp;
			<a class="linkable" id="user_psearch" title="%lang('user_search')"><img id="togglesearch" src="%img('cmd_search_open')" alt="?" onclick="return usersTreeAction.toggle();" /></a>
		</div>
		<div class="left" id="upagenext"<!-- IF !{PAGE_NEXT_BLOCK} --> style="display: none"<!-- ENDIF -->>
			<a class="linkable" id="user_pnext" title="%lang('sys_pagination_goto_next')"><img id="user_page_next" src="%img('cmd_page_next')" alt="&gt;" onclick="return usersTreeAction.pageNext();" /></a>
			<a class="linkable" id="user_plast" title="%lang('sys_pagination_goto_last')"><img id="user_page_last" src="%img('cmd_page_last')" alt="&raquo;" onclick="return usersTreeAction.pageLast();" /></a>
		</div>
	</div>
	<div>
		<img id="img_user_root_0" src="%img('cmd_user_root')" alt="" /><a id="txt_user_tree_0">%lang('user_root')&nbsp;</a>
		<div id="usearchcontainer" class="right" style="display: none;"><input tabindex="1" id="usearch" name="usearch" type="text" value="{user.FILTER}" onkeypress="return usersTreeAction.onKey(event);" style="width: 172px;" /></div>
	</div>
	<div class="clearboth tree_root">&nbsp;</div>
</div>
<!-- ENDIF -->

<div id="tree_list">
	<!-- IF {user.pagination} --><!-- BEGIN user.pagination -->
	<input type="hidden" id="tpage" name="tpage" value="{user.pagination.PAGE_TOTAL}" />
	<input type="hidden" id="ppage" name="ppage" value="{user.pagination.PAGE_PPAGE}" />
	<input type="hidden" id="curpage" name="curpage" value="{user.pagination.PAGE_CURRENT}" />
	<input type="hidden" id="prevpage" name="prevpage" value="{user.pagination.PAGE_PREV}" />
	<input type="hidden" id="nextpage" name="nextpage" value="{user.pagination.PAGE_NEXT}" />
	<!-- END user.pagination --><!-- ELSE -->
	<input type="hidden" id="tpage" name="tpage" value="0" />
	<input type="hidden" id="ppage" name="ppage" value="0" />
	<input type="hidden" id="curpage" name="curpage" value="0" />
	<input type="hidden" id="prevpage" name="prevpage" value="0" />
	<input type="hidden" id="nextpage" name="nextpage" value="0" />
	<!-- ENDIF -->

	<!-- IF {user.row} --><ul class="tree first" id="tree_root"><!-- BEGIN user.row -->
		<li class="tree" id="user_tree_{user.row.ID}">
			<a id="url_user_tree_{user.row.ID}" title="%realname({user.row.REALNAME})"><img id="img_user_tree_{user.row.ID}"<!-- IF {mute_admin} || (!{user.row.IS_ADMIN} && !{user.row.IS_GUEST} && ({user.row.ID} != {ACTOR_ID})) --> class="catchable"<!-- ENDIF --> src="<!-- IF !{mute_admin} && ({user.row.IS_ADMIN} || {user.row.IS_GUEST} || ({user.row.ID} == {ACTOR_ID})) -->%img('cmd_user_small_light')<!-- ELSE -->%img('cmd_user_small')<!-- ENDIF -->" alt="" /></a>
			<a id="txt_user_tree_{user.row.ID}" title="%realname({user.row.REALNAME})"<!-- IF {mute_admin} || (!{user.row.IS_ADMIN} && !{user.row.IS_GUEST}) --> class="linkable" href="%url(SYS_U_MODE, {MODE_BASE} . '.' . {MODE_SUB} . '.user.content', SYS_U_ITEM, {user.row.ID})"<!-- ENDIF -->>%realname({user.row.REALNAME})&nbsp;</a>
		</li>
	<!-- END user.row --></ul><!-- ENDIF -->
	<!-- BEGIN user.pagination --><div id="user_bpagination"<!-- IF !{PAGE_PREV_BLOCK} && !{PAGE_NEXT_BLOCK} --> style="display: none"<!-- ENDIF -->>
		<div class="clearboth tree_root">&nbsp;</div>
		<div id="user_bprev" class="left"<!-- IF !{PAGE_PREV_BLOCK} --> style="display: none"<!-- ENDIF -->>
			<a class="linkable" id="user_bpfirst" title="%lang('sys_pagination_goto_first')"><img id="user_bpage_first" src="%img('cmd_page_first')" alt="&laquo;" onclick="return usersTreeAction.pageFirst();" /></a>
			<a class="linkable" id="user_bpprev" title="%lang('sys_pagination_goto_previous')"><img id="user_bpage_previous" src="%img('cmd_page_previous')" alt="&lt;" onclick="return usersTreeAction.pagePrevious();" /></a>
		</div>
		<div id="user_bnext" class="right"<!-- IF !{PAGE_NEXT_BLOCK} --> style="display: none"<!-- ENDIF -->>
			<a class="linkable" id="user_bpnext" title="%lang('sys_pagination_goto_next')"><img id="user_bpage_next" src="%img('cmd_page_next')" alt="&gt;" onclick="return usersTreeAction.pageNext();" /></a>
			<a class="linkable" id="user_bplast" title="%lang('sys_pagination_goto_last')"><img id="user_bpage_last" src="%img('cmd_page_last')" alt="&raquo;" onclick="return usersTreeAction.pageLast();" /></a>
		</div>
		<div class="small center">%lang('sys_pagination_current', {user.pagination.PAGE_CURRENT}, {user.pagination.PAGE_TOTAL})</div>
	</div><!-- END user.pagination -->
</div>
<!-- END user -->