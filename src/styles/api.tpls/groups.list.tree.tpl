<!-- IF {FULL_TREE} && !{AJAX} -->
<ul class="breadscrumb">
	<li id="node_tree_0" class="breadscrumb">
		<a id="txt_tree_0" title="%lang('group_root')" href="%url(SYS_U_MODE, 'groups.content')" class="linkable">
			<img id="img_root_0" class="linkable actionable" src="%img('cmd_group_breadscrumb')" alt="%lang('group_root')" />
			%lang('group_root')
		</a>
	</li>
</ul>
<div class="clearboth tree_root">&nbsp;</div>
<!-- ENDIF -->

<!-- IF {FULL_TREE} && !{AJAX} && {row} --><ul id="tree_root" class="tree first"><!-- ENDIF -->

<!-- BEGIN row -->
	<!-- IF {FULL_TREE} || {row__IDX} --><li id="tree_{row.ID}" class="tree"><!-- ENDIF -->

	<div id="node_tree_{row.ID}" class="nodetree tree_<!-- IF {row.IS_OPENED} -->opened<!-- ELSEIF {row.IS_OPENABLE} -->closed<!-- ELSE -->leaf<!-- ENDIF -->">
		<!-- IF {row.IS_OPENED} -->
		<a id="url_tree_{row.ID}" title="<!-- IF {row.NAME_TRS} -->%lang({row.NAME})<!-- ELSE -->{row.NAME}<!-- ENDIF -->" href="%url(SYS_U_MODE, 'groups.close', SYS_U_ITEM, {row.ID})"><img id="img_tree_{row.ID}" class="actionable catchable" src="<!-- IF {row.CAN_MANAGE} -->%img('cmd_group_close')<!-- ELSE -->%img('cmd_group_light_close')<!-- ENDIF -->" alt="" /></a>
		<a id="txt_tree_{row.ID}" class="linkable" title="<!-- IF {row.NAME_TRS} -->%lang({row.NAME})<!-- ELSE -->{row.NAME}<!-- ENDIF -->" href="%url(SYS_U_MODE, 'groups.content', SYS_U_ITEM, {row.ID})"><!-- IF {row.NAME_TRS} -->%lang({row.NAME})<!-- ELSE -->{row.NAME}<!-- ENDIF --></a>
		<!-- ELSEIF {row.IS_OPENABLE} -->
		<a id="url_tree_{row.ID}" title="<!-- IF {row.NAME_TRS} -->%lang({row.NAME})<!-- ELSE -->{row.NAME}<!-- ENDIF -->" href="%url(SYS_U_MODE, 'groups.open', SYS_U_ITEM, {row.ID})"><img id="img_tree_{row.ID}" class="actionable catchable" src="<!-- IF {row.CAN_MANAGE} -->%img('cmd_group_open')<!-- ELSE -->%img('cmd_group_light_open')<!-- ENDIF -->" alt="" /></a>
		<a id="txt_tree_{row.ID}" class="linkable" title="<!-- IF {row.NAME_TRS} -->%lang({row.NAME})<!-- ELSE -->{row.NAME}<!-- ENDIF -->" href="%url(SYS_U_MODE, 'groups.content', SYS_U_ITEM, {row.ID})"><!-- IF {row.NAME_TRS} -->%lang({row.NAME})<!-- ELSE -->{row.NAME}<!-- ENDIF --></a>
		<!-- ELSE -->
		<a id="url_tree_{row.ID}" title="<!-- IF {row.NAME_TRS} -->%lang({row.NAME})<!-- ELSE -->{row.NAME}<!-- ENDIF -->"><img id="img_tree_{row.ID}" class="actionable catchable" src="<!-- IF {row.CAN_MANAGE} -->%img('cmd_group_empty')<!-- ELSE -->%img('cmd_group_light')<!-- ENDIF -->" alt="" /></a>
		<a id="txt_tree_{row.ID}" class="linkable" title="<!-- IF {row.NAME_TRS} -->%lang({row.NAME})<!-- ELSE -->{row.NAME}<!-- ENDIF -->" href="%url(SYS_U_MODE, 'groups.content', SYS_U_ITEM, {row.ID})"><!-- IF {row.NAME_TRS} -->%lang({row.NAME})<!-- ELSE -->{row.NAME}<!-- ENDIF --></a>
		<!-- ENDIF -->
	</div>
	<!-- BEGIN row.close --></li></ul><!-- END row.close -->
	<!-- IF {row.IS_OPENED} --><ul id="childs_tree_{row.ID}" class="tree"><!-- ELSEIF {FULL_TREE} || ({row__IDX} < {row__COUNT} - 1) --></li><!-- ENDIF -->

<!-- END row -->

<!-- IF {FULL_TREE} && !{AJAX} && {row} --></ul><!-- ENDIF -->