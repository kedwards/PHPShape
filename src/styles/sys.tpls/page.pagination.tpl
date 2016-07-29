<!-- IF {PAGE_TOTAL} > 0 --><div><!-- IF {PAGE_TOTAL} > 1 --><div class="right"><!-- IF {PAGE_CURRENT} > 1 --><a href="{U_PAGE_PREV}" title="%lang('sys_pagination_goto_previous')">%lang('sys_pagination_previous')</a>&nbsp;
<!-- ENDIF -->
<!-- BEGIN block --><!-- BEGIN block.page --><!-- IF {block.page.PAGE} == {PAGE_CURRENT} --><strong><!-- ELSE --><a href="{block.page.U_PAGE}" title="%lang('sys_pagination_goto_page', {block.page.PAGE})"><!-- ENDIF -->{block.page.PAGE}<!-- IF {block.page.PAGE} == {PAGE_CURRENT} --></strong><!-- ELSE --></a><!-- ENDIF --><!-- IF {block.page__IDX} < {block.page__COUNT} - 1 -->,&nbsp;
<!-- ENDIF --><!-- END block.page --><!-- IF {block__IDX} < {block__COUNT} - 2 -->&nbsp;
...&nbsp;
<!-- ENDIF --><!-- END block -->
<!-- IF {PAGE_CURRENT} < {PAGE_TOTAL} -->&nbsp;
<a href="{U_PAGE_NEXT}" title="%lang('sys_pagination_goto_next')">%lang('sys_pagination_next')</a><!-- ENDIF -->
</div><!-- ENDIF -->%lang('sys_pagination_current', {PAGE_CURRENT}, {PAGE_TOTAL})</div><!-- ENDIF -->