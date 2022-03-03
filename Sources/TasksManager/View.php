<?php

/**
 * @package Tasks Manager
 * @version 1.0
 * @author Diego Andrés <diegoandres_cortes@outlook.com>
 * @copyright Copyright (c) 2022, SMF Tricks
 * @license MIT
 */

namespace TasksManager;

if (!defined('SMF'))
	die('No direct access...');

class View
{
	/**
	 * @var array The actions of the page 
	 */
	private $_actions = [];

	/**
	 * @var array The menu areas for the page
	 */
	private $_tasks_areas = [];

	/**
	 * @var string The current action
	 */
	private $_area;

	function __construct()
	{
		// Language
		loadLanguage('TasksManager/');

		// Template
		loadTemplate('TasksManager');

		// Permission
		isAllowedTo(['tasksmanager_can_view', 'tasksmanager_can_edit']);

		// Actions
		$this->actions();

		// Menu
		$this->areas();

		// Hide Search
		addInlineCss('.admin_search { display: none; }');
	}

	/**
	 * View::actions()
	 * 
	 * Set the main areas/actions of the page
	 * @return void
	 */
	private function actions()
	{
		$this->_actions = [
			'config' => 'Settings::config',
			'permissions' => 'Settings::permissions',
			'projects' => 'Projects::main',
			'tasks' => 'Tasks::main',
			'booking' => 'Book::main',
			'categories' => 'Categories::main',
			'status' => 'Status::main',
			'types' => 'Types::main',
		];

		// Get the current action
		$this->_area = isset($_GET['area'], $this->_actions[$_GET['area']]) ? $_GET['area'] : 'projects';
	}

	/**
	 * View::areas()
	 * 
	 *  Populates the menu area array and builds the menu
	 * @return void
	 */
	private function areas()
	{
		global $txt, $sourcedir;

		$this->_tasks_areas = [
			'projects' => [
				'title' => $txt['TasksManager_projects'],
				'description' => 'Testing',
				'areas' => [
					'projects' => [
						'label' => $txt['TasksManager_projects'],
						'icon' => 'reports',
						'subsections' => [
							'index' => [$txt['TasksManager_projects_index']],
							'add' => [$txt['TasksManager_projects_add'], 'tasksmanager_can_edit'],
						],
					],
					'tasks' => [
						'label' => $txt['TasksManager_tasks'],
						'icon' => 'posts',
						'subsections' => [
							'index' => [$txt['TasksManager_tasks_index']],
							'add' => [$txt['TasksManager_tasks_add'], 'tasksmanager_can_edit'],
						],
					],
					'booking' => [
						'label' => $txt['TasksManager_booking'],
						'icon' => 'scheduled',
						'permission' => 'tasksmanager_can_edit',
						'subsections' => [
							'log' => [$txt['TasksManager_booking_log']],
							'booktime' => [$txt['TasksManager_booking_booktime']],
						],
					],
				],
			],
			'manage' => [
				'title' => $txt['TasksManager_manage'],
				'permission' => ['tasksmanager_can_edit'],
				'areas' => [
					'categories' => [
						'label' => $txt['TasksManager_categories'],
						'icon' => 'boards',
						'subsections' => [
							'projects' => [$txt['TasksManager_projects_cat']],
							'tasks' => [$txt['TasksManager_tasks_cat']],
							'add' => [$txt['TasksManager_add_category']],
						],
					],
					'status' => [
						'label' => $txt['TasksManager_statuses'],
						'icon' => 'warning',
						'subsections' => [
							'index' => [$txt['TasksManager_status_list']],
							'add' => [$txt['TasksManager_status_add']],
						],
					],
					'types' => [
						'label' => $txt['TasksManager_types'],
						'icon' => 'logs',
						'subsections' => [
							'index' => [$txt['TasksManager_types_index']],
							'add' => [$txt['TasksManager_add_type']],
						],
					],
				],
			],
			'settings' => [
				'title' => $txt['settings'],
				'permission' => ['tasksmanager_can_edit'],
				'areas' => [
					'config' => [
						'icon' => 'features',
						'label' => $txt['TasksManager_config'],
					],
					'permissions' => [
						'label' => $txt['TasksManager_permissions'],
						'permission' => ['manage_permissions'],
					],
				],
			],
		];

		require_once($sourcedir . '/Subs-Menu.php');

		// Set a few options for the menu.
		$menuOptions = array(
			'current_area' => $this->_area,
			'disable_url_session_check' => true,
		);

		$tm_include_data = createMenu($this->_tasks_areas, $menuOptions);
		unset($this->_tasks_areas);

		// No menu means no access.
		if (!$tm_include_data && (validateSession()))
			fatal_lang_error('no_access', false);

		// Set the selected item.
		$context['menu_item_selected'] = $tm_include_data['current_area'];
	}

	/**
	 * View::main()
	 * 
	 * Provides the basic information for the action
	 * It also loads the correct function based on the area and subsections
	 * @return void
	 */
	public function main()
	{
		global $context, $scripturl, $txt, $modSettings;

		// Page title
		$context['page_title'] = (!empty($modSettings['tppm_title']) ? $modSettings['tppm_title'] : $txt['TasksManager_button']);

		// Linktree
		$context['linktree'][] = [
			'url' => $scripturl . '?action=tasksmanager',
			'name' =>(!empty($modSettings['tppm_title']) ? $modSettings['tppm_title'] : $txt['TasksManager_button']),
		];

		// Menu Deets
		$context['tasks_menu_name'] = 'menu_data_' . $context['max_menu_id'];
		$context[$context['tasks_menu_name']]['tab_data'] = [
			'title' => (!empty($modSettings['tppm_title']) ? $modSettings['tppm_title'] : $txt['TasksManager_button']),
			'icon_class' => 'main_icons reports',
		];

		// Add layers... with the copyright and other stuff
		$context['template_layers'][] = 'main';

		// Copyright
		$context['tasksmanager']['copyright'] = $this->copyright();

		// Invoke the function
		call_helper(__NAMESPACE__ . '\\' . $this->_actions[$this->_area] . '#');
	}

	/**
	 * View::page_setup()
	 * 
	 * Returns the page setup which includes the page title,
	 * the linktree and the menu data with description and icon.
	 * @param string $action The page action
	 * @param string $sub_template The sub_template to use for the page
	 * @param string $title The page title
	 * @param string $link The page custom link in case it's not the same as the action
	 * @param string $icon The page icon
	 * @return void
	 */
	public static function page_setup($action, $sub_template = null, $title = null, $link = null, $icon = 'help')
	{
		global $txt, $context, $scripturl;

		// Page title
		$context['page_title'] = $txt['TasksManager_button'] . ' - ' . $txt['TasksManager_' . (!empty($title) ? $title : $action)];
		// Linktree
		$context['linktree'][] = [
			'url' => $scripturl . (!empty($link) ? $link : '?action=tasksmanager;area=' . $action),
			'name' => $txt['TasksManager_' . (!empty($title) ? $title : $action)]
		];
		// Template
		if (!empty($sub_template))
			$context['sub_template'] = $sub_template;

		// Menu Deets
		$context['tasks_menu_name'] = 'menu_data_' . $context['max_menu_id'];
		$context[$context['tasks_menu_name']]['tab_data'] = [
			'title' => $txt['TasksManager_' . (!empty($title) ? $title : $action)],
			'icon_class' => 'main_icons ' . $icon
		];
		if (isset($txt['TasksManager_' . (!empty($title) ? $title : $action) . '_desc']))
			$context[$context['tasks_menu_name']]['tab_data']['description'] = $txt['TasksManager_' . (!empty($title) ? $title : $action) . '_desc'];
	}

	/**
	 * View::itemSelect()
	 * 
	 * Sorts a list of items for the select
	 * 
	 * @param array $list The list of items
	 * @param string $key The key to sort by
	 * @param string $value The value to sort by
	 * @param string $none_txt The text to use for none
	 * @return array The sorted list of items
	 */
	public static function itemSelect($list, $key, $value, $none_txt)
	{
		// print_r($list);
		$sort = [
			0 => $none_txt
		];
		foreach ($list as $item)
			$sort[$item[$key]] = $item[$value];

		return $sort;
	}

	/**
	 * View::copyright()
	 *
	 * @return string A link for copyright notice
	 */
	private function copyright()
	{
		return ' Powered by <a href="https://smftricks.com" target="_blank" rel="noopener">Tasks Manager</a>';
	}
}