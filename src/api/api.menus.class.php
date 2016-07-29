<?php
//
//	file: api/api.menus.class.php
//	author: Kevin Edwards - https://www.kncedwards.com/phpshape
//	begin: 31/05/2010
//	version: 0.0.1 - 31/05/2010
//	license: http://opensource.org/licenses/gpl-3.0.html GNU General Public License version 3 (GPLv3)
//

if ( !defined('SYS') )
{
	die('Not allowed');
}

class api_menus extends sys_stdclass
{
	var $data;

	function __construct()
	{
		parent::__construct();
		$this->data = false;
	}

	function __destruct()
	{
		unset($this->data);
		parent::__destruct();
	}

	function process()
	{
		if ( $this->init() )
		{
			return $this->display();
		}
		return false;
	}

	function init()
	{
		$api = &$GLOBALS[$this->api_name];

		// get all menus through the hook processor
		$api->hooks->process('menus');
		$this->data = $api->hooks->get_data('menus');
		$api->hooks->unset_data('menus');

		return $this->data ? true : false;
	}

	function display()
	{
		$sys = &$GLOBALS[SYS];
		$api = &$GLOBALS[$this->api_name];
		$tpl = &$sys->tpl;

		// home menu is first
		if ( isset($this->data['menu_home']) )
		{
			$menu_home = $this->data['menu_home'];
			unset($this->data['menu_home']);
			$this->data = array('menu_home' => $menu_home) + $this->data;
			unset($menu_home);
		}

		// admin menu occurs after all other
		if ( isset($this->data['menu_admin']) )
		{
			$menu_admin = $this->data['menu_admin'];
			unset($this->data['menu_admin']);
			$this->data = $this->data + array('menu_admin' => $menu_admin);
			unset($menu_admin);
		}

		// set href & selected status for parents
		$this->_get_href($this->data);
		$this->_get_selected($this->data);

		// and finaly send all to display
		$count_data = count($this->data);
		foreach ( $this->data as $name => $def )
		{
			if ( $count_data > 1 )
			{
				$tpl->add('menu_main', array(
					'NAME' => $name,
					'TITLE' => $def['title'],
					'HREF' => $api->url($def['href']),
				));
			}
			if ( $def['selected'] )
			{
				if ( $count_data > 1 )
				{
					$tpl->add('menu_main.selected');
				}
				if ( isset($def['subs']) && $def['subs'] )
				{
					$count_subs = count($def['subs']);
					foreach ( $def['subs'] as $name => $def )
					{
						if ( $count_subs > 1 )
						{
							$tpl->add('menu_sub', array(
								'NAME' => $name,
								'TITLE' => $def['title'],
								'HREF' => $def['href'] !== false ? $api->url($def['href']) : $api->url(),
							));
						}
						if ( $def['selected'] )
						{
							if ( $count_subs > 1 )
							{
								$tpl->add('menu_sub.selected');
							}
							if ( isset($def['subs']) && $def['subs'] )
							{
								foreach ( $def['subs'] as $name => $def )
								{
									$tpl->add('menu_local', array(
										'NAME' => $name,
										'TITLE' => $def['title'],
										'HREF' => $def['href'] !== false ? $api->url($def['href']) : $api->url(),
									));
									if ( $def['selected'] )
									{
										$tpl->add('menu_local.selected');
									}
								}
							}
						}
					}
				}
			}
		}
		$this->data = false;

		return true;
	}


	function _get_href(&$menus, $name=false)
	{
		if ( $name )
		{
			if ( $menus[$name]['href'] !== false )
			{
				return $menus[$name]['href'];
			}
			if ( isset($menus[$name]['subs']) && $menus[$name]['subs'] )
			{
				foreach ( $menus[$name]['subs'] as $sub => $dummy )
				{
					$href = $this->_get_href($menus[$name]['subs'], $sub);
					if ( !$done && ($done = true) )
					{
						$menus[$name]['href'] = $href;
					}
				}
				return $menus[$name]['href'];
			}
			return false;
		}
		if ( $menus )
		{
			foreach ( $menus as $name => $dummy )
			{
				$this->_get_href($menus, $name);
			}
		}
	}

	function _get_selected(&$menus, $name=false)
	{
		if ( $name )
		{
			if ( $menus[$name]['selected'] )
			{
				return $menus[$name]['selected'];
			}
			if ( isset($menus[$name]['subs']) && $menus[$name]['subs'] )
			{
				foreach ( $menus[$name]['subs'] as $sub => $dummy )
				{
					if ( ($menus[$name]['selected'] = $this->_get_selected($menus[$name]['subs'], $sub)) )
					{
						return $menus[$name]['selected'];
					}
				}
			}
			return false;
		}
		if ( $menus )
		{
			foreach ( $menus as $name => $dummy )
			{
				if ( ($menus[$name]['selected'] = $this->_get_selected($menus, $name)) )
				{
					return $menus[$name]['selected'];
				}
			}
			$menus['menu_home']['selected'] = true;
		}
		return false;
	}

	// static: api menus
	function hook($api_name, $action)
	{
		$sys = &$GLOBALS[SYS];
		$api = &$GLOBALS[$api_name];
		$actor = &$api->user;

		if ( $action !== 'menus' )
		{
			return false;
		}
		$menus = $api->hooks->get_data('menus');
		if ( $menus === false )
		{
			$menus = array();
		}

		// some status
		$is_group_manager = isset($sys->tpl->data['IS_GROUP_MANAGER']) && $sys->tpl->data['IS_GROUP_MANAGER'];
		$actor_id = isset($sys->tpl->data['ACTOR_ID']) ? $sys->tpl->data['ACTOR_ID'] : false;

		if ( !isset($menus['menu_home']) )
		{
			$menus['menu_home'] = array(
				'title' => 'menu_home',
				'selected' => false,
				'href' => array(),
				'subs' => array(),
			);
		}
		if ( !$actor_id )
		{
			$menus['menu_login'] = array(
				'title' => 'menu_login',
				'selected' => $api->mode == 'login',
				'href' => array(SYS_U_MODE => 'login'),
			);
			$menus['menu_register'] = array(
				'title' => 'menu_register',
				'selected' => $api->mode == 'register',
				'href' => array(SYS_U_MODE => 'register'),
			);
		}
		else
		{
			$menus['menu_actor'] = array(
				'title' => 'menu_actor',
				'selected' => $api->mode == 'profile',
				'href' => array(SYS_U_MODE => 'profile'),
			);

			// main level: admin
			if ( $is_group_manager )
			{
				if ( !isset($menus['menu_admin']) )
				{
					$menus['menu_admin'] = array(
						'title' => 'menu_admin',
						'selected' => false,
						'href' => false,
						'subs' => array(),
					);
				}
				$menus['menu_admin']['subs'] += array('menu_groups' => array(
					'title' => 'menu_groups',
					'selected' => $api->mode_base == 'groups',
					'href' => false,
					'subs' => array(
						'menu_groups_def' => array(
							'title' => 'menu_groups_def',
							'selected' => ($api->mode_base == 'groups') && !in_array($api->mode_sub, array('managers', 'membership')),
							'href' => array(SYS_U_MODE => 'groups'),
						),
						'menu_groups_managers' => array(
							'title' => 'menu_groups_managers',
							'selected' => ($api->mode_base . '.' . $api->mode_sub) == 'groups.managers',
							'href' => array(SYS_U_MODE => 'groups.managers'),
						),
						'menu_groups_membership' => array(
							'title' => 'menu_groups_membership',
							'selected' => ($api->mode_base . '.' . $api->mode_sub) == 'groups.membership',
							'href' => array(SYS_U_MODE => 'groups.membership'),
						),
					),
				));
			}
		}
		$api->hooks->set_data('menus', $menus);

		return true;
	}
}

?>