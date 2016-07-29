<!-- BEGIN warnings --><error><!-- BEGIN warnings.warning --><!-- IF {warnings.warning__IDX} --><others><!-- ENDIF -->
	<errmsg><!-- IF {warnings.warning.TRANSLATE} -->%lang({warnings.warning.MSG})<!-- ELSE -->{warnings.warning.MSG}<!-- ENDIF --></errmsg>
	<!-- IF {warnings.warning.LINE} --><errline>{warnings.warning.LINE}</errline><!-- ENDIF -->
	<!-- IF {warnings.warning.FILE} --><errfile>{warnings.warning.FILE}</errfile><!-- ENDIF -->
<!-- IF {warnings.warning__IDX} && ({warnings.warning__IDX} + 1 >= {warnings.warning__COUNT}) --></others><!-- ENDIF --><!-- END warnings.warning --></error><!-- END warnings -->