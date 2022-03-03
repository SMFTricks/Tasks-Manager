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

class Status
{
	/**
	 * @var array The subactions of the page
	 */
	private $_subactions;

	/**
	 * Status::main()
	 * 
	 * Setup the status area and load the actions
	 * @return array
	 */
	public function main()
	{
		global $context, $txt;

		// Can you manage tasks?
		isAllowedTo('tasksmanager_can_edit');

		// Page setup
		View::page_setup('status', null, 'statuses', null, 'warning');

		// Tabs
		$context[$context['tasks_menu_name']]['tab_data']['tabs'] = [
			'index' => ['description' => $txt['TasksManager_status_list_desc']],
			'add' => ['description' => $txt['TasksManager_status_add_desc']],
		];

		// Subactions
		$this->_subactions = [
			'index' => 'list',
			'add' => 'manage',
			'edit' => 'manage',
			'save' => 'save',
			'delete' => 'delete',
		];

		// Get the current action
		call_helper(__CLASS__ . '::' . $this->_subactions[isset($_GET['sa'], $this->_subactions[$_GET['sa']]) ? $_GET['sa'] : 'index'] . '#');
	}

	/**
	 * Status::list()
	 * 
	 * List the statuses
	 * @return array
	 */
	public function list()
	{
		global $scripturl, $context, $context, $sourcedir, $modSettings, $txt;

		// Page setup
		View::page_setup('index', 'show_list', 'status_list', '?action=tasksmanager;area=status;sa=index', 'warning');

		// Setup the list
		require_once($sourcedir . '/Subs-List.php');
		$context['default_list'] = 'status_list';

		// List
		$listOptions = [
			'id' => 'status_list',
			'title' => $txt['TasksManager_status_list'],
			'items_per_page' => !empty($modSettings['tppm_items_per_page']) ? $modSettings['tppm_items_per_page'] : 20,
			'base_href' => $scripturl . '?action=tasksmanager;area=status;sa=index',
			'default_sort_col' => 'name',
			'get_items' => [
				'function' => __NAMESPACE__ . '\Status::getStatus',
			],
			'get_count' => [
				'function' => __NAMESPACE__ . '\Status::countStatus',
			],
			'no_items_label' => $txt['TasksManager_no_statuses'],
			'no_items_align' => 'center',
			'columns' => [
				'name' => [
					'header' => [
						'value' => $txt['TasksManager_status_name'],
						'style' => 'width: 65%;',
						'class' => 'lefttext',
					],
					'data' => [
						'db' => 'status_name',
					],
					'sort' => [
						'default' => 'status_name',
						'reverse' => 'status_name DESC',
					],
				],
				'modify' => [
					'header' => [
						'value' => $txt['modify'],
						'class' => 'centertext',
					],
					'data' => [
						'sprintf' => [
							'format' => '<a href="' . $scripturl . '?action=tasksmanager;area=status;sa=edit;id=%1$s">' . $txt['modify'] . '</a>',
							'params' => [
								'status_id' => false,
							],
						],
						'class' => 'centertext',
					],
					'sort' => [
						'default' => 'status_id',
						'reverse' => 'status_id DESC',
					],
				],
				'delete' => [
					'header' => [
						'value' => $txt['delete'],
						'class' => 'centertext',
					],
					'data' => [
						'sprintf' => [
							'format' => '<a href="' . $scripturl . '?action=tasksmanager;area=status;sa=delete;id=%1$s;' . $context['session_var'] . '=' . $context['session_id'] . '" onclick="return confirm(\'' . $txt['quickmod_confirm'] . '\');">' . $txt['delete'] . '</a>',
							'params' => [
								'status_id' => false,
							],
						],
						'class' => 'centertext',
					],
				],
			],
		];
		// Info?
		if (isset($_REQUEST['deleted']) || isset($_REQUEST['added']) || isset($_REQUEST['updated']))
		{
			$listOptions['additional_rows']['updated'] = [
				'position' => 'top_of_list',
				'value' => '<div class="infobox">',
			];
			$listOptions['additional_rows']['updated']['value'] .= $txt['TasksManager_status_' . (!isset($_REQUEST['deleted']) ? (!isset($_REQUEST['added']) ? (!isset($_REQUEST['updated']) ? '' : 'updated') : 'added') : 'deleted')] . '</div>';
		}
		createList($listOptions);
	}

	/**
	 * Status::manage()
	 * 
	 * Setup the booking area and load the actions
	 * @return array
	 */
	public function manage()
	{
		global $context, $scripturl, $txt;

		// Page setup
		View::page_setup('statuses', 'manage', $_REQUEST['sa'] . '_status', '?action=tasksmanager;area=status;sa=' . $_REQUEST['sa'] . (!empty($_REQUEST['id']) ? ';id=' . $_REQUEST['id'] : ''), 'settings');

		// Editing?
		if (isset($_REQUEST['sa']) && $_REQUEST['sa'] == 'edit')
		{
			// Status id
			if (!isset($_REQUEST['id']) || empty($_REQUEST['id']))
				fatal_lang_error('TasksManager_no_status', false);

			// Get the status
			$context['tasks_pp_status'] = Status::getStatus(0, 1, 's.status_id', 'WHERE s.status_id = {int:id}', ['id' => (int) $_REQUEST['id']]);

			// No status? Sad
			if (empty($context['tasks_pp_status']))
				fatal_lang_error('TasksManager_no_status', false);
			else
				$context['tasks_pp_status'] = $context['tasks_pp_status'][$_REQUEST['id']];
		}

		// Settings
		$context['tasks_pp_settings'] = [
			'status_name' => [
				'label' => $txt['TasksManager_status_name'],
				'value' => !empty($context['tasks_pp_status']['status_name']) ? $context['tasks_pp_status']['status_name'] : '',
				'type' => 'text',
			],
		];

		// Add the session
		$context['tasks_pp_settings'][$context['session_var']] = [
			'value' => $context['session_id'],
			'type' => 'hidden',
		];

		// Add the id when editing
		if (!empty($context['tasks_pp_status']))
		{
			$context['tasks_pp_settings']['status_id'] = [
				'value' => $context['tasks_pp_status']['status_id'],
				'type' => 'hidden',
			];
		}

		// Post URL
		$context['post_url'] = $scripturl . '?action=tasksmanager;area=status;sa=save';
	}

	/**
	 * Status::save()
	 * 
	 * Add a status
	 * @return void
	 */
	public function save()
	{
		global $smcFunc;

		checkSession();
		$status = 'updated';

		// Status name?
		if (empty($_REQUEST['status_name']))
			fatal_lang_error('TasksManager_no_status_name', false);
		else
			$status_name = $smcFunc['htmlspecialchars']($_REQUEST['status_name'], ENT_QUOTES);

		// Editing the status?
		if (!empty($_REQUEST['status_id']))
		{
			$smcFunc['db_query']('','
				UPDATE {db_prefix}taskspp_project_status
				SET
					status_name = {string:status_name}
				WHERE status_id = {int:id}',
				[
					'id' => (int) $_REQUEST['status_id'],
					'status_name' => $status_name,
				]
			);
		}
		// Adding a status
		else
		{
			$status = 'added';
			$smcFunc['db_insert']('',
				'{db_prefix}taskspp_project_status',
				['status_name' => 'string'],
				[$status_name,],
				[]
			);
		}

		// Redirect
		redirectexit('action=tasksmanager;area=status;sa=index;' . $status);
	}

	/**
	 * Status::getStatus()
	 * 
	 * Get the statuses
	 * @param int $start The start of the list
	 * @param int $limit The limit of the list
	 * @param string $sort The sort order
	 * @param string $query Any additional queries
	 * @param array $values The values to be used in the query
	 * @return array The list of statuses
	 */
	public static function getStatus($start, $limit, $sort, $query = null, $values = null)
	{
		global $smcFunc;

		// Categories data
		$data = [
			'start' => $start,
			'limit' => $limit,
			'sort' => $sort,
		];

		// Get the rest of the values
		if (!empty($values) && is_array($values))
			$data = array_merge($data, $values);

		$request = $smcFunc['db_query']('', '
			SELECT
				s.status_id, s.status_name
			FROM {db_prefix}taskspp_project_status AS s ' . (!empty($query) ? 
				$query : '') . '
			ORDER BY {raw:sort}
			LIMIT {int:start}, {int:limit}',
			$data
		);

		$result = [];
		while ($row = $smcFunc['db_fetch_assoc']($request))
			$result[$row['status_id']] = $row;

		$smcFunc['db_free_result']($request);

		return $result;
	}

	/**
	 * Status::countStatus()
	 * 
	 * Get the statuses count
	 * @param string $query Any additional queries
	 * @param array $values The values to be used in the query
	 * @return int The total number of statuses
	 */
	public static function countStatus()
	{
		global $smcFunc;

		$request = $smcFunc['db_query']('', '
			SELECT COUNT(*)
			FROM {db_prefix}taskspp_project_status',
			[]
		);
		list($rows) = $smcFunc['db_fetch_row']($request);
		$smcFunc['db_free_result']($request);

		return $rows;
	}

	/**
	 * Status::delete()
	 * 
	 * Delete a status
	 * @return void
	 */
	public function delete()
	{
		global $smcFunc;

		// Check the id?
		if (!isset($_REQUEST['id']) || empty($_REQUEST['id']))
			fatal_lang_error('TasksManager_no_status', false);

		// Sesh
		checkSession('get');

		// Delete the category
		$smcFunc['db_query']('','
			DELETE FROM {db_prefix}taskspp_project_status
			WHERE status_id = {int:id}',
			[
				'id' => (int) $_REQUEST['id'],
			]
		);

		// Remove the status from projects
		$smcFunc['db_query']('','
			UPDATE {db_prefix}taskspp_projects
			SET status_id = 0
			WHERE status_id = {int:id}',
			[
				'id' => (int) $_REQUEST['id'],
			]
		);
		// Remove the status from tasks
		$smcFunc['db_query']('','
			UPDATE {db_prefix}taskspp_tasks
			SET task_status_id = 0
			WHERE task_status_id = {int:id}',
			[
				'id' => (int) $_REQUEST['id'],
			]
		);

		// OUT!
		redirectexit('action=tasksmanager;area=status;sa=index;deleted');
	}
}