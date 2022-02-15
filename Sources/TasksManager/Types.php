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

class Types
{
	/**
	 * @var array The subactions of the page
	 */
	private $_subactions = [];

	function __construct()
	{
		// Can you manage tasks?
		isAllowedTo('tasksmanager_can_edit');

		// Subactions
		$this->_subactions = [
			'index' => 'list',
			'add' => 'manage',
			'edit' => 'manage',
			'save' => 'save',
			'delete' => 'delete',
		];
	}

	public function main()
	{
		global $context, $txt;

		// Page setup
		View::page_setup('types', null, null, null, 'logs');

		// Tabs
		$context[$context['tasks_menu_name']]['tab_data']['tabs'] = [
			'index' => ['description' => $txt['TasksManager_types_index_desc']],
			'add' => ['description' => $txt['TasksManager_add_type_desc']],
		];

		// Get the current action
		call_helper(__CLASS__ . '::' . $this->_subactions[isset($_GET['sa'], $this->_subactions[$_GET['sa']]) ? $_GET['sa'] : 'index'] . '#');
	}

	public function list()
	{
		global $scripturl, $context, $context, $sourcedir, $modSettings, $txt;

		// Page setup
		View::page_setup('types', 'show_list', 'types_index', '?action=tasksmanager;area=types;sa=index', 'logs');

		// Setup the list
		require_once($sourcedir . '/Subs-List.php');
		$context['default_list'] = 'types_list';

		// List
		$listOptions = [
			'id' => 'types_list',
			'title' => $txt['TasksManager_types'],
			'items_per_page' => !empty($modSettings['tppm_items_per_page']) ? $modSettings['tppm_items_per_page'] : 20,
			'base_href' => $scripturl . '?action=tasksmanager;area=type;sa=index',
			'default_sort_col' => 'name',
			'get_items' => [
				'function' => __NAMESPACE__ . '\Types::getTypes',
			],
			'get_count' => [
				'function' => __NAMESPACE__ . '\Types::countTypes',
			],
			'no_items_label' => $txt['TasksManager_no_types'],
			'no_items_align' => 'center',
			'columns' => [
				'name' => [
					'header' => [
						'value' => $txt['TasksManger_types_name'],
						'style' => 'width: 65%;',
						'class' => 'lefttext',
					],
					'data' => [
						'db' => 'type_name',
					],
					'sort' => [
						'default' => 'type_name DESC',
						'reverse' => 'type_name',
					],
				],
				'modify' => [
					'header' => [
						'value' => $txt['modify'],
						'class' => 'centertext',
					],
					'data' => [
						'sprintf' => [
							'format' => '<a href="' . $scripturl . '?action=tasksmanager;area=types;sa=edit;id=%1$s">' . $txt['modify'] . '</a>',
							'params' => [
								'type_id' => false,
							],
						],
						'class' => 'centertext',
					],
					'sort' => [
						'default' => 'type_id DESC',
						'reverse' => 'type_id',
					],
				],
				'delete' => [
					'header' => [
						'value' => $txt['delete'],
						'class' => 'centertext',
					],
					'data' => [
						'sprintf' => [
							'format' => '<a href="' . $scripturl . '?action=tasksmanager;area=types;sa=delete;id=%1$s;' . $context['session_var'] . '=' . $context['session_id'] . '" onclick="return confirm(\'' . $txt['quickmod_confirm'] . '\');">' . $txt['delete'] . '</a>',
							'params' => [
								'type_id' => false,
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
			$listOptions['additional_rows']['updated']['value'] .= $txt['TasksManger_types_' . (!isset($_REQUEST['deleted']) ? (!isset($_REQUEST['added']) ? (!isset($_REQUEST['updated']) ? '' : 'updated') : 'added') : 'deleted')] . '</div>';
		}
		createList($listOptions);
	}

	public function manage()
	{
		global $context, $scripturl, $txt;

		// Page setup
		View::page_setup('types', 'manage', $_REQUEST['sa'] . '_type', '?action=tasksmanager;area=types;sa=' . $_REQUEST['sa'] . (!empty($_REQUEST['id']) ? ';id=' . $_REQUEST['id'] : ''), 'settings');

		// Editing?
		if (isset($_REQUEST['sa']) && $_REQUEST['sa'] == 'edit')
		{
			// Type id
			if (!isset($_REQUEST['id']) || empty($_REQUEST['id']))
				fatal_lang_error('TasksManager_no_type');

			// Get the type
			$context['tasks_pp_type'] = Types::getTypes(0, 1, 't.type_id', 'WHERE t.type_id = {int:id}', ['id' => (int) $_REQUEST['id']]);

			// No type?
			if (empty($context['tasks_pp_type']))
				fatal_lang_error('TasksManager_no_type');
			else
				$context['tasks_pp_type'] = $context['tasks_pp_type'][0];
		}

		// Settings
		$context['tasks_pp_settings'] = [
			'type_name' => [
				'label' => $txt['TasksManger_types_name'],
				'value' => !empty($context['tasks_pp_type']['type_name']) ? $context['tasks_pp_type']['type_name'] : '',
				'type' => 'text',
			],
		];

		// Add the session
		$context['tasks_pp_settings'][$context['session_var']] = [
			'value' => $context['session_id'],
			'type' => 'hidden',
		];

		// Add the id when editing
		if (!empty($context['tasks_pp_type']))
		{
			$context['tasks_pp_settings']['type_id'] = [
				'value' => $context['tasks_pp_type']['type_id'],
				'type' => 'hidden',
			];
		}

		// Post URL
		$context['post_url'] = $scripturl . '?action=tasksmanager;area=types;sa=save';
	}

	public function save()
	{
		global $smcFunc;

		checkSession();
		$status = 'updated';

		// Type name?
		if (empty($_REQUEST['type_name']))
			fatal_lang_error('TasksManager_no_type_name', false);
		else
			$type_name = $smcFunc['htmlspecialchars']($_REQUEST['type_name'], ENT_QUOTES);

		// Editing the type?
		if (!empty($_REQUEST['type_id']))
		{
			$smcFunc['db_query']('','
				UPDATE IGNORE {db_prefix}taskspp_project_types
				SET
				type_name = {string:type_name}
				WHERE type_id = {int:id}',
				[
					'id' => (int) $_REQUEST['type_id'],
					'type_name' => $type_name,
				]
			);
		}
		// Adding a type
		else
		{
			$status = 'added';
			$smcFunc['db_insert']('ignore',
				'{db_prefix}taskspp_project_types',
				['type_name' => 'string'],
				[$type_name,],
				[]
			);
		}

		// Redirect
		redirectexit('action=tasksmanager;area=types;sa=index;' . $status);
	}

	public static function getTypes($start, $limit, $sort, $query = null, $values = null)
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
				t.type_id, t.type_name
			FROM {db_prefix}taskspp_project_types AS t ' . (!empty($query) ? 
				$query : '') . '
			ORDER BY {raw:sort}
			LIMIT {int:start}, {int:limit}',
			$data
		);

		$result = [];
		while ($row = $smcFunc['db_fetch_assoc']($request))
			$result[$row['type_id']] = $row;

		$smcFunc['db_free_result']($request);

		return $result;
	}

	public static function countTypes()
	{
		global $smcFunc;

		$request = $smcFunc['db_query']('', '
			SELECT COUNT(*)
			FROM {db_prefix}taskspp_project_types',
			[]
		);
		$rows = $smcFunc['db_num_rows']($request);
		$smcFunc['db_free_result']($request);

		return $rows;
	}

	public function delete()
	{
		global $smcFunc;

		// Check the id?
		if (!isset($_REQUEST['id']) || empty($_REQUEST['id']))
			fatal_lang_error('TasksManager_no_type', false);

		// Sesh
		checkSession('get');

		// Delete the category
		$smcFunc['db_query']('','
			DELETE FROM {db_prefix}taskspp_project_types
			WHERE type_id = {int:id}',
			[
				'id' => (int) $_REQUEST['id'],
			]
		);

		// OUT!
		redirectexit('action=tasksmanager;area=types;sa=index;deleted');
	}
}