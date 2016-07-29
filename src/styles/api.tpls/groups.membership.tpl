<!-- PHP -->
$this->add('jscripts', array('VALUE' => $sys->ini_get('mousehandler.js', 'resource')));
$this->add('jscripts', array('VALUE' => $sys->ini_get('groups.membership.js', 'resource')));
$this->add('jscripts', array('VALUE' => $sys->ini_get('users.tree.js', 'resource')));
$this->add('onload', array('FILE' => 'groups.membership.onload'));
<!-- ENDPHP -->
<!-- INCLUDE page.header -->
<!-- IF {warnings} --><!-- INCLUDE page.warnings --><!-- ENDIF -->
<form id="post" method="post" action="%url()">
	<fieldset class="tree">
		<div id="tree"><!-- INCLUDE groups.membership.groups.tree --><br /></div>
	</fieldset>
	<fieldset class="treecontent middle">
		<div id="treecontentcombined">
			<!-- IF {CONTENT_TYPE} == 'group' --><!-- INCLUDE groups.membership.groups.content -->
			<!-- ELSEIF {CONTENT_TYPE} --><!-- INCLUDE groups.membership.users.content -->
			<!-- ELSE --><p class="errormsg"><br /><br />%lang('err_group_membership_select')<br /><br /></p>
			<!-- ENDIF -->
		</div>
	</fieldset>
	<fieldset class="treeright">
		<div id="treeright"><!-- INCLUDE users.tree --></div>
	</fieldset>
	<!-- IF {hidden_field} --><div id="hidden_fields"><!-- INCLUDE form.hidden_fields --></div><!-- ENDIF -->
	<div class="clearboth">&nbsp;</div>
</form>
<div id="floatbox" style="display: none">&nbsp;</div>

<script type="text/javascript">
/*<![CDATA[*/

	var mouseHandler = new MouseHandler();
	mouseHandler.set();

	var resizeBoxes = new ResizeBoxes();
	resizeBoxes.set();
	resizeBoxes.register('tree');
	resizeBoxes.register('treecontentcombined');
	resizeBoxes.register('treeright');
	window.onresize = function (event) {
		resizeBoxes.resize();
		return true;
	}

	var dd = new ContentTree();
	dd.set(
		'dd',
		"%url_noamp(SYS_U_MODE, 'groups.membership.$1.$2.x', SYS_U_ITEM, '$3')",
		'{CONTENT_ID}',
		'{CONTENT_TYPE}',
		'floatbox',
		{
			iOpen: "%img('cmd_group_open')",
			iOpenOver: "%img('cmd_group_open_over')",
			iClose: "%img('cmd_group_close')",
			iCloseOver: "%img('cmd_group_close_over')",
			iEmpty: "%img('cmd_group_empty')",
			iEmptyOver: "%img('cmd_group_empty_over')",
			iContentLTree: "%img('cmd_group_empty')",
			iContentRTree: "%img('cmd_user_small')"
		}
	);

	var usersTreeAction = new UsersTreeAction();
	usersTreeAction.set(
		'usersTreeAction',
		'usearch',
		'togglesearch',
		'%img('cmd_search_open')',
		'%img('cmd_search_close')',
		'tpage',
		'ppage',
		'curpage',
		'prevpage',
		'nextpage',
		'upageprev',
		'upagenext',
		'', '',
		"%url_noamp(SYS_U_MODE, 'groups.membership.user.filter.x')",
		'rstart',
		'tree_list',
		'treeright'
	);

	var usersContentTreeAction = new UsersTreeAction();
	usersContentTreeAction.set(
		'usersContentTreeAction',
		'ucsearch',
		'ctogglesearch',
		'%img('cmd_search_open')',
		'%img('cmd_search_close')',
		'ctpage',
		'cppage',
		'ccurpage',
		'cprevpage',
		'cnextpage',
		'ucpageprev',
		'ucpagenext',
		'contentid',
		'<!-- PHP echo SYS_U_ITEM; -->',
		"%url_noamp(SYS_U_MODE, 'groups.membership.group.filter.x')",
		'cstart',
		'tree_content_list',
		'treecontentcombined'
	);
/*]]>*/
</script>
<!-- INCLUDE page.footer -->