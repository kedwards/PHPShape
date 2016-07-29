<?php
//
//	file: sys/pagination.class.php
//	author: Kevin Edwards - https://www.kncedwards.com/phpshape
//	begin: 08/02/2008
//	version: 0.0.1 - 08/02/2008
//	license: http://opensource.org/licenses/gpl-3.0.html GNU General Public License version 3 (GPLv3)
//

if ( !defined('SYS') )
{
	die('Not allowed');
}

class sys_pagination extends sys_stdclass
{
	var $scope_mini;
	var $scope_maxi;
	var $scope_percent;

	function __construct()
	{
		parent::__construct();
		$this->scope_mini = false;
		$this->scope_maxi = false;
		$this->scope_percent = false;
	}

	function __destruct()
	{
		unset($this->scope_percent);
		unset($this->scope_maxi);
		unset($this->scope_mini);
		parent::__destruct();
	}

	function set($api_name)
	{
		parent::set($api_name);

		// - scope_min & scope_max are the limits for the number of pages displayed
		$this->scope_mini = 5; // minimal number of pages displayed: 2 on sides + the current one
		$this->scope_maxi = 11; // maximal number of pages displayed: 5 on sides + the current one

		// - the percentage is applied to the total number of items to get the scope
		$this->scope_percent = 10; // 10 % of total pages displayed: if total pages = 100, we will show 10 pages
	}

	function display($total, $start, $tpl_switch=false, $ppage=0)
	{
		$sys = &$GLOBALS[SYS];
		$tpl = &$sys->tpl;

		// pages
		$ppage = !$ppage && !($ppage = (int) $sys->ini_get('ppage.list')) ? 25 : $ppage;
		$page_total = intval(($total - 1) / $ppage) + 1;
		if ( $page_total <= 1 )
		{
			return false;
		}
		$page_current = floor($start / $ppage);

		// head
		$tpl->add($tpl_switch, array(
			'PAGE_CURRENT' => $page_current + 1,
			'PAGE_TOTAL' => $page_total,
			'PAGE_PPAGE' => $ppage,
			'PAGE_PREV' => $page_current > 0? ($page_current - 1) * $ppage : 0,
			'PAGE_NEXT' => $page_current < $page_total - 1 ? ($page_current + 1) * $ppage : 0,
			'PAGE_DISPLAYED' => $page_current * $ppage,
		));

		// dump the forks
		if ( $page_total > 1 )
		{
			$tpl_block = ($tpl_switch ? $tpl_switch . '.' : '') . 'block';
			$forks = $this->get_forks($total, $ppage, $start);
			$count_forks = count($forks);
			for ( $i = 0; $i < $count_forks; $i++ )
			{
				$tpl->add($tpl_block);
				for ( $j = $forks[$i][0]; $j <= $forks[$i][1]; $j++ )
				{
					$tpl->add($tpl_block . '.page', array(
						'NUMBER' => $j + 1,
						'START' => $j * $ppage,
					));
				}
			}
		}
		return true;
	}

	function get_forks($total, $ppage, $start)
	{
		// get the number of pages
		$page_total = ceil($total / $ppage);
		if ( $page_total == 1 )
		{
			return false;
		}

		// center on the current page : $scope is half the number of page around the current page ($middle)
		$scope = ceil((min(max(intval($total * $this->scope_percent / 100), $this->scope_maxi), $this->scope_mini) - 1) / 2);
		$middle = floor($start / $ppage);

		// get forks limits
		$left_end = min($scope, $page_total - 1);
		$middle_start = max($middle - $scope, $scope, 0);
		$middle_end = min($middle + $scope, $page_total - $scope);
		$right_start = max($page_total - $scope - 1, 0);

		// middle get over edges
		if ( !($is_left = $middle_start > ($left_end + $scope)) )
		{
			$middle_start = 0;
		}
		if ( !($is_right = ($middle_end + $scope) < $right_start) )
		{
			$middle_end = $page_total - 1;
		}

		// store forks
		$forks = array();
		if ( $is_left )
		{
			$forks[] = array(0, $left_end);
		}
		$forks[] = array($middle_start, $middle_end);
		if ( $is_right )
		{
			$forks[] = array($right_start, $page_total - 1);
		}
		return $forks;
	}
}

?>