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

class Settings
{
	function __construct()
	{
		global $sourcedir;

		// Load Admin template
		loadTemplate('Admin', 'admin');

		// Load other languages cuz this mod setup is really stupid haha
		loadLanguage('Admin');

		// Settings....
		require_once($sourcedir . '/ManageServer.php');

		// Can you manage tasks?
		isAllowedTo('tasksmanager_can_edit');
	}

	/**
	 * Settings::permissions()
	 * 
	 * Display the permissions page
	 * @return void
	 */
	public function permissions()
	{
		global $txt;

		// Page setup
		View::page_setup('permissions', 'show_settings', null, null, 'permissions');

		// Can you manage permissions?
		isAllowedTo('manage_permissions');

		$config_vars = [
			['permissions', 'tasksmanager_can_view', 'subtext' => $txt['permissionhelp_tasksmanager_can_view']],
			['permissions', 'tasksmanager_can_edit', 'subtext' => $txt['permissionhelp_tasksmanager_can_edit']],
		];

		// Save
		$this->save($config_vars, 'permissions');
	}

	/**
	 * Settings::config()
	 * 
	 * Display the settings page
	 * @return void
	 */
	public function config()
	{
		global $txt;

		// Page setup
		View::page_setup('config', 'show_settings', null, null, 'settings');
		
		$config_vars = [
			['text', 'tppm_title', 'subtext' => $txt['tppm_title_desc']],
			['int', 'tppm_items_per_page', 'subtext' => $txt['tppm_items_per_page_desc']],
			'',
			['int', 'tppm_items_filter', 'subtext' => $txt['tppm_items_filter_desc']],
			['select', 'tppm_filter_sort', [
					$txt['tppm_filter_sort_id'],
					$txt['tppm_filter_sort_name']
				],
				'subtext' => $txt['tppm_filter_sort_desc']
			]
		];

		// Save
		$this->save($config_vars, 'config');
	}

	/**
	 * Settings::save
	 * 
	 * Save the settings
	 * @return void
	 */
	private function save($config_vars, $sa)
	{
		global $context, $scripturl;

		// Post url
		$context['post_url'] = $scripturl . '?action=tasksmanager;area='. $sa. ';save';

		// Saving?
		if (isset($_GET['save'])) {
			checkSession();
			saveDBSettings($config_vars);
			redirectexit('action=tasksmanager;area='. $sa. ';saved');
		}
		prepareDBSettingContext($config_vars);
	}
}