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
		add_integration_function('integrate_menu_buttons', __CLASS__ . '::menu_buttons', false);
		add_integration_function('integrate_admin_areas', __CLASS__ . '::language', false);
		add_integration_function('integrate_helpadmin', __CLASS__ . '::language', false);
		add_integration_function('integrate_load_permissions', __CLASS__ . '::load_permissions#', false);
		add_integration_function('integrate_load_illegal_guest_permissions', __CLASS__ . '::illegal_guest#', false);
		add_integration_function('integrate_mod_buttons', __CLASS__ . '::mod_buttons#', false);
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
	public static function menu_buttons(&$buttons)
	{
		global $scripturl, $txt, $modSettings;

		// Language
		loadLanguage('TasksManager/');

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

		// Language
		loadLanguage('TasksManager/');

		// Don't do anything if we don't have the permission
		if (!allowedTo('tasksmanager_can_edit'))
			return;

		// Find if this topic is in any task
		if (($topic_task = cache_get_data('tasksmanager_topic_tasks_' . $context['current_topic'], 3600)) === null)
		{
			// Get tasks with this topic
			$topic_task = Tasks::getTasks(0, 100000, 'tk.task_id', 'WHERE tk.topic_id = {int:topic}', ['topic' => $context['current_topic']]);

			cache_put_data('tasksmanager_topic_tasks_' . $context['current_topic'], $topic_task, 3600);
		}

		// Add a topic to a task
		if (empty($topic_task))
			$mod_buttons['tasksmanager_add_task'] = ['text' => 'TasksManager_add_topic_task', 'icon' => 'posts', 'url' => $scripturl . '?action=tasksmanager;area=tasks;sa=addtopic;id=' . $context['current_topic'] . ';' . $context['session_var'] . '=' . $context['session_id']];
		else
			$mod_buttons['tasksmanager_remove_task'] = ['text' => 'TasksManager_remove_topic_task', 'icon' => 'delete', 'url' => $scripturl . '?action=tasksmanager;area=tasks;sa=deletetopic;id=' . $context['current_topic'] . ';' . $context['session_var'] . '=' . $context['session_id']];
	}
}