<!-- INCLUDE page.header -->
<!-- IF {warnings} --><!-- INCLUDE page.warnings --><!-- ENDIF -->
<form id="post" method="post" action="%url()"><fieldset><br />
	<p class="errormsg">%lang({MESSAGE})</p>
	<!-- IF {links} --><p class="errorback">
		<!-- BEGIN links --><a class="errorback" href="{links.U_BACK}" title="%lang({links.L_BACK})">%lang({links.L_BACK})</a><br /><!-- END links -->
	</p><br /><!-- ENDIF -->
</fieldset></form>
<!-- INCLUDE page.footer -->