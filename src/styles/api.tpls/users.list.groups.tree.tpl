<!-- BEGIN grp -->
<!-- IF {grp.FULL_TREE} -->
	<!-- IF !{AJAX} --><ul class="tree first" id="group_tree_root"><!-- ENDIF -->
	<li id="node_group_tree_0">
		<span id="txt_group_tree_0"><img id="img_group_root_0" src="%img('cmd_group_breadscrumb')" alt="" />&nbsp;%lang('group_root')&nbsp;</span>
		<div class="clearboth tree_root">&nbsp;</div>
	</li>
<!-- ENDIF -->
<!-- BEGIN grp.row --><!-- IF {grp.FULL_TREE} || {grp.row__IDX} -->
<li id="group_tree_{grp.row.ID}" class="tree">
<!-- ENDIF -->
	<div id="node_group_tree_{grp.row.ID}" class="nodetree tree_<!-- IF {grp.row.IS_OPENED} -->opened<!-- ELSEIF {grp.row.IS_OPENABLE} -->closed<!-- ELSE -->leaf<!-- ENDIF -->">
		<!-- IF {grp.row.IS_OPENED} -->
		<a id="url_group_tree_{grp.row.ID}" title="<!-- IF {grp.row.NAME_TRS} -->%lang({grp.row.NAME})<!-- ELSE -->{grp.row.NAME}<!-- ENDIF -->" href="%url(SYS_U_MODE, 'users.group.close', SYS_U_ITEM, {grp.row.ID})"><img class="actionable catchable" id="img_group_tree_{grp.row.ID}" src="%img('cmd_group_close')" alt="" /></a>
		<a id="txt_group_tree_{grp.row.ID}" title="<!-- IF {grp.row.NAME_TRS} -->%lang({grp.row.NAME})<!-- ELSE -->{grp.row.NAME}<!-- ENDIF -->" href="%url(SYS_U_MODE, 'users.group.content', SYS_U_ITEM, {grp.row.ID})" class="linkable"><!-- IF {grp.row.NAME_TRS} -->%lang({grp.row.NAME})<!-- ELSE -->{grp.row.NAME}<!-- ENDIF --></a>
		<!-- ELSEIF {grp.row.IS_OPENABLE} -->
		<a id="url_group_tree_{grp.row.ID}" title="<!-- IF {grp.row.NAME_TRS} -->%lang({grp.row.NAME})<!-- ELSE -->{grp.row.NAME}<!-- ENDIF -->" href="%url(SYS_U_MODE, 'users.group.open', SYS_U_ITEM, {grp.row.ID})"><img class="actionable catchable" id="img_group_tree_{grp.row.ID}" src="%img('cmd_group_open')" alt="" /></a>
		<a id="txt_group_tree_{grp.row.ID}" title="<!-- IF {grp.row.NAME_TRS} -->%lang({grp.row.NAME})<!-- ELSE -->{grp.row.NAME}<!-- ENDIF -->" href="%url(SYS_U_MODE, 'users.group.content', SYS_U_ITEM, {grp.row.ID})" class="linkable actionable"><!-- IF {grp.row.NAME_TRS} -->%lang({grp.row.NAME})<!-- ELSE -->{grp.row.NAME}<!-- ENDIF --></a>
		<!-- ELSE -->
		<a id="url_group_tree_{grp.row.ID}" title="<!-- IF {grp.row.NAME_TRS} -->%lang({grp.row.NAME})<!-- ELSE -->{grp.row.NAME}<!-- ENDIF -->"><img class="catchable" id="img_group_tree_{grp.row.ID}" src="%img('cmd_group_empty')" alt="" /></a>
		<a id="txt_group_tree_{grp.row.ID}" title="<!-- IF {grp.row.NAME_TRS} -->%lang({grp.row.NAME})<!-- ELSE -->{grp.row.NAME}<!-- ENDIF -->" href="%url(SYS_U_MODE, 'users.group.content', SYS_U_ITEM, {grp.row.ID})" class="linkable"><!-- IF {grp.row.NAME_TRS} -->%lang({grp.row.NAME})<!-- ELSE -->{grp.row.NAME}<!-- ENDIF --></a>
		<!-- ENDIF -->
	</div>
<!-- BEGIN grp.row.close -->
</li></ul>
<!-- END grp.row.close -->
	<!-- IF {grp.row.IS_OPENED} --><ul class="tree" id="childs_group_tree_{grp.row.ID}"><!-- ELSEIF {grp.FULL_TREE} || ({grp.row__IDX} < {grp.row__COUNT} - 1) --></li><!-- ENDIF -->
<!-- END grp.row -->
<!-- IF {grp.FULL_TREE} && !{AJAX} --></ul><!-- IF !{grp.row} --><br /><!-- ENDIF --><!-- ENDIF -->
<!-- END grp -->