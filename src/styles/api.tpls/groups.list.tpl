<!-- PHP -->
$this->add('jscripts', array('VALUE' => $sys->ini_get('mousehandler.js', 'resource')));
$this->add('jscripts', array('VALUE' => $sys->ini_get('tree.list.js', 'resource')));
$this->add('onload', array('FILE' => 'groups.list.onload'));
<!-- ENDPHP --><!-- INCLUDE page.header -->
<!-- IF {warnings} --><!-- INCLUDE page.warnings --><!-- ENDIF -->
<form id="post" method="post" action="%url()">
	<fieldset class="tree">
		<div id="tree"><!-- INCLUDE groups.list.tree --><br /></div>
	</fieldset>
	<fieldset class="treecontent">
		<div id="treecontent"><!-- INCLUDE groups.list.content --><br /></div>
		<!-- IF {hidden_field} --><div id="hidden_fields"><!-- INCLUDE form.hidden_fields --></div><!-- ENDIF -->
	</fieldset>
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
	resizeBoxes.register('treecontent');
	window.onresize = function (event) {
		resizeBoxes.resize();
		return true;
	}

	var dd = new DragDropTree();
	dd.set(
		'dd',
		"%url_noamp(SYS_U_MODE, 'groups.$1.x', SYS_U_ITEM, '$2')",
		'{CONTENT_ID}',
		'floatbox',
		{
			iOpen: "%img('cmd_group_open')",
			iOpenOver: "%img('cmd_group_open_over')",
			iClose: "%img('cmd_group_close')",
			iCloseOver: "%img('cmd_group_close_over')",
			iEmpty: "%img('cmd_group_empty')",
			iEmptyOver: "%img('cmd_group_empty_over')",
			iContent: "%img('cmd_group_content')",
			iContentOver: "%img('cmd_group_content_over')"
		}
	);
/*]]>*/
</script>
<!-- INCLUDE page.footer -->