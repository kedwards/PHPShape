		</div>
	</div>
	<div id="gimeheight">&nbsp;</div>
</div>
<div id="footer"><div class="copy">&copy;&nbsp;<a href="https://www.kncedwards.com/phpshape" title="phpshape" class="copy">phpShape</a>&nbsp;&bull;&nbsp;2007&nbsp;</div></div>

<!-- IF {onload} || ({NEW_SESSION} && !{NEW_SESSION_TSHIFT_DISABLE})--><script type="text/javascript">
/*<![CDATA[*/
window.onload = function ()
{
	<!-- IF {onload} -->
	<!-- PHP -->
	$sav_debug = $this->debug;
	$this->debug = false;
	<!-- ENDPHP -->
	<!-- BEGIN onload -->
	<!-- INCLUDE {onload.FILE} -->
	<!-- END onload -->
	<!-- PHP -->
	$this->debug = $sav_debug;
	unset($sav_debug);
	<!-- ENDPHP -->
	<!-- ENDIF -->

	<!-- IF {NEW_SESSION} && !{NEW_SESSION_TSHIFT_DISABLE} -->
	/* control user timezone */
	checktimeshift({NEW_SESSION_TSHIFT}, "%lang('timeshift_differ')", '%url(SYS_U_MODE, 'srv.time.x')', '%url()');
	<!-- ENDIF -->
}
/*]]>*/
</script><!-- ENDIF -->
%rstats('page.rstats')</body>
</html>