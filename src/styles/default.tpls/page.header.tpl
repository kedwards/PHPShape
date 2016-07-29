<!-- PHP -->
$this->add('jscripts', array('VALUE' => $sys->ini_get('func.js', 'resource')));
$this->add('jscripts', array('VALUE' => $sys->ini_get('admin.menu.js', 'resource')));

// actor realname
{ACTOR_REALNAME_FMT} = {ACTOR_ID} ? call_user_func($f['realname'], {ACTOR_REALNAME}) : '';
<!-- ENDPHP --><!-- IF {transitional} --><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><!-- ELSE --><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"><!-- ENDIF -->

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="X-UA-Compatible" content="IE=contours" />
	<meta http-equiv="Content-Type" content="text/html; charset=%lang('ENCODING')" />
	<meta http-equiv="Content-Style-Type" content="text/css" />

	<!-- BEGIN meta -->
	<meta http-equiv="{meta.HTTP_EQUIV}" content="{meta.CONTENT}" />
	<!-- END meta -->

	<title>%lang('application')<!-- IF {SCRIPT_TITLE} --> &bull; %lang({SCRIPT_TITLE})<!-- ENDIF --></title>

	<!-- BEGIN css -->
	<link rel="stylesheet" href="{css.VALUE}" type="text/css" />
	<!-- END css -->
	<!--[if IE]><!-- BEGIN css_ie -->
	<link rel="stylesheet" href="{css_ie.VALUE}" type="text/css" />
	<!-- END css_ie --><![endif]-->
	<!--[if lte IE 8]><!-- BEGIN css_ie8 -->
	<link rel="stylesheet" href="{css_ie8.VALUE}" type="text/css" />
	<!-- END css_ie8 --><![endif]-->
	<!--[if lte IE 6]><!-- BEGIN css_ie6 -->
	<link rel="stylesheet" href="{css_ie6.VALUE}" type="text/css" />
	<!-- END css_ie6 --><![endif]-->
	<!--[if !IE]>--><!-- BEGIN css_other -->
	<link rel="stylesheet" href="{css_other.VALUE}" type="text/css" />
	<!-- END css_other --><!--<![endif]-->

	<!-- BEGIN jscripts -->
	<script type="text/javascript" src="{jscripts.VALUE}"></script>
	<!-- END jscripts -->

</head><!-- PHP flush(); -->
<body><div id="background">
	<div id="header">
		<div id="head">
			<div id="logmenu">&nbsp;<!-- IF {ACTOR_ID} -->
				%lang('menu_welcome', {ACTOR_REALNAME_FMT})&nbsp;
				<a href="%url(SYS_U_MODE, 'logout')" title="%lang('menu_logout')"><img src="%img('cmd_actor_logout')" alt="%lang('menu_logout')" /></a>&nbsp;
			<!-- ENDIF --></div><div class="clearboth">&nbsp;</div>

			<!-- IF {menu_main} --><div id="menu_box"><ul id="menu"><!-- BEGIN menu_main -->
				<li class="menu<!-- IF {menu_main.selected} --> selected<!-- ENDIF --> rounded-top">%rounded_begin('top')<a class="menu" href="{menu_main.HREF}" title="%lang({menu_main.TITLE})">%lang({menu_main.NAME})</a>%rounded_end('top')</li>
			<!-- END menu_main --></ul><div class="clearboth">&nbsp;</div></div><!-- ENDIF -->

			<!-- IF {menu_sub} --><div id="submenu_box"><ul id="submenu"><!-- BEGIN menu_sub -->
				<li class="menu<!-- IF {menu_sub.selected} --> selected<!-- ENDIF --> rounded-bottom">%rounded_begin('bottom')<a class="menu" href="{menu_sub.HREF}" title="%lang({menu_sub.TITLE})">%lang({menu_sub.NAME})</a>%rounded_end('bottom')</li>
			<!-- END menu_sub --><li class="clearboth">&nbsp;</li></ul><div class="clearboth">&nbsp;</div></div><!-- ENDIF -->
		</div>
	</div>
	<div id="content">
		<!-- IF {menu_local} --><div id="subsubmenu_box"><ul id="subsubmenu"><!-- BEGIN menu_local -->
			<li class="optmenu<!-- IF {menu_local.selected} --> selected<!-- ENDIF --> rounded-top">%rounded_begin('top')<a class="optmenu" href="{menu_local.HREF}" onclick="return adminmenu(this.href);" title="%lang({menu_local.TITLE})">%lang({menu_local.NAME})</a>%rounded_end('top')</li>
		<!-- END menu_local --></ul><div class="clearboth">&nbsp;</div></div><!-- ENDIF -->

		<div id="innercontent">