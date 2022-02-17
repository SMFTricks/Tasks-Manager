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

	public static function initialize()
	{
		self::hooks();
	}

	public static function hooks()
	{
		add_integration_function('integrate_autoload', __CLASS__ . '::autoload', false);
		add_integration_function('integrate_actions', __CLASS__ . '::actions', false);
		add_integration_function('integrate_menu_buttons', __CLASS__ . '::menu_buttons', false);
		add_integration_function('integrate_admin_areas', __CLASS__ . '::language', false);
		add_integration_function('integrate_load_permissions', __CLASS__ . '::load_permissions#', false);
		add_integration_function('integrate_load_illegal_guest_permissions', __CLASS__ . '::illegal_guest#', false);
	}

	public static function autoload(&$classMap)
	{
		$classMap[__NAMESPACE__ . '\\'] = __NAMESPACE__ . '/';
	}

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

	public static function actions(&$actions)
	{
		// The main action
		$actions['tasksmanager'] = [__NAMESPACE__ . '/View.php', __NAMESPACE__  . '\View::main#'];
	}

	public static function language()
	{
		// Language for the permissions
		loadLanguage('TasksManager/');
	}

	public function load_permissions(&$permissionGroups, &$permissionList)
	{
		$permissionGroups['membergroup'][] = 'tasksmanager';
		foreach ($this->_permissions as $permission)
			$permissionList['membergroup'][$permission] = [false, 'tasksmanager'];
	}

	public function illegal_guest()
	{
		global $context;

		// Guest should not be able to edit or add anything
		$context['non_guest_permissions'][] = 'tasksmanager_can_edit';
	}
}