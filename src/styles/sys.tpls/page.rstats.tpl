<div id="rstats_full"><div id="rstats_global" class="rstats">
	<!-- IF {SCRIPT_TIME} -->[%lang('sys_rstats_elapsed', {SCRIPT_TIME})]<!-- ENDIF -->
	<!-- IF {DB_COUNT} -->[%lang('sys_rstats_db', {DB_COUNT}, {DB_TIME})]<!-- ENDIF -->
	<!-- IF {DB_CACHE_COUNT} -->[%lang('sys_rstats_db_cache', {DB_CACHE_COUNT}, {DB_CACHE_TIME})]<!-- ENDIF -->
</div>
<!-- IF {script_debug} --><div id="rstats_script_details" class="rstats"><strong>%lang('sys_rstats_script_debug')</strong>
	<ul>
		<!-- BEGIN script_debug --><li><!-- IF {script_debug.TITLE} -->%lang('sys_rstats_title_location', {script_debug.TITLE}, {script_debug.FILE}, {script_debug.LINE})<!-- ELSE -->%lang('sys_rstats_location', {script_debug.FILE}, {script_debug.LINE})<!-- ENDIF -->&nbsp;&bull;&nbsp;%lang('sys_rstats_script_at_time', {script_debug.TICK})<!-- IF {script_debug__IDX} && ({script_debug.ELAPSED} != {script_debug.TICK}) -->&nbsp;&bull;&nbsp;%lang('sys_rstats_script_elapsed', {script_debug.ELAPSED})<!-- ENDIF --><br />
		<!-- IF {script_debug.calls} --><ul style="list-style-type: circle;"><!-- BEGIN script_debug.calls --><li>%lang('sys_rstats_function_location', {script_debug.calls.FUNCTION}, {script_debug.calls.FILE}, {script_debug.calls.LINE})</li><!-- END script_debug.calls --></ul><!-- ENDIF -->
		<br /></li>
		<!-- END script_debug -->
	</ul>
</div><!-- ENDIF -->
<!-- IF {db_debug} --><div id="rstats_db_details" class="rstats"><strong>%lang('sys_rstats_db_debug')</strong>
	<ul><!-- BEGIN db_debug --><li>%lang('sys_rstats_location', {db_debug.FILE}, {db_debug.LINE})&nbsp;&bull;&nbsp;<!-- IF {db_debug.FROM_CACHE} -->%lang('sys_rstats_cache_elapsed', {db_debug.ELAPSED})<!-- IF {db_debug.DB_ELAPSED} -->&nbsp;&bull;&nbsp;%lang('sys_rstats_db_elapsed', {db_debug.DB_ELAPSED})<!-- ENDIF --><!-- ELSE -->%lang('sys_rstats_elapsed', {db_debug.ELAPSED})<!-- ENDIF --><br />
		<textarea cols="80" rows="3" style="width: 96%; font-size: 1.2em;" readonly="readonly">{db_debug.SQL}</textarea><br />
		<!-- IF {db_debug.explain} --><table>
			<thead><tr><!-- BEGIN db_debug.explain --><th>&nbsp;{db_debug.explain.TITLE}&nbsp;</th><!-- END db_debug.explain --></tr></thead>
			<tbody><!-- BEGIN db_debug.row --><tr><!-- BEGIN db_debug.row.cell --><td>&nbsp;{db_debug.row.cell.VALUE}&nbsp;</td><!-- END db_debug.row.cell --></tr><!-- END db_debug.row --></tbody>
		</table><!-- ENDIF -->
	<br /></li><!-- END db_debug --></ul>
</div><!-- ENDIF --></div>