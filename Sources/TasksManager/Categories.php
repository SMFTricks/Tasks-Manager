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

class Categories
{
	/**
	 * @var array The subactions of the page
	 */
	private $_subactions;

	/**
	 * Categories::main()
	 * 
	 * Setup the categories area and load the actions
	 * @return array
	 */
	public function main()
	{
		global $context, $txt;

		// Can you manage tasks?
		isAllowedTo('tasksmanager_can_edit');

		// Page setup
		View::page_setup('categories', null, null, null, 'boards');

		// Tabs
		$context[$context['tasks_menu_name']]['tab_data']['tabs'] = [
			'projects' => ['description' => $txt['TasksManager_projects_cat_desc']],
			'tasks' => ['description' => $txt['TasksManager_tasks_cat_desc']],
			'add' => ['description' => $txt['TasksManager_add_category_desc']],
		];

		// Subactions
		$this->_subactions = [
			'projects' => 'view',
			'tasks' => 'view',
			'add' => 'manage',
			'edit' => 'manage',
			'editp' => 'manage',
			'save' => 'save',
			'delete' => 'delete',
			'deletep' => 'delete',
		];

		// Get the current action
		call_helper(__CLASS__ . '::' . $this->_subactions[isset($_GET['sa'], $this->_subactions[$_GET['sa']]) ? $_GET['sa'] : 'projects'] . '#');
	}

	/**
	 * Categories::project()
	 * 
	 * Loads the list of project categories
	 * @return array
	 */
	public function view()
	{
		global $scripturl, $context, $sourcedir, $modSettings, $txt;
		
		// Page setup
		View::page_setup(
			(!isset($_REQUEST['sa']) || $_REQUEST['sa'] == 'projects' ? 'projects' : 'tasks'),
			'show_list',
			(!isset($_REQUEST['sa']) || $_REQUEST['sa'] == 'projects' ? 'projects' : 'tasks') . '_cat',
			'?action=tasksmanager;area=categories;sa=' . (!isset($_REQUEST['sa']) || $_REQUEST['sa'] == 'projects' ? 'projects' : 'tasks'),
			'boards'
		);

		// Setup the list
		require_once($sourcedir . '/Subs-List.php');
		$context['default_list'] = (!isset($_REQUEST['sa']) || $_REQUEST['sa'] == 'projects' ? 'projects' : 'tasks') .'_categories';

		// List
		$listOptions = [
			'id' => (!isset($_REQUEST['sa']) || $_REQUEST['sa'] == 'projects' ? 'projects' : 'tasks') . '_categories',
			'title' => $txt['TasksManager_categories'] . ' - ' . $txt['TasksManager_' . (!isset($_REQUEST['sa']) || $_REQUEST['sa'] == 'projects' ? 'projects' : 'tasks') . '_cat'],
			'items_per_page' => !empty($modSettings['tppm_items_per_page']) ? $modSettings['tppm_items_per_page'] : 20,
			'base_href' => $scripturl . '?action=tasksmanager;area=categories;sa=' . (!isset($_REQUEST['sa']) || $_REQUEST['sa'] == 'projects' ? 'projects' : 'tasks'),
			'default_sort_col' => 'name',
			'get_items' => [
				'function' => __NAMESPACE__ . '\Categories::getCategories',
				'params' => [null, null, (!isset($_REQUEST['sa']) || $_REQUEST['sa'] == 'projects' ? 'projects' : 'tasks')],
			],
			'get_count' => [
				'function' => __NAMESPACE__ . '\Categories::countCategories',
				'params' => [null, null, (!isset($_REQUEST['sa']) || $_REQUEST['sa'] == 'projects' ? 'projects' : 'tasks')],
			],
			'no_items_label' => $txt['TasksManager_no_categories'],
			'no_items_align' => 'center',
			'columns' => [
				'name' => [
					'header' => [
						'value' => $txt['TasksManager_category_name'],
						'style' => 'width: 60%;',
						'class' => 'lefttext',
					],
					'data' => [
						'db' => 'category_name',
					],
					'sort' => [
						'default' => 'category_name',
						'reverse' => 'category_name DESC',
					],
				],
				'modify' => [
					'header' => [
						'value' => $txt['modify'],
						'class' => 'centertext',
					],
					'data' => [
						'sprintf' => [
							'format' => '<a href="' . $scripturl . '?action=tasksmanager;area=categories;sa=' . (!isset($_REQUEST['sa']) || $_REQUEST['sa'] == 'projects' ? 'editp' : 'edit') . ';id=%1$s">' . $txt['modify'] . '</a>',
							'params' => [
								'category_id' => false,
							],
						],
						'class' => 'centertext',
					],
					'sort' => [
						'default' => 'category_id',
						'reverse' => 'category_id DESC',
					],
				],
				'delete' => [
					'header' => [
						'value' => $txt['delete'],
						'class' => 'centertext',
					],
					'data' => [
						'sprintf' => [
							'format' => '<a href="' . $scripturl . '?action=tasksmanager;area=categories;sa=' . (!isset($_REQUEST['sa']) || $_REQUEST['sa'] == 'projects' ? 'deletep' : 'delete') . ';id=%1$s;' . $context['session_var'] . '=' . $context['session_id'] . '" onclick="return confirm(\'' . $txt['TasksManager_category_delete_confirm'] . '\');">' . $txt['delete'] . '</a>',
							'params' => [
								'category_id' => false,
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
			$listOptions['additional_rows']['updated']['value'] .= $txt['TasksManager_category_' . (!isset($_REQUEST['deleted']) ? (!isset($_REQUEST['added']) ? (!isset($_REQUEST['updated']) ? '' : 'updated') : 'added') : 'deleted')] . '</div>';
		}
		createList($listOptions);
	}

	/**
	 * Categories:manage()
	 * 
	 * Edit or create a category
	 * @return void
	 */
	public function manage()
	{
		global $context, $scripturl, $txt;

		// Page setup
		View::page_setup('categories', 'manage', $_REQUEST['sa'] . '_category', '?action=tasksmanager;area=categories;sa=' . $_REQUEST['sa'] . (!empty($_REQUEST['id']) ? ';id=' . $_REQUEST['id'] : ''), 'settings');

		// Editing?
		if (isset($_REQUEST['sa']) && ($_REQUEST['sa'] == 'edit' || $_REQUEST['sa'] == 'editp'))
		{
			// Category id?
			if (!isset($_REQUEST['id']) || empty($_REQUEST['id']))
				fatal_lang_error('TasksManager_no_category', false);

			// Set the active tab
			$context[$context['tasks_menu_name']]['current_subsection'] = $_REQUEST['sa'] == 'editp' ? 'projects' : 'tasks';

			// Get the categoy
			$context['tasks_pp_category'] = $this->getCategories(0, 1, 'category_id', 'WHERE c.' . ($_REQUEST['sa'] == 'editp' ? 'category' : 'task_cat') . '_id = {int:cat}', ['cat' => (int) $_REQUEST['id']], ($_REQUEST['sa'] == 'editp' ? 'projects' : 'tasks'));

			// No category?
			if (empty($context['tasks_pp_category']))
				fatal_lang_error('TasksManager_no_category', false);
			else
				$context['tasks_pp_category'] = $context['tasks_pp_category'][$_REQUEST['id']];
		}

		// Settings
		$context['tasks_pp_settings'] = [
			'category_name' => [
				'label' => $txt['TasksManager_category_name'],
				'value' => !empty($context['tasks_pp_category']['category_name']) ? $context['tasks_pp_category']['category_name'] : '',
				'type' => 'text',
			],
			'category_type' => [
				'label' => $txt['TasksManager_category_type'],
				'value' => '',
				'type' => 'select',
				'options' => [
					'project' => $txt['TasksManager_projects'],
					'task' => $txt['TasksManager_tasks'],
				],
			],
		];

		// Add the session
		$context['tasks_pp_settings'][$context['session_var']] = [
			'value' => $context['session_id'],
			'type' => 'hidden',
		];

		// Do some changes for when editing a cat
		if (!empty($context['tasks_pp_category']))
		{
			// Remove the type
			unset($context['tasks_pp_settings']['category_type']);

			// Add the id
			$context['tasks_pp_settings']['category_id'] = [
				'value' => $context['tasks_pp_category']['category_id'],
				'type' => 'hidden',
			];

			// The current type
			$context['tasks_pp_settings']['category_type'] = [
				'value' => ($_REQUEST['sa'] == 'editp' ? 'project' : 'task'),
				'type' => 'hidden',
			];
		}

		// Post URL
		$context['post_url'] = $scripturl . '?action=tasksmanager;area=categories;sa=save';
	}

	/**
	 * Categories:save()
	 * 
	 * Save a category
	 * @return void
	 */
	public function save()
	{
		global $smcFunc;

		checkSession();
		$status = 'updated';

		// Category name?
		if (empty($_REQUEST['category_name']))
			fatal_lang_error('TasksManager_no_category_name', false);
		else
			$category_name = (string) $smcFunc['htmlspecialchars']($_REQUEST['category_name'], ENT_QUOTES);
		
		// Edit the category
		if (!empty($_REQUEST['category_id']))
		{
			$smcFunc['db_query']('','
				UPDATE {db_prefix}taskspp_' . ($_REQUEST['category_type'] == 'project'  ? 'project' : 'task') .  '_categories
				SET
					category_name = {string:category_name}
				WHERE ' . ($_REQUEST['category_type'] == 'project' ? 'category_id'  : 'task_cat_id') .  ' = {int:cat}',
				[
					'cat' => (int) $_REQUEST['category_id'],
					'category_name' => $category_name,
				]
			);
		}
		// Add the category
		else
		{
			$status = 'added';
			$smcFunc['db_insert']('',
				'{db_prefix}taskspp_' . ($_REQUEST['category_type'] == 'project'  ? 'project' : 'task') .  '_categories',
				['category_name' => 'string'],
				[$category_name,],
				[]
			);
		}

		// We are done
		redirectexit('action=tasksmanager;area=categories;sa=' . ($_REQUEST['category_type'] == 'project' ? 'projects' : 'tasks') . ';' . $status);
	}

	/**
	 * Categories::getCategories()
	 * 
	 * Get the categories for tasks or projects
	 * @param int $start The start of the list
	 * @param int $limit The limit of the list
	 * @param string $sort The sort order
	 * @param string $query Any additional queries
	 * @param array $values The values to be used in the query
	 * @param string $type The type of categories to get
	 * @return array The task categories
	 */
	public static function getCategories($start, $limit, $sort, $query = null, $values = null, $type = 'projects')
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
				c.' . ($type == 'projects' ? 'category' : 'task_cat') .  '_id AS category_id, c.category_name AS category_name
			FROM {db_prefix}taskspp_' . ($type == 'projects' ? 'project' : 'task') .  '_categories AS c ' . (!empty($query) ? 
				$query : '') . '
			ORDER BY {raw:sort}
			LIMIT {int:start}, {int:limit}',
			$data
		);

		$result = [];
		while ($row = $smcFunc['db_fetch_assoc']($request))
			$result[$row['category_id']] = $row;

		$smcFunc['db_free_result']($request);

		return $result;
	}

	/**
	 * Categories::countCategories()
	 * 
	 * Get the total number of categories
	 * @param string $type The type of category (projects or tasks)
	 * @return int The total number of categories
	 */
	public static function countCategories($type = 'projects')
	{
		global $smcFunc;

		$request = $smcFunc['db_query']('', '
			SELECT COUNT(*)
			FROM {db_prefix}taskspp_' . ($type == 'projects' ? 'project' : 'task') .  '_categories',
			[]
		);
		list($rows) = $smcFunc['db_fetch_row']($request);
		$smcFunc['db_free_result']($request);

		return $rows;
	}

	/**
	 * Categories:delete()
	 * 
	 * Delete a category
	 * @return void
	 */
	public function delete()
	{
		global $smcFunc;

		// Check the id?
		if (!isset($_REQUEST['id']) || empty($_REQUEST['id']))
			fatal_lang_error('TasksManager_no_category', false);

		// Sesh
		checkSession('get');

		// Delete the category
		$smcFunc['db_query']('','
			DELETE FROM {db_prefix}taskspp_' . ($_REQUEST['sa'] == 'deletep'  ? 'project' : 'task') .  '_categories
			WHERE ' . ($_REQUEST['sa'] == 'deletep' ? 'category_id'  : 'task_cat_id') .  ' = {int:cat}',
			[
				'cat' => (int) $_REQUEST['id'],
			]
		);

		// Make the project or tasks uncategorized
		$smcFunc['db_query']('', '
			UPDATE {db_prefix}taskspp_' . ($_REQUEST['sa'] == 'deletep'  ? 'project' : 'task') .  's
			SET ' . ($_REQUEST['sa'] == 'deletep' ? 'category_id'  : 'task_cat_id') .  ' = 0
			WHERE ' . ($_REQUEST['sa'] == 'deletep' ? 'category_id'  : 'task_cat_id') .  ' = {int:cat}',
			[
				'cat' => (int) $_REQUEST['id'],
			]
		);

		// OUT!
		redirectexit('action=tasksmanager;area=categories;sa=' . ($_REQUEST['sa'] == 'deletep' ? 'projects' : 'tasks') . ';deleted');
	}
}