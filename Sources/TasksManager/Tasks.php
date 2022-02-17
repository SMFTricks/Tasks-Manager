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

class Tasks
{
	/**
	 * @var array The subactions of the page
	 */
	private $_subactions = [];

	/**
	 * @var array The categories
	 */
	private $_categories = [];

	/**
	 * @var array The projects
	 */
	private $_projects = [];

	/**
	 * @var array The statuses
	 */
	private $_statuses = [];

	function __construct()
	{
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
		View::page_setup('tasks', null, null, null, 'posts');

		// Tabs
		$context[$context['tasks_menu_name']]['tab_data']['tabs'] = [
			'index' => ['description' => $txt['TasksManager_tasks_index_desc']],
			'add' => ['description' => $txt['TasksManager_tasks_add_desc']],
		];

		// Get the current action
		call_helper(__CLASS__ . '::' . $this->_subactions[isset($_GET['sa'], $this->_subactions[$_GET['sa']]) ? $_GET['sa'] : 'index'] . '#');
	}

	public function list()
	{
		global $scripturl, $context, $context, $sourcedir, $modSettings, $txt;

		// Page setup
		View::page_setup('tasks', 'show_list', 'tasks_index', '?action=tasksmanager;area=tasks;sa=index', 'posts');
		
		// Setup the list
		require_once($sourcedir . '/Subs-List.php');
		$context['default_list'] = 'tasks_list';

		// List
		$listOptions = [
			'id' => 'tasks_list',
			'items_per_page' => !empty($modSettings['tppm_items_per_page']) ? $modSettings['tppm_items_per_page'] : 20,
			'base_href' => $scripturl . '?action=tasksmanager;area=tasks;sa=index',
			'default_sort_col' => 'title',
			'get_items' => [
				'function' => __NAMESPACE__ . '\Tasks::getTasks',
			],
			'get_count' => [
				'function' => __NAMESPACE__ . '\Tasks::countTasks',
			],
			'no_items_label' => $txt['TasksManager_no_tasks'],
			'no_items_align' => 'center',
			'columns' => [
				'title' => [
					'header' => [
						'value' => $txt['TasksManager_tasks_name'],
						'class' => 'lefttext',
					],
					'data' => [
						'function' => function($row)
						{
							$title = '<strong>' . $row['task_name'] . '</strong>';

							$title .= (!empty($row['task_desc']) ? '<br />
								<span class="smalltext">' . parse_bbc($row['task_desc']) . '</span>' : '');

							return  $title;
						},
						'style' => 'width: 25%;',
					],
					'sort' => [
						'default' => 'task_name',
						'reverse' => 'task_name DESC',
					],
				],
				'details' => [
					'header' => [
						'value' => $txt['TasksManager_tasks_details'],
						'class' => 'lefttext',
						'style' => 'width: 20%;',
					],
					'data' => [
						'function' => function($row) use ($txt)
						{
							// Category
							$details = '<strong>'. $txt['TasksManager_tasks_category'] . ':</strong> ' . (!empty($row['task_cat_id']) ? $row['category_name'] : $txt['TasksManager_uncategorized']);

							// Start Date
							$details .= (!empty($row['start_date']) ? '<br /><strong>' . $txt['TasksManager_projects_start_date'] . ':</strong> ' . $row['start_date'] : '');

							// End Date
							$details .= (!empty($row['end_date']) ? '<br /><strong>' . $txt['TasksManager_projects_end_date'] . ':</strong> ' . $row['end_date'] : '');

							return  $details;
						}
					],
				],
				'project' => [
					'header' => [
						'value' => $txt['TasksManager_tasks_project'],
					],
					'data' => [
						'function' => function($row) use ($txt)
						{
							return  !empty($row['project_id']) ? $row['project_title'] : $txt['TasksManager_tasks_no_project'];
						},
						'style' => 'width: 20%;',
						'class' => 'centertext',
					],
					'sort' => [
						'default' => 'project_title',
						'reverse' => 'project_title DESC',
					],
				],
				'status' => [
					'header' => [
						'value' => $txt['TasksManager_tasks_status'],
						'style' => 'width: 12%;',
					],
					'data' => [
						'function' => function($row) use ($txt)
						{
							// Status
							return (!empty($row['task_status_id']) ? $row['status_name'] : $txt['TasksManager_projects_no_status']);
						},
						'class' => 'centertext',
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
						'style' => 'width: 7%;',
					],
					'data' => [
						'sprintf' => [
							'format' => '<a href="' . $scripturl . '?action=tasksmanager;area=tasks;sa=edit;id=%1$s">' . $txt['modify'] . '</a>',
							'params' => [
								'task_id' => false,
							],
						],
						'class' => 'centertext',
					],
					'sort' => [
						'default' => 'task_id',
						'reverse' => 'task_id DESC',
					],
				],
				'delete' => [
					'header' => [
						'value' => $txt['delete'],
						'class' => 'centertext',
						'style' => 'width: 7%;',
					],
					'data' => [
						'sprintf' => [
							'format' => '<a href="' . $scripturl . '?action=tasksmanager;area=tasks;sa=delete;id=%1$s;' . $context['session_var'] . '=' . $context['session_id'] . '" onclick="return confirm(\'' . $txt['quickmod_confirm'] . '\');">' . $txt['delete'] . '</a>',
							'params' => [
								'task_id' => false,
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
			$listOptions['additional_rows']['updated']['value'] .= $txt['TasksManager_tasks_' . (!isset($_REQUEST['deleted']) ? (!isset($_REQUEST['added']) ? (!isset($_REQUEST['updated']) ? '' : 'updated') : 'added') : 'deleted')] . '</div>';
		}

		// Visually remove options if the user doesn't have permissions
		if (!allowedTo('tasksmanager_can_edit'))
		{
			unset($listOptions['columns']['modify']);
			unset($listOptions['columns']['delete']);
		}

		createList($listOptions);
	}

	public function manage()
	{
		global $context, $scripturl, $txt, $modSettings;

		// Can you manage tasks?
		isAllowedTo('tasksmanager_can_edit');

		// Page setup
		View::page_setup('tasks', 'manage', 'tasks_' . $_REQUEST['sa'], '?action=tasksmanager;area=tasks;sa=' . $_REQUEST['sa'] . (!empty($_REQUEST['id']) ? ';id=' . $_REQUEST['id'] : ''), 'settings');

		// Editing?
		if (isset($_REQUEST['sa']) && $_REQUEST['sa'] == 'edit')
		{
			// Type id
			if (!isset($_REQUEST['id']) || empty($_REQUEST['id']))
				fatal_lang_error('TasksManager_no_task', false);

			// Get the type
			$context['tasks_pp_task'] = Tasks::getTasks(0, 1, 'tk.task_id', 'WHERE tk.task_id = {int:id}', ['id' => (int) $_REQUEST['id']]);

			// No type?
			if (empty($context['tasks_pp_task']))
				fatal_lang_error('TasksManager_no_task', false);
			else
				$context['tasks_pp_task'] = $context['tasks_pp_task'][$_REQUEST['id']];
		}

		loadCSSFile('jquery-ui.datepicker.css', [], 'smf_datepicker');
		loadJavaScriptFile('jquery-ui.datepicker.min.js', ['defer' => true], 'smf_datepicker');
		loadJavaScriptFile('jquery.timepicker.min.js', ['defer' => true], 'smf_timepicker');
		loadJavaScriptFile('datepair.min.js', ['defer' => true], 'smf_datepair');
		addInlineJavaScript('
			$("#allday").click(function(){
				$("#start_time").attr("disabled", this.checked);
				$("#end_time").attr("disabled", this.checked);
				$("#tz").attr("disabled", this.checked);
			});
			$("#event_time_input .date_input").datepicker({
				dateFormat: "yy-mm-dd",
				autoSize: true,
				isRTL: ' . ($context['right_to_left'] ? 'true' : 'false') . ',
				constrainInput: true,
				showAnim: "",
				showButtonPanel: false,
				minDate: "' . $modSettings['cal_minyear'] . '-01-01",
				maxDate: "' . $modSettings['cal_maxyear'] . '-12-31",
				yearRange: "' . $modSettings['cal_minyear'] . ':' . $modSettings['cal_maxyear'] . '",
				hideIfNoPrevNext: true,
				monthNames: ["' . implode('", "', $txt['months_titles']) . '"],
				monthNamesShort: ["' . implode('", "', $txt['months_short']) . '"],
				dayNames: ["' . implode('", "', $txt['days']) . '"],
				dayNamesShort: ["' . implode('", "', $txt['days_short']) . '"],
				dayNamesMin: ["' . implode('", "', $txt['days_short']) . '"],
				prevText: "' . $txt['prev_month'] . '",
				nextText: "' . $txt['next_month'] . '",
			});
			var date_entry = document.getElementById("event_time_input");
			var date_entry_pair = new Datepair(date_entry, {
				dateClass: "date_input",
				parseDate: function (el) {
					var utc = new Date($(el).datepicker("getDate"));
					return utc && new Date(utc.getTime() + (utc.getTimezoneOffset() * 60000));
				},
				updateDate: function (el, v) {
					$(el).datepicker("setDate", new Date(v.getTime() - (v.getTimezoneOffset() * 60000)));
				}
			});
		', true);

		// Categories
		$this->categories();

		// Statuses
		$this->statuses();

		// Projects
		$this->projects();

		// Settings
		$context['tasks_pp_settings'] = [
			'task_name' => [
				'label' => $txt['TasksManager_tasks_name'],
				'value' => !empty($context['tasks_pp_task']['task_name']) ? $context['tasks_pp_task']['task_name'] : '',
				'type' => 'text',
			],
			'task_description' => [
				'label' => $txt['TasksManager_tasks_description'],
				'value' => !empty($context['tasks_pp_task']['task_desc']) ? $context['tasks_pp_task']['task_desc'] : '',
				'type' => 'textarea',
			],
			'project_id' => [
				'label' => $txt['TasksManager_tasks_project'],
				'value' => !empty($context['tasks_pp_task']['project_id']) ? $context['tasks_pp_task']['project_id'] : '',
				'type' => 'select',
				'options' => $this->_projects,
			],
			'task_cat_id' => [
				'label' => $txt['TasksManager_tasks_category'],
				'value' => !empty($context['tasks_pp_task']['task_cat_id']) ? $context['tasks_pp_task']['task_cat_id'] : 0,
				'type' => 'select',
				'options' => $this->_categories,
			],
			'task_status_id' => [
				'label' => $txt['TasksManager_tasks_status'],
				'value' => !empty($context['tasks_pp_task']['task_status_id']) ? $context['tasks_pp_task']['task_status_id'] : 0,
				'type' => 'select',
				'options' => $this->_statuses,
			],
			'start_date' => [
				'label' => $txt['TasksManager_projects_start_date'],
				'value' => !empty($context['tasks_pp_task']['start_date']) ? $context['tasks_pp_task']['start_date'] : '',
				'type' => 'time',
			],
			'end_date' => [
				'label' => $txt['TasksManager_projects_end_date'],
				'value' => !empty($context['tasks_pp_task']['end_date']) ? $context['tasks_pp_task']['end_date'] : '',
				'type' => 'time',
			],
		];

		// Add the session
		$context['tasks_pp_settings'][$context['session_var']] = [
			'value' => $context['session_id'],
			'type' => 'hidden',
		];

		// Add the id when editing
		if (!empty($context['tasks_pp_task']))
		{
			$context['tasks_pp_settings']['task_id'] = [
				'value' => $context['tasks_pp_task']['task_id'],
				'type' => 'hidden',
			];
		}

		// Post URL
		$context['post_url'] = $scripturl . '?action=tasksmanager;area=tasks;sa=save';
	}

	private function categories()
	{
		global $txt;

		$this->_categories = [
			$txt['TasksManager_projects_no_category'] => 0,
		];
		$pp_categories = Categories::GettasksCategories(0, 1000000, 'c.task_cat_id');
		// Add the categories we got
		if (!empty($pp_categories))
			foreach ($pp_categories as $category)
				$this->_categories[$category['category_name']] = $category['category_id'];
	}

	private function statuses()
	{
		global $txt;

		$this->_statuses = [
			$txt['TasksManager_projects_no_status'] => 0,
		];
		$pp_statuses = Status::getStatus(0, 1000000, 's.status_id');
		// Add the statuses we got
		if (!empty($pp_statuses))
			foreach ($pp_statuses as $status)
				$this->_statuses[$status['status_name']] = $status['status_id'];
	}

	private function projects()
	{
		global $txt;

		$this->_projects = [
			$txt['TasksManager_tasks_no_project'] => 0,
		];
		$pp_projects = Projects::getProjects(0, 1000000, 'p.project_id');
		// Add the projects we got
		if (!empty($pp_projects))
			foreach ($pp_projects as $project)
				$this->_projects[$project['project_title']] = $project['project_id'];
	}

	public static function save()
	{
		global $smcFunc;

		// Can you manage tasks?
		isAllowedTo('tasksmanager_can_edit');

		checkSession();
		$status = 'updated';

		// Task name?
		if (empty($_REQUEST['task_name']))
			fatal_lang_error('TasksManager_no_task_name', false);
		else
			$task_name = $smcFunc['htmlspecialchars']($_REQUEST['task_name'], ENT_QUOTES);

		// Check for dates
		if (!empty($_REQUEST['start_date']) && !empty($_REQUEST['end_date']) && strtotime($_REQUEST['start_date']) >= strtotime($_REQUEST['end_date']))
			fatal_lang_error('TasksManager_end_date_before_start_date', false);

		// Task description?
		$task_description = !empty($_REQUEST['task_description']) ? $smcFunc['htmlspecialchars']($_REQUEST['task_description'], ENT_QUOTES) : '';

		// Editing the task?
		if (!empty($_REQUEST['task_id']))
		{
			$smcFunc['db_query']('','
				UPDATE IGNORE {db_prefix}taskspp_tasks
				SET
					task_name = {string:task_name},
					task_desc = {string:task_desc},
					project_id = {int:project_id},
					task_cat_id = {int:task_cat_id},
					task_status_id = {int:task_status_id}' . (empty($_REQUEST['start_date']) ? '' : ', start_date = {string:start_date}') . (empty($_REQUEST['end_date']) ? '' : ', end_date = {string:end_date}') . '
				WHERE task_id = {int:id}',
				[
					'id' => (int) $_REQUEST['task_id'],
					'task_name' => $task_name,
					'task_desc' => $task_description,
					'project_id' => !empty($_REQUEST['project_id']) ? (int) $_REQUEST['project_id'] : 0,
					'task_cat_id' => !empty($_REQUEST['task_cat_id']) ? (int) $_REQUEST['task_cat_id'] : 0,
					'task_status_id' => !empty($_REQUEST['task_status_id']) ? (int) $_REQUEST['task_status_id'] : 0,
					'start_date' => !empty($_REQUEST['start_date']) ? $_REQUEST['start_date'] : '',
					'end_date' => !empty($_REQUEST['end_date']) ? $_REQUEST['end_date'] : '',
				]
			);
		}
		// Adding a type
		else
		{
			// set the data types
			$pp_columns = [
				'task_name' => 'string',
				'task_desc' => 'string',
				'project_id' => 'int',
				'task_cat_id' => 'int',
				'task_status_id' => 'int',
			];
			$pp_values = [
				$task_name,
				$task_description,
				!empty($_REQUEST['project_id']) ? (int) $_REQUEST['project_id'] : 0,
				!empty($_REQUEST['task_cat_id']) ? (int) $_REQUEST['task_cat_id'] : 0,
				!empty($_REQUEST['task_status_id']) ? (int) $_REQUEST['task_status_id'] : 0,
			];

			// Start Date
			if (!empty($_REQUEST['start_date']))
			{
				$pp_columns['start_date'] = 'string';
				$pp_values[] = $_REQUEST['start_date'];
			}
			// End Date
			if (!empty($_REQUEST['end_date']))
			{
				$pp_columns['end_date'] = 'string';
				$pp_values[] = $_REQUEST['end_date'];
			}

			$status = 'added';
			$smcFunc['db_insert']('ignore',
				'{db_prefix}taskspp_tasks',
				$pp_columns,
				$pp_values,
				[]
			);
		}

		// Redirect
		redirectexit('action=tasksmanager;area=tasks;sa=index;' . $status);
	}

	public static function getTasks($start, $limit, $sort, $query = null, $values = null)
	{
		global $smcFunc;

		// Tasks data
		$data = [
			'start' => $start,
			'limit' => $limit,
			'sort' => $sort,
		];

		// Get the other values as well
		if (!empty($values) && is_array($values))
			$data = array_merge($data, $values);

		$request = $smcFunc['db_query']('', '
			SELECT
				tk.task_id, tk.task_name, tk.task_cat_id, tk.project_id,
				tk.start_date, tk.end_date, tk.task_desc, tk.task_status_id,
				c.category_name, s.status_name, p.project_title
			FROM {db_prefix}taskspp_tasks AS tk
				LEFT JOIN {db_prefix}taskspp_projects AS p ON (p.project_id = tk.project_id)
				LEFT JOIN {db_prefix}taskspp_task_categories AS c ON (c.task_cat_id = tk.task_cat_id)
				LEFT JOIN {db_prefix}taskspp_project_status AS s ON (s.status_id = tk.task_status_id) ' . (!empty($query) ? 
				$query : '') . '
			ORDER BY {raw:sort}
			LIMIT {int:start}, {int:limit}',
			$data
		);

		$result = [];
		while ($row = $smcFunc['db_fetch_assoc']($request))
			$result[$row['task_id']] = $row;

		$smcFunc['db_free_result']($request);

		return $result;
	}

	public static function countTasks()
	{
		global $smcFunc;

		$request = $smcFunc['db_query']('', '
			SELECT COUNT(*)
			FROM {db_prefix}taskspp_tasks',
			[]
		);
		list($rows) = $smcFunc['db_fetch_row']($request);
		$smcFunc['db_free_result']($request);

		return $rows;
	}

	public function delete()
	{
		global $smcFunc;

		// Can you manage tasks?
		isAllowedTo('tasksmanager_can_edit');

		// Check the id?
		if (!isset($_REQUEST['id']) || empty($_REQUEST['id']))
			fatal_lang_error('TasksManager_no_project', false);

		// Sesh
		checkSession('get');

		// Delete the category
		$smcFunc['db_query']('','
			DELETE FROM {db_prefix}taskspp_tasks
			WHERE task_id = {int:id}',
			[
				'id' => (int) $_REQUEST['id'],
			]
		);

		// OUT!
		redirectexit('action=tasksmanager;area=tasks;sa=index;deleted');
	}
}