<!-- BEGIN grp -->
<!-- IF {grp.FULL_TREE} && !{AJAX} -->
<ul class="breadscrumb">
	<li id="node_group_tree_0" class="breadscrumb">
		<a id="txt_group_tree_0" title="%lang('group_root')">
			<img id="img_group_root_0" src="%img('cmd_group_breadscrumb')" alt="%lang('group_root')" />
			%lang('group_root')
		</a>
	</li>
</ul>
<div class="clearboth tree_root">&nbsp;</div>
<!-- ENDIF -->

<!-- IF {grp.FULL_TREE} && !{AJAX} && {grp.row} --><ul id="group_tree_root" class="tree first"><!-- ENDIF -->

<!-- BEGIN grp.row -->
	<!-- IF {grp.FULL_TREE} || {grp.row__IDX} --><li id="group_tree_{grp.row.ID}" class="tree"><!-- ENDIF -->

	<div id="node_group_tree_{grp.row.ID}" class="nodetree tree_<!-- IF {grp.row.IS_OPENED} -->opened<!-- ELSEIF {grp.row.IS_OPENABLE} -->closed<!-- ELSE -->leaf<!-- ENDIF -->">
		<!-- IF {grp.row.IS_OPENED} -->
		<a id="url_group_tree_{grp.row.ID}" title="<!-- IF {grp.row.NAME_TRS} -->%lang({grp.row.NAME})<!-- ELSE -->{grp.row.NAME}<!-- ENDIF -->" href="%url(SYS_U_MODE, {MODE_BASE} . '.' . {MODE_SUB} . '.group.close', SYS_U_ITEM, {grp.row.ID})"><img id="img_group_tree_{grp.row.ID}" class="actionable catchable" src="<!-- IF {grp.row.CAN_MANAGE} -->%img('cmd_group_close')<!-- ELSE -->%img('cmd_group_light_close')<!-- ENDIF -->" alt="" /></a>
		<a id="txt_group_tree_{grp.row.ID}" class="linkable" title="<!-- IF {grp.row.NAME_TRS} -->%lang({grp.row.NAME})<!-- ELSE -->{grp.row.NAME}<!-- ENDIF -->" href="%url(SYS_U_MODE, {MODE_BASE} . '.' . {MODE_SUB} . '.group.content', SYS_U_ITEM, {grp.row.ID})"><!-- IF {grp.row.NAME_TRS} -->%lang({grp.row.NAME})<!-- ELSE -->{grp.row.NAME}<!-- ENDIF --></a>
		<!-- ELSEIF {grp.row.IS_OPENABLE} -->
		<a id="url_group_tree_{grp.row.ID}" title="<!-- IF {grp.row.NAME_TRS} -->%lang({grp.row.NAME})<!-- ELSE -->{grp.row.NAME}<!-- ENDIF -->" href="%url(SYS_U_MODE, {MODE_BASE} . '.' . {MODE_SUB} . '.group.open', SYS_U_ITEM, {grp.row.ID})"><img id="img_group_tree_{grp.row.ID}" class="actionable catchable" src="<!-- IF {grp.row.CAN_MANAGE} -->%img('cmd_group_open')<!-- ELSE -->%img('cmd_group_light_open')<!-- ENDIF -->" alt="" /></a>
		<a id="txt_group_tree_{grp.row.ID}" class="linkable actionable" title="<!-- IF {grp.row.NAME_TRS} -->%lang({grp.row.NAME})<!-- ELSE -->{grp.row.NAME}<!-- ENDIF -->" href="%url(SYS_U_MODE, {MODE_BASE} . '.' . {MODE_SUB} . '.group.content', SYS_U_ITEM, {grp.row.ID})"><!-- IF {grp.row.NAME_TRS} -->%lang({grp.row.NAME})<!-- ELSE -->{grp.row.NAME}<!-- ENDIF --></a>
		<!-- ELSE -->
		<a id="url_group_tree_{grp.row.ID}" title="<!-- IF {grp.row.NAME_TRS} -->%lang({grp.row.NAME})<!-- ELSE -->{grp.row.NAME}<!-- ENDIF -->"><img id="img_group_tree_{grp.row.ID}" class="catchable" src="<!-- IF {grp.row.CAN_MANAGE} -->%img('cmd_group_empty')<!-- ELSE -->%img('cmd_group_light')<!-- ENDIF -->" alt="" /></a>
		<a id="txt_group_tree_{grp.row.ID}" class="linkable" title="<!-- IF {grp.row.NAME_TRS} -->%lang({grp.row.NAME})<!-- ELSE -->{grp.row.NAME}<!-- ENDIF -->" href="%url(SYS_U_MODE, {MODE_BASE} . '.' . {MODE_SUB} . '.group.content', SYS_U_ITEM, {grp.row.ID})"><!-- IF {grp.row.NAME_TRS} -->%lang({grp.row.NAME})<!-- ELSE -->{grp.row.NAME}<!-- ENDIF --></a>
		<!-- ENDIF -->
	</div>
	<!-- BEGIN grp.row.close --></li></ul><!-- END grp.row.close -->
	<!-- IF {grp.row.IS_OPENED} --><ul id="childs_group_tree_{grp.row.ID}" class="tree"><!-- ELSEIF {grp.FULL_TREE} || ({grp.row__IDX} < {grp.row__COUNT} - 1) --></li><!-- ENDIF -->

<!-- END grp.row -->

<!-- IF {grp.FULL_TREE} && !{AJAX} && {grp.row} --></ul><!-- ENDIF -->
<!-- END grp -->