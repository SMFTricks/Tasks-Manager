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
	private $_subactions;

	/**
	 * @var array The categories
	 */
	private $_categories = [];

	/**
	 * @var array The projects
	 */
	private $_projects = [];

	/**
	 * @var array The tasks
	 */
	private $_tasks = [];

	/**
	 * @var array The statuses
	 */
	private $_statuses = [];

	/**
	 * Tasks::main()
	 * 
	 * Setup the tasks details and load the action
	 * @return array
	 */
	public function main()
	{
		global $context, $txt, $modSettings;

		// Page setup
		View::page_setup('tasks', null, null, null, 'posts');

		// Tabs
		$context[$context['tasks_menu_name']]['tab_data']['tabs'] = [
			'index' => ['description' => $txt['TasksManager_tasks_index_desc']],
			'add' => ['description' => $txt['TasksManager_tasks_add_desc']],
		];

		// Subactions
		$this->_subactions = [
			'index' => 'list',
			'add' => 'manage',
			'edit' => 'manage',
			'save' => 'save',
			'delete' => 'delete',
			'addtopic' => 'addtopic',
			'savetopic' => 'savetopic',
			'deletetopic' => 'deletetopic',
		];

		// Projects
		$this->_projects = Projects::getProjects(0, !empty($modSettings['tppm_items_filter']) ? $modSettings['tppm_items_filter'] : 25, !empty($modSettings['tppm_filter_sort']) ? 'p.project_title' : 'p.project_id DESC');

		// Categories
		$this->_categories = Categories::getCategories(0, !empty($modSettings['tppm_items_filter']) ? $modSettings['tppm_items_filter'] : 25, !empty($modSettings['tppm_filter_sort']) ? 'category_name' : 'category_id DESC', null, null, 'tasks');

		// Statuses
		$this->_statuses = Status::getStatus(0, !empty($modSettings['tppm_items_filter']) ? $modSettings['tppm_items_filter'] : 25, !empty($modSettings['tppm_filter_sort']) ? 's.status_name' : 's.status_id DESC');

		// Get the current action
		call_helper(__CLASS__ . '::' . $this->_subactions[isset($_GET['sa'], $this->_subactions[$_GET['sa']]) ? $_GET['sa'] : 'index'] . '#');
	}

	/**
	 * Tasks::list()
	 * 
	 * List the tasks
	 * @return void
	 */
	public function list()
	{
		global $scripturl, $context, $context, $sourcedir, $modSettings, $txt;

		// Page setup
		View::page_setup('tasks', 'show_list', 'tasks_index', '?action=tasksmanager;area=tasks;sa=index', 'posts');
		
		// Setup the list
		require_once($sourcedir . '/Subs-List.php');
		$context['default_list'] = 'tasks_list';

		// Set the filter
		if (!empty($this->_projects) || !empty($this->_statuses) || !empty($this->_categories))
		{
			// Projects
			$context['tasks_projects_list'] = $this->_projects;
			//Categories
			$context['tasks_category_list'] = $this->_categories;
			// Statuses
			$context['tasks_status_list'] = $this->_statuses;
			// Filter layer
			$context['template_layers'][] = 'list_selector';
		}

		// Form URL
		$context['form_url'] = $scripturl . '?action=tasksmanager;area=tasks;sa=index' . (isset($_REQUEST['project']) && $_REQUEST['project'] >= 0 ? ';project=' . $_REQUEST['project'] : '' ) . (isset($_REQUEST['status']) && $_REQUEST['status'] >= 0 ? ';status=' . $_REQUEST['status'] : '' ) . (isset($_REQUEST['category']) && $_REQUEST['category'] >= 0 ? ';category=' . $_REQUEST['category'] : '' );

		// List
		$listOptions = [
			'id' => 'tasks_list',
			'items_per_page' => !empty($modSettings['tppm_items_per_page']) ? $modSettings['tppm_items_per_page'] : 20,
			'base_href' => $context['form_url'],
			'default_sort_col' => 'title',
			'get_items' => [
				'function' => __NAMESPACE__ . '\Tasks::getTasks',
				'params' => ['WHERE ' . (isset($_REQUEST['project']) && $_REQUEST['project'] >= 0 ? 'tk.project_id = {int:project}' : ' 1=1') . (isset($_REQUEST['status']) && $_REQUEST['status'] >= 0 ? ' AND tk.task_status_id = {int:status}' : ' AND 1=1') . (isset($_REQUEST['category']) && $_REQUEST['category'] >= 0 ? ' AND tk.task_cat_id = {int:cat}' : ' AND 1=1'), ['project' => (int) isset($_REQUEST['project']) ? $_REQUEST['project'] : 0, 'status' => (int) isset($_REQUEST['status']) ? $_REQUEST['status'] : 0, 'cat' => (int) isset($_REQUEST['category']) ? $_REQUEST['category'] : 0]],
			],
			'get_count' => [
				'function' => __NAMESPACE__ . '\Tasks::countTasks',
				'params' => ['WHERE ' . (isset($_REQUEST['project']) && $_REQUEST['project'] >= 0 ? 'project_id = {int:project}' : ' 1=1') . (isset($_REQUEST['status']) && $_REQUEST['status'] >= 0 ? ' AND task_status_id = {int:status}' : ' AND 1=1') . (isset($_REQUEST['category']) && $_REQUEST['category'] >= 0 ? ' AND task_cat_id = {int:cat}' : ' AND 1=1'), ['project' => (int) isset($_REQUEST['project']) ? $_REQUEST['project'] : 0, 'status' => (int) isset($_REQUEST['status']) ? $_REQUEST['status'] : 0, 'cat' => (int) isset($_REQUEST['category']) ? $_REQUEST['category'] : 0]],
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
						'function' => function($row) use ($scripturl)
						{
							$title = '<h6>' . $row['task_name'] . '</h6>';

							if (!empty($row['topic_id']))
								$title = '<h6><a href="' . $scripturl . '?topic=' . $row['topic_id'] . '.0" title="' . $row['task_name'] . '">' . $row['task_name'] . '</a></h6>';

							$title .= (!empty($row['task_desc']) ? '<span class="smalltext">' . parse_bbc($row['task_desc']) . '</span>' : '');

							return  $title;
						},
						'style' => 'width: 30%;',
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
							$details = '<strong>'. $txt['TasksManager_category'] . ':</strong> ' . (!empty($row['task_cat_id']) ? $row['category_name'] : $txt['TasksManager_uncategorized']);

							// Estimated hours
							$details .= '<br /><strong>'. $txt['TasksManager_tasks_estimated_hours'] . ':</strong> ' . (!empty($row['estimated_hrs']) ? $row['estimated_hrs'] .' ' . $txt['hours'] : $txt['TasksManager_tasks_estimated_unknown']);

							// Minutes
							$minutes = (!empty($row['minutes_worked']) ? ($row['minutes_worked'] % 60) : 0);

							// Actual hours
							$hours = $row['hours_worked'] + floor($row['minutes_worked'] / 60);

							$details .= '<br /><strong>'. $txt['TasksManager_tasks_time_booked'] . ':</strong> ' . (!empty($hours) || !empty($minutes) ? sprintf('%02d', $hours). ':' . sprintf('%02d', $minutes) : $txt['TasksManager_no_total_time']);

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

	/**
	 * Tasks:manage()
	 * 
	 * Edit or create a task
	 * @return void
	 */
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
			// Task id
			if (!isset($_REQUEST['id']) || empty($_REQUEST['id']))
				fatal_lang_error('TasksManager_no_task', false);

			// Get the task
			$context['tasks_pp_task'] = Tasks::getTasks(0, 1, 'tk.task_id', 'WHERE tk.task_id = {int:id}', ['id' => (int) $_REQUEST['id']]);

			// No task?
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
				monthNames: ["' . implode('", "', (array) $txt['months_titles']) . '"],
				monthNamesShort: ["' . implode('", "', (array) $txt['months_short']) . '"],
				dayNames: ["' . implode('", "', (array) $txt['days']) . '"],
				dayNamesShort: ["' . implode('", "', (array) $txt['days_short']) . '"],
				dayNamesMin: ["' . implode('", "', (array) $txt['days_short']) . '"],
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
				'options' => View::itemSelect($this->_projects, 'project_id', 'project_title', $txt['TasksManager_tasks_no_project']),
			],
			'task_cat_id' => [
				'label' => $txt['TasksManager_tasks_category'],
				'value' => !empty($context['tasks_pp_task']['task_cat_id']) ? $context['tasks_pp_task']['task_cat_id'] : 0,
				'type' => 'select',
				'options' => View::itemSelect($this->_categories, 'category_id', 'category_name', $txt['TasksManager_projects_no_category']),
			],
			'task_status_id' => [
				'label' => $txt['TasksManager_tasks_status'],
				'value' => !empty($context['tasks_pp_task']['task_status_id']) ? $context['tasks_pp_task']['task_status_id'] : 0,
				'type' => 'select',
				'options' => View::itemSelect($this->_statuses, 'status_id', 'status_name', $txt['TasksManager_projects_no_status']),
			],
			'estimated_hrs' => [
				'label' => $txt['TasksManager_tasks_estimated_hours'],
				'value' => !empty($context['tasks_pp_task']['estimated_hrs']) ? $context['tasks_pp_task']['estimated_hrs'] : '',
				'type' => 'number',
			],
			'start_date' => [
				'label' => $txt['TasksManager_projects_start_date'],
				'value' => !empty($context['tasks_pp_task']['start_date']) ? $context['tasks_pp_task']['start_date'] : '',
				'type' => 'date',
			],
			'end_date' => [
				'label' => $txt['TasksManager_projects_end_date'],
				'value' => !empty($context['tasks_pp_task']['end_date']) ? $context['tasks_pp_task']['end_date'] : '',
				'type' => 'date',
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

	/**
	 * Tasks::save()
	 * 
	 * Save a new or edited task
	 * @return void
	 */
	public function save()
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
		if (!empty($_REQUEST['start_date']) && !empty($_REQUEST['end_date']) && strtotime($_REQUEST['start_date']) > strtotime($_REQUEST['end_date']))
			fatal_lang_error('TasksManager_end_date_before_start_date', false);

		// Task description?
		$task_description = !empty($_REQUEST['task_description']) ? $smcFunc['htmlspecialchars']($_REQUEST['task_description'], ENT_QUOTES) : '';

		// Editing the task?
		if (!empty($_REQUEST['task_id']))
		{
			$smcFunc['db_query']('','
				UPDATE {db_prefix}taskspp_tasks
				SET
					task_name = {string:task_name},
					task_desc = {string:task_desc},
					estimated_hrs = {int:estimated_hrs},
					project_id = {int:project_id},
					task_cat_id = {int:task_cat_id},
					task_status_id = {int:task_status_id}' . (empty($_REQUEST['start_date']) ? '' : ', start_date = {string:start_date}') . (empty($_REQUEST['end_date']) ? '' : ', end_date = {string:end_date}') . '
				WHERE task_id = {int:id}',
				[
					'id' => (int) $_REQUEST['task_id'],
					'task_name' => $task_name,
					'task_desc' => $task_description,
					'estimated_hrs' => !empty($_REQUEST['estimated_hrs']) && isset($_REQUEST['estimated_hrs']) ? (int) $_REQUEST['estimated_hrs'] : 0,
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
				'estimated_hrs' => 'int',
				'project_id' => 'int',
				'task_cat_id' => 'int',
				'task_status_id' => 'int',
			];
			$pp_values = [
				$task_name,
				$task_description,
				!empty($_REQUEST['estimated_hrs']) && isset($_REQUEST['estimated_hrs']) ? (int) $_REQUEST['estimated_hrs'] : 0,
				!empty($_REQUEST['project_id']) &&  isset($_REQUEST['project_id']) ? (int) $_REQUEST['project_id'] : 0,
				!empty($_REQUEST['task_cat_id']) && isset($_REQUEST['task_cat_id']) ? (int) $_REQUEST['task_cat_id'] : 0,
				!empty($_REQUEST['task_status_id']) && isset($_REQUEST['task_status_id']) ? (int) $_REQUEST['task_status_id'] : 0,
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
			$smcFunc['db_insert']('',
				'{db_prefix}taskspp_tasks',
				$pp_columns,
				$pp_values,
				[]
			);
		}

		// Redirect
		redirectexit('action=tasksmanager;area=tasks;sa=index;' . $status . ($status == 'updated' ? ((!empty($_REQUEST['project_id']) &&  isset($_REQUEST['project_id']) ? ';project=' . (int) $_REQUEST['project_id'] : '') . (!empty($_REQUEST['task_cat_id']) &&  isset($_REQUEST['task_cat_id']) ? ';category=' . (int) $_REQUEST['task_cat_id'] : '')) : ''));
	}

	/**
	 * Tasks::getTasks()
	 * 
	 * Get the tasks
	 * @param int $start The start of the list
	 * @param int $limit The limit of the list
	 * @param string $sort The sort order
	 * @param string $query Any additional queries
	 * @param array $values The values to be used in the query
	 * @return array The tasks
	 */
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
				tk.task_id, tk.topic_id, tk.task_name, tk.task_cat_id, tk.project_id,
				tk.start_date, tk.end_date, tk.task_desc, tk.task_status_id, tk.estimated_hrs,
				c.category_name, s.status_name, p.project_title, SUM(ts.hours_worked) AS hours_worked, SUM(ts.minutes_worked) AS minutes_worked
			FROM {db_prefix}taskspp_tasks AS tk
				LEFT JOIN {db_prefix}taskspp_timesheet AS ts ON (ts.task_id = tk.task_id)
				LEFT JOIN {db_prefix}taskspp_projects AS p ON (p.project_id = tk.project_id)
				LEFT JOIN {db_prefix}taskspp_task_categories AS c ON (c.task_cat_id = tk.task_cat_id)
				LEFT JOIN {db_prefix}taskspp_project_status AS s ON (s.status_id = tk.task_status_id) ' . (!empty($query) ? 
				$query : '') . '
			GROUP BY tk.task_id, tk.topic_id, c.category_name, s.status_name, p.project_title
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

	/**
	 * Tasks::countTasks()
	 * 
	 * Get the tasks total
	 * @param string $query Any additional queries
	 * @param array $values The values to be used in the query
	 * @return int The total number of tasks
	 */
	public static function countTasks($query = null, $values = null)
	{
		global $smcFunc;

		// Tasks data
		$data = [];

		// Get the other values as well
		if (!empty($values) && is_array($values))
			$data = array_merge($data, $values);

		$request = $smcFunc['db_query']('', '
			SELECT COUNT(*)
			FROM {db_prefix}taskspp_tasks ' . (!empty($query) ? 
			$query : ''),
			$data
		);
		list($rows) = $smcFunc['db_fetch_row']($request);
		$smcFunc['db_free_result']($request);

		return $rows;
	}

	/**
	 * Tasks::delete()
	 * 
	 * Delete a task
	 * @return void
	 */
	public function delete()
	{
		global $smcFunc;

		// Can you manage tasks?
		isAllowedTo('tasksmanager_can_edit');

		// Check the id?
		if (!isset($_REQUEST['id']) || empty($_REQUEST['id']))
			fatal_lang_error('TasksManager_no_task', false);

		// Sesh
		checkSession('get');

		// Delete the task
		$smcFunc['db_query']('','
			DELETE FROM {db_prefix}taskspp_tasks
			WHERE task_id = {int:id}',
			[
				'id' => (int) $_REQUEST['id'],
			]
		);
		// Remove bookings
		$smcFunc['db_query']('','
			DELETE FROM {db_prefix}taskspp_timesheet
			WHERE task_id = {int:id}',
			[
				'id' => (int) $_REQUEST['id'],
			]
		);

		// OUT!
		redirectexit('action=tasksmanager;area=tasks;sa=index;deleted');
	}

	/**
	 * Tasks::addtopic()
	 * 
	 * Add/link a topic to a task
	 * @return void
	 */
	public function addtopic()
	{
		global $smcFunc, $context, $scripturl, $txt;

		// Can you manage tasks?
		isAllowedTo('tasksmanager_can_edit');

		// Check the id?
		if (!isset($_REQUEST['id']) || empty($_REQUEST['id']))
			fatal_lang_error('TasksManager_no_topic', false);

		// Sesh
		checkSession('get');

		// Page setup
		View::page_setup('tasks', 'manage', 'add_topic_task',  '?action=tasksmanager;area=tasks;sa=' . $_REQUEST['sa'] . (!empty($_REQUEST['id']) ? ';id=' . $_REQUEST['id'] : '') . ';' . $context['session_var'] . '=' . $context['session_id'], 'approve');

		// Add some topic context
		$request = $smcFunc['db_query']('', '
			SELECT
				t.id_topic, m.subject
			FROM {db_prefix}topics AS t
				LEFT JOIN {db_prefix}messages AS m ON (m.id_msg = t.id_first_msg)
			WHERE t.id_topic = {int:id}',
			[
				'id' => (int) $_REQUEST['id'],
			]
		);
		list ($context['task_topic_id'], $context['task_topic_subject']) = $smcFunc['db_fetch_row']($request);
		$smcFunc['db_free_result']($request);
		$context['TasksManager_adding_topic'] = $txt['TasksManager_adding_topic_task'];

		// Check again for the topic
		if (empty($context['task_topic_id']))
			fatal_lang_error('TasksManager_no_topic', false);

		// Setup a layer so the user understand the context of this page a little bit better
		$context['template_layers'][]= 'add_topic';

		// Get the tasks that don't have a topic
		$this->_tasks = self::getTasks(0, 1000000, 'tk.task_name',  'WHERE tk.topic_id = {int:no_topic}', ['no_topic' => 0]);

		// Check if there are any tasks available
		if (empty($this->_tasks))
			fatal_lang_error('TasksManager_no_tasks_available', false);

		// Sort the tasks for the select
		$this->_tasks = View::itemSelect($this->_tasks, 'task_id', 'task_name', '');
		unset($this->_tasks[0]);

		// Settings
		$context['tasks_pp_settings'] = [
			'task_id' => [
				'label' => $txt['TasksManager_tasks'],
				'type' => 'select',
				'options' => $this->_tasks,
			],
			'topic_id' => [
				'value' => (int) $_REQUEST['id'],
				'type' => 'hidden',
			],
		];

		// Add the session
		$context['tasks_pp_settings'][$context['session_var']] = [
			'value' => $context['session_id'],
			'type' => 'hidden',
		];

		// Post URL
		$context['post_url'] = $scripturl . '?action=tasksmanager;area=tasks;sa=savetopic';
	}

	/**
	 * Tasks::savetopic()
	 * 
	 * Update the topic value for the task
	 * @return void
	 */
	public function savetopic()
	{
		global $smcFunc;

		// Can you manage tasks?
		isAllowedTo('tasksmanager_can_edit');

		// Task and topic?
		if (empty($_REQUEST['task_id']) || empty($_REQUEST['topic_id']))
			fatal_lang_error('TasksManager_no_topic', false);

		checkSession();

		// User might add a different topic to a task so let's drop it from any other task
		$smcFunc['db_query']('', '
			UPDATE {db_prefix}taskspp_tasks
			SET
				topic_id = {int:no_topic}
			WHERE topic_id = {int:topic_id}',
			[
				'no_topic' => 0,
				'topic_id' => (int) $_REQUEST['topic_id'],
			]
		);

		// Add the topic to the task
		$smcFunc['db_query']('','
			UPDATE {db_prefix}taskspp_tasks
			SET
				topic_id = {int:topic_id}
			WHERE task_id = {int:id}',
			[
				'id' => (int) $_REQUEST['task_id'],
				'topic_id' => (int) $_REQUEST['topic_id'],
			]
		);

		// Redirect
		redirectexit('topic=' . $_REQUEST['topic_id'] . '.0');
	}

	/**
	 * Tasks::deletetopic()
	 * 
	 * Delete the topic from the task
	 * @return void
	 */
	public function deletetopic()
	{
		global $smcFunc;

		// Can you manage tasks?
		isAllowedTo('tasksmanager_can_edit');

		// Check the id?
		if (!isset($_REQUEST['id']) || empty($_REQUEST['id']))
			fatal_lang_error('TasksManager_no_topic', false);

		// Sesh
		checkSession('get');

		// Drop this topic from the task (or tasks lol)
		$smcFunc['db_query']('','
			UPDATE {db_prefix}taskspp_tasks
			SET
				topic_id = {int:id}
			WHERE topic_id = {int:topic_id}',
			[
				'id' => 0,
				'topic_id' => (int) $_REQUEST['id'],
			]
		);

		// OUT!
		redirectexit('topic=' . $_REQUEST['id'] . '.0');
	}
}