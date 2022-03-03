<?php

/**
 * @package Tasks Manager
 * @version 1.1
 * @author Diego Andrés <diegoandres_cortes@outlook.com>
 * @copyright Copyright (c) 2022, SMF Tricks
 * @license MIT
 */

namespace TasksManager;

if (!defined('SMF'))
	die('No direct access...');

class Integration
{
	/**
	 * @var array The mod permissions
	 */
	private $_permissions = [
		'tasksmanager_can_view',
		'tasksmanager_can_edit',
	];

	/**
	 * Integration::initialize()
	 * 
	 * Loads all the hooks and settings for this mod
	 * @return void
	 */
	public static function initialize()
	{
		// Hooks
		add_integration_function('integrate_autoload', __CLASS__ . '::autoload', false);
		add_integration_function('integrate_actions', __CLASS__ . '::actions', false);
		add_integration_function('integrate_menu_buttons', __CLASS__ . '::menu_buttons#', false);
		// Language
		add_integration_function('integrate_admin_areas', __CLASS__ . '::language', false);
		add_integration_function('integrate_helpadmin', __CLASS__ . '::language', false);
		// Permission
		add_integration_function('integrate_load_permissions', __CLASS__ . '::load_permissions#', false);
		add_integration_function('integrate_load_illegal_guest_permissions', __CLASS__ . '::illegal_guest#', false);
		// Mod Button
		add_integration_function('integrate_mod_buttons', __CLASS__ . '::mod_buttons#', false);
		// Topic
		add_integration_function('integrate_display_topic', __CLASS__ . '::display_topic', false);
		// Who
		add_integration_function('whos_online_after', __CLASS__ . '::whos_online_after#', false);
	}

	/**
	 * Integration::autoload()
	 * 
	 * Add the tasks manager to the autoloader
	 * @param array $classMap The autoloader map
	 * @return void
	 */
	public static function autoload(&$classMap)
	{
		$classMap[__NAMESPACE__ . '\\'] = __NAMESPACE__ . '/';
	}

	/**
	 * Integration::menu_buttons()
	 * 
	 * Add the tasks button to the menu
	 * @param array $buttons The forum menu buttons
	 * @return void
	 */
	public function menu_buttons(&$buttons)
	{
		global $scripturl, $txt, $modSettings;

		// Language
		$this->language();

		// Menu Button
		$buttons['tasksmanager' ] = [
			'title' => (!empty($modSettings['tppm_title']) ? $modSettings['tppm_title'] : $txt['TasksManager_button']),
			'href' => $scripturl . '?action=tasksmanager',
			'icon' => 'reports',
			'show' => allowedTo('tasksmanager_can_view'),
		];
	}

	/**
	 * Integration::actions()
	 * 
	 * Add the tasks action
	 * @param array $actions The forum actions
	 * @return void
	 */
	public static function actions(&$actions)
	{
		// The main action
		$actions['tasksmanager'] = [__NAMESPACE__ . '/View.php', __NAMESPACE__  . '\View::main#'];
	}

	/**
	 * Integration::language()
	 * 
	 * Load the language using the admin area hooks.
	 * It's only needed to load the language file in the permissions page.
	 * It also loads the language for the admin help, because it's a popup duh
	 */
	public static function language()
	{
		// Language for the permissions
		loadLanguage('TasksManager/');
	}

	/**
	 * Integration::load_permissions()
	 * 
	 * Load the permissions
	 * @param array $permissionGroups
	 * @param array $permissionList
	 */
	public function load_permissions(&$permissionGroups, &$permissionList)
	{
		$permissionGroups['membergroup'][] = 'tasksmanager';
		foreach ($this->_permissions as $permission)
			$permissionList['membergroup'][$permission] = [false, 'tasksmanager'];
	}

	/**
	 * Integration::illegal_guest()
	 * 
	 * Remove the permissions from guests
	 */
	public function illegal_guest()
	{
		global $context;

		// Guest should not be able to edit or add anything
		$context['non_guest_permissions'][] = 'tasksmanager_can_edit';
	}

	/**
	 * Integration::mod_buttons()
	 * 
	 * Add the button to link tasks
	 * @param array $mod_buttons The mod buttons
	 * @return void
	 */
	public function mod_buttons(&$mod_buttons)
	{
		global $scripturl, $context;
		static $topic_task;

		// Don't do anything if we don't have the permission
		if (!allowedTo('tasksmanager_can_edit'))
			return;	

		// Language
		$this->language();

		// Add a topic to a task
		if (empty($context['topicinfo']['tasks_task_id']))
			$mod_buttons['tasksmanager_add_task'] = ['text' => 'TasksManager_add_topic_task', 'icon' => 'posts', 'url' => $scripturl . '?action=tasksmanager;area=tasks;sa=addtopic;id=' . $context['current_topic'] . ';' . $context['session_var'] . '=' . $context['session_id']];
		else
			$mod_buttons['tasksmanager_remove_task'] = ['text' => 'TasksManager_remove_topic_task', 'icon' => 'delete', 'url' => $scripturl . '?action=tasksmanager;area=tasks;sa=deletetopic;id=' . $context['current_topic'] . ';' . $context['session_var'] . '=' . $context['session_id']];
	}

	/**
	 * Integration::display_topic()
	 * 
	 * Add the tasks to the topic info
	 * 
	 * @param array $topic_selects The topic additional selects
	 * @param array $topic_tables The topic additional tables
	 */
	public static function display_topic(&$topic_selects, &$topic_tables)
	{
		// Column
		$topic_selects[] = 't.tasks_task_id';

		// Table
		$topic_tables[] = 'LEFT JOIN {db_prefix}taskspp_tasks AS tk ON (tk.task_id = t.tasks_task_id)';
	}

	/**
	 * Integration::whos_online_after()
	 * 
	 * Add the tasks to the who allowed list
	 * 
	 * @param mixed $urls a single url (string) or an array of arrays, each inner array being (JSON-encoded request data, id_member)
	 * @param array $data Returns the correct strings for each action
	 * @return void
	 */
	public function whos_online_after(&$urls, &$data)
	{
		global $smcFunc, $txt, $scripturl, $modSettings;

		// Load language
		$this->language();

		// Go through the urls
		foreach ($urls as $key => $url)
		{
			// Get the actual actions
			$actions = $smcFunc['json_decode']($url[0], true);

			// Any actions?
			if (empty($actions))
				continue;

			// We only want tasksmanager actions here
			if (!isset($actions['action']) || $actions['action'] !== 'tasksmanager')
				continue;

			// Can they see the tasks manager?
			if (!allowedTo('tasksmanager_can_view'))
			{
				$data[$key] = $txt['who_hidden'];
				continue;
			}

			// Use some defaults
			$default_url = $scripturl . '?action=' . $actions['action'];
			$default_txt = $txt['TasksManager_who_viewing_tasksmanager'];
			$allowed_area = true;

			// Set a default text
			$data[$key] = sprintf($default_txt, $default_url, (!empty($modSettings['tppm_title']) ? $modSettings['tppm_title'] : $txt['TasksManager_button']));

			// Viewing the index or... something? Do nothing!
			if (!isset($actions['area']))
				continue;

			// Give it the area
			$default_url .= ';area=' . $actions['area'];

			// Now we go for the areas
			switch ($actions['area'])
			{
				// Projects
				case 'projects':
					$default_txt = $txt['TasksManager_who_viewing_projects'];
					break;
				// Tasks
				case 'tasks':
					$default_txt = $txt['TasksManager_who_viewing_tasks'];
					break;
				// Booking
				case 'booking':
					$allowed_area = allowedTo('tasksmanager_can_edit');
					$default_txt = $txt['TasksManager_who_viewing_bookings'];
					break;
				// Categories
				case 'categories':
					$allowed_area = allowedTo('tasksmanager_can_edit');
					$default_txt = $txt['TasksManager_who_viewing_categories'];
					break;
				// Status
				case 'status':
					$allowed_area = allowedTo('tasksmanager_can_edit');
					$default_txt = $txt['TasksManager_who_viewing_status'];
					break;
				// Types
				case 'types':
					$allowed_area = allowedTo('tasksmanager_can_edit');
					$default_txt = $txt['TasksManager_who_viewing_types'];
					break;
				// Config
				case 'config':
					$allowed_area = allowedTo('tasksmanager_can_edit');
					$default_txt = $txt['TasksManager_who_viewing_config'];
					break;
				// Permissions
				case 'permissions':
					$allowed_area = allowedTo('tasksmanager_can_edit');
					$default_txt = $txt['TasksManager_who_viewing_permissions'];
					break;
			}

			// Sub action variations?
			// For all of these you require a specific permission
			if (isset($actions['sa']) && allowedTo('tasksmanager_can_edit'))
			{
				// Change the URL with the subaction
				$default_url .= ';sa=' . $actions['sa'];

				// Add the text
				switch ($actions['sa'])
				{
					// Tasks
					case 'tasks':
						if ($actions['area'] === 'categories')
							$default_txt = $txt['TasksManager_who_viewing_categories_tasks'];
						break;
					// Add
					case 'add':
						$default_txt = $txt['TasksManager_who_adding_' . $actions['area']];
						break;
					// Edit
					case 'editp':
					case 'edit':
						$default_txt = $txt['TasksManager_who_editing_' . $actions['area']];
						// Add the id?
						if (isset($actions['id']))
							$default_url .= ';id=' . $actions['id'];
						break;
					// Booking
					case 'booking':
						$default_txt = $txt['TasksManager_who_booking'];
						break;
				}
			}

			// Setup who is they are allowed to see that area
			if (!empty($allowed_area))
				$data[$key] = sprintf($default_txt, $default_url);
		}
	}
}