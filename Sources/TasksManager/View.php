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

	private function actions()
	{
		$this->_actions = [
			'main' => 'View::all',
			'config' => 'Settings::config',
			'permissions' => 'Settings::permissions',
			'projects' => 'Projects::main',
			'categories' => 'Categories::main',
			'status' => 'Status::main',
			'types' => 'Types::main',
		];

		// Get the current action
		$this->_area = isset($_GET['area'], $this->_actions[$_GET['area']]) ? $_GET['area'] : 'main';
	}

	private function areas()
	{
		global $txt, $sourcedir;

		$this->_tasks_areas = [
			'projects' => [
				'title' => $txt['TasksManager_projects'],
				'description' => 'Testing',
				'amt' => 5,
				'areas' => [
					'projects' => [
						'label' => $txt['TasksManager_projects'],
						'amt' => 0,
						'icon' => 'reports',
						'subsections' => [
							'index' => [$txt['TasksManager_projects_index']],
							'add' => [$txt['TasksManager_projects_add'], 'tasksmanager_can_edit'],
						],
					],
					'tasks' => [
						'label' => $txt['TasksManager_tasks'],
						'amt' => 0,
						'subsections' => [
							'all' => [
								$txt['TasksManager_add_task']
							],
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

	public function main()
	{
		global $context, $scripturl, $txt;

		// Page title
		$context['page_title'] = $txt['TasksManager_button'];

		// Linktree
		$context['linktree'][] = [
			'url' => $scripturl . '?action=tasksmanager',
			'name' => $txt['TasksManager_button']
		];

		// Add layers... with the copyright and other stuff
		$context['template_layers'][] = 'main';

		// Copyright
		$context['tasksmanager']['copyright'] = $this->copyright();

		// Invoke the function
		call_helper(__NAMESPACE__ . '\\' . $this->_actions[$this->_area] . '#');
	}

	public static function page_setup($action, $template = null, $title = null, $link = null, $icon = 'help')
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
		if (!empty($template))
			$context['sub_template'] = $template;

		// Menu Deets
		$context['tasks_menu_name'] = 'menu_data_' . $context['max_menu_id'];
		$context[$context['tasks_menu_name']]['tab_data'] = [
			'title' => $txt['TasksManager_' . (!empty($title) ? $title : $action)],
			'icon_class' => 'main_icons ' . $icon
		];
		if (isset($txt['TasksManager_' . (!empty($title) ? $title : $action) . '_desc']))
			$context[$context['tasks_menu_name']]['tab_data']['description'] = $txt['TasksManager_' . (!empty($title) ? $title : $action) . '_desc'];
	}

	public function all()
	{
		global $context, $txt, $scripturl;

		// Page setup
		$this->page_setup('all', 'list', 'list');
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