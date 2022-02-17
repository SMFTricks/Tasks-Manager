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

class Projects
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
	 * @var array The types
	 */
	private $_types = [];

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
		View::page_setup('projects', null, null, null, 'reports');

		// Tabs
		$context[$context['tasks_menu_name']]['tab_data']['tabs'] = [
			'index' => ['description' => $txt['TasksManager_projects_index_desc']],
			'add' => ['description' => $txt['TasksManager_projects_add_desc']],
		];

		// Get the current action
		call_helper(__CLASS__ . '::' . $this->_subactions[isset($_GET['sa'], $this->_subactions[$_GET['sa']]) ? $_GET['sa'] : 'index'] . '#');
	}

	public function list()
	{
		global $scripturl, $context, $context, $sourcedir, $modSettings, $txt;

		// Page setup
		View::page_setup('projects', 'show_list', 'projects_index', '?action=tasksmanager;area=projects;sa=index', 'reports');
		
		// Setup the list
		require_once($sourcedir . '/Subs-List.php');
		$context['default_list'] = 'projects_list';

		// List
		$listOptions = [
			'id' => 'projects_list',
			'items_per_page' => !empty($modSettings['tppm_items_per_page']) ? $modSettings['tppm_items_per_page'] : 20,
			'base_href' => $scripturl . '?action=tasksmanager;area=projects;sa=index',
			'default_sort_col' => 'title',
			'get_items' => [
				'function' => __NAMESPACE__ . '\Projects::getProjects',
			],
			'get_count' => [
				'function' => __NAMESPACE__ . '\Projects::countProjects',
			],
			'no_items_label' => $txt['TasksManager_no_projects'],
			'no_items_align' => 'center',
			'columns' => [
				'picture' => [
					'header' => [
						'style' => 'width: 64px;',
					],
					'data' => [
						'function' => function ($row) {
							return (!empty($row['project_picture']) ? '<img style="max-width: 64px; max-height: 64px" src="' . $row['project_picture'] . '" alt="'. $row['project_title'] . '" />' : '');
						},
					],
				],
				'title' => [
					'header' => [
						'value' => $txt['TasksManager_projects_title'],
						'class' => 'lefttext',
					],
					'data' => [
						'function' => function($row)
						{
							$title = '<strong>' . $row['project_title'] . '</strong>';

							$title .= (!empty($row['description']) ? '<br />
								<span class="smalltext">' . parse_bbc($row['description']) . '</span>' : '');

							return  $title;
						},
						'style' => 'width: 50%;',
					],
					'sort' => [
						'default' => 'project_title',
						'reverse' => 'project_title DESC',
					],
				],
				'details' => [
					'header' => [
						'value' => $txt['TasksManager_projects_details'],
						'class' => 'lefttext',
					],
					'data' => [
						'function' => function($row) use ($txt)
						{
							// Category
							$details = '<strong>'. $txt['TasksManager_category'] . ':</strong> ' . (!empty($row['category_id']) ? $row['category_name'] : $txt['TasksManager_uncategorized']);

							// Type
							$details .= (!empty($row['type_id']) ? '<br /><strong>' . $txt['TasksManager_projects_type'] . ':</strong> ' . $row['type_name'] : '');

							// Start Date
							$details .= (!empty($row['start_date']) ? '<br /><strong>' . $txt['TasksManager_projects_start_date'] . ':</strong> ' . $row['start_date'] : '');

							// End Date
							$details .= (!empty($row['end_date']) ? '<br /><strong>' . $txt['TasksManager_projects_end_date'] . ':</strong> ' . $row['end_date'] : '');

							return  $details;
						}
					],
				],
				'status' => [
					'header' => [
						'value' => $txt['TasksManager_projects_status'],
					],
					'data' => [
						'function' => function($row) use ($txt)
						{
							// Status
							return (!empty($row['status_id']) ? $row['status_name'] : $txt['TasksManager_projects_no_status']);
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
							'format' => '<a href="' . $scripturl . '?action=tasksmanager;area=projects;sa=edit;id=%1$s">' . $txt['modify'] . '</a>',
							'params' => [
								'project_id' => false,
							],
						],
						'class' => 'centertext',
					],
					'sort' => [
						'default' => 'project_id',
						'reverse' => 'project_id DESC',
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
							'format' => '<a href="' . $scripturl . '?action=tasksmanager;area=projects;sa=delete;id=%1$s;' . $context['session_var'] . '=' . $context['session_id'] . '" onclick="return confirm(\'' . $txt['quickmod_confirm'] . '\');">' . $txt['delete'] . '</a>',
							'params' => [
								'project_id' => false,
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
			$listOptions['additional_rows']['updated']['value'] .= $txt['TasksManager_projects_' . (!isset($_REQUEST['deleted']) ? (!isset($_REQUEST['added']) ? (!isset($_REQUEST['updated']) ? '' : 'updated') : 'added') : 'deleted')] . '</div>';
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
		View::page_setup('projects', 'manage', 'projects_' . $_REQUEST['sa'], '?action=tasksmanager;area=projects;sa=' . $_REQUEST['sa'] . (!empty($_REQUEST['id']) ? ';id=' . $_REQUEST['id'] : ''), 'settings');

		// Editing?
		if (isset($_REQUEST['sa']) && $_REQUEST['sa'] == 'edit')
		{
			// Type id
			if (!isset($_REQUEST['id']) || empty($_REQUEST['id']))
				fatal_lang_error('TasksManager_no_project', false);

			// Get the type
			$context['tasks_pp_project'] = Projects::getProjects(0, 1, 'p.project_id', 'WHERE p.project_id = {int:id}', ['id' => (int) $_REQUEST['id']]);

			// No type?
			if (empty($context['tasks_pp_project']))
				fatal_lang_error('TasksManager_no_project', false);
			else
				$context['tasks_pp_project'] = $context['tasks_pp_project'][$_REQUEST['id']];
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

		// Types
		$this->types();

		// Categories
		$this->categories();

		// Statuses
		$this->statuses();

		// Settings
		$context['tasks_pp_settings'] = [
			'project_title' => [
				'label' => $txt['TasksManager_projects_title'],
				'value' => !empty($context['tasks_pp_project']['project_title']) ? $context['tasks_pp_project']['project_title'] : '',
				'type' => 'text',
			],
			'project_picture' => [
				'label' => $txt['TasksManager_projects_picture'],
				'value' => !empty($context['tasks_pp_project']['project_picture']) ? $context['tasks_pp_project']['project_picture'] : '',
				'type' => 'text',
				'description' => $txt['TasksManager_projects_picture_desc'],
			],
			'project_description' => [
				'label' => $txt['TasksManager_projects_description'],
				'value' => !empty($context['tasks_pp_project']['description']) ? $context['tasks_pp_project']['description'] : '',
				'type' => 'textarea',
			],
			'category_id' => [
				'label' => $txt['TasksManager_projects_category'],
				'value' => !empty($context['tasks_pp_project']['category_id']) ? $context['tasks_pp_project']['category_id'] : 0,
				'type' => 'select',
				'options' => $this->_categories,
			],
			'type_id' => [
				'label' => $txt['TasksManager_projects_type'],
				'value' => !empty($context['tasks_pp_project']['type_id']) ? $context['tasks_pp_project']['type_id'] : 0,
				'type' => 'select',
				'options' => $this->_types,
			],
			'status_id' => [
				'label' => $txt['TasksManager_projects_status'],
				'value' => !empty($context['tasks_pp_project']['status_id']) ? $context['tasks_pp_project']['status_id'] : 0,
				'type' => 'select',
				'options' => $this->_statuses,
			],
			'start_date' => [
				'label' => $txt['TasksManager_projects_start_date'],
				'value' => !empty($context['tasks_pp_project']['start_date']) ? $context['tasks_pp_project']['start_date'] : '',
				'type' => 'time',
			],
			'end_date' => [
				'label' => $txt['TasksManager_projects_end_date'],
				'value' => !empty($context['tasks_pp_project']['end_date']) ? $context['tasks_pp_project']['end_date'] : '',
				'type' => 'time',
			],
		];

		// Add the session
		$context['tasks_pp_settings'][$context['session_var']] = [
			'value' => $context['session_id'],
			'type' => 'hidden',
		];

		// Add the id when editing
		if (!empty($context['tasks_pp_project']))
		{
			$context['tasks_pp_settings']['project_id'] = [
				'value' => $context['tasks_pp_project']['project_id'],
				'type' => 'hidden',
			];
		}

		// Post URL
		$context['post_url'] = $scripturl . '?action=tasksmanager;area=projects;sa=save';
	}

	private function types()
	{
		global $txt;

		$this->_types = [
			$txt['TasksManager_projects_no_type'] => 0,
		];
		$pp_types = Types::getTypes(0, 1000000, 't.type_id');
		// Add the types we got
		if (!empty($pp_types))
			foreach ($pp_types as $type)
				$this->_types[$type['type_name']] = $type['type_id'];
	}

	private function categories()
	{
		global $txt;

		$this->_categories = [
			$txt['TasksManager_projects_no_category'] => 0,
		];
		$pp_categories = Categories::GetprojectCategories(0, 1000000, 'c.category_id');
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

	public static function save()
	{
		global $smcFunc;

		// Can you manage tasks?
		isAllowedTo('tasksmanager_can_edit');

		checkSession();
		$status = 'updated';

		// Project title?
		if (empty($_REQUEST['project_title']))
			fatal_lang_error('TasksManager_no_project_title', false);
		else
			$project_title = $smcFunc['htmlspecialchars']($_REQUEST['project_title'], ENT_QUOTES);

		// Check for dates
		if (!empty($_REQUEST['start_date']) && !empty($_REQUEST['end_date']) && strtotime($_REQUEST['start_date']) >= strtotime($_REQUEST['end_date']))
			fatal_lang_error('TasksManager_end_date_before_start_date', false);

		// Project description?
		$project_description = !empty($_REQUEST['project_description']) ? $smcFunc['htmlspecialchars']($_REQUEST['project_description'], ENT_QUOTES) : '';

		// Project picture?
		$project_picture = !empty($_REQUEST['project_picture']) ? $smcFunc['htmlspecialchars']($_REQUEST['project_picture'], ENT_QUOTES) : '';

		// Editing the project?
		if (!empty($_REQUEST['project_id']))
		{
			$smcFunc['db_query']('','
				UPDATE IGNORE {db_prefix}taskspp_projects
				SET
					project_title = {string:project_title},
					project_picture = {string:project_picture},
					description = {string:description},
					category_id = {int:category_id},
					type_id = {int:type_id},
					status_id = {int:status_id}' . (empty($_REQUEST['start_date']) ? '' : ', start_date = {string:start_date}') . (empty($_REQUEST['end_date']) ? '' : ', end_date = {string:end_date}') . '
				WHERE project_id = {int:id}',
				[
					'id' => (int) $_REQUEST['project_id'],
					'project_title' => $project_title,
					'project_picture' => $project_picture,
					'description' => $project_description,
					'category_id' => !empty($_REQUEST['category_id']) ? (int) $_REQUEST['category_id'] : 0,
					'type_id' => !empty($_REQUEST['type_id']) ? (int) $_REQUEST['type_id'] : 0,
					'status_id' => !empty($_REQUEST['status_id']) ? (int) $_REQUEST['status_id'] : 0,
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
				'project_title' => 'string',
				'project_picture' => 'string',
				'description' => 'string',
				'category_id' => 'int',
				'type_id' => 'int',
				'status_id' => 'int',
			];
			$pp_values = [
				$project_title,
				$project_picture,
				$project_description,
				!empty($_REQUEST['category_id']) ? (int) $_REQUEST['category_id'] : 0,
				!empty($_REQUEST['type_id']) ? (int) $_REQUEST['type_id'] : 0,
				!empty($_REQUEST['status_id']) ? (int) $_REQUEST['status_id'] : 0,
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
				'{db_prefix}taskspp_projects',
				$pp_columns,
				$pp_values,
				[]
			);
		}

		// Redirect
		redirectexit('action=tasksmanager;area=projects;sa=index;' . $status);
	}

	public static function getProjects($start, $limit, $sort, $query = null, $values = null)
	{
		global $smcFunc;

		// Projects data
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
				p.project_id, p.project_title, p.project_picture, p.view_type, p.category_id,
				p.start_date, p.end_date, p.description, p.related_items, p.type_id, p.status_id,
				c.category_name, t.type_name, s.status_name
			FROM {db_prefix}taskspp_projects AS p
				LEFT JOIN {db_prefix}taskspp_project_categories AS c ON (c.category_id = p.category_id)
				LEFT JOIN {db_prefix}taskspp_project_types AS t ON (t.type_id = p.type_id)
				LEFT JOIN {db_prefix}taskspp_project_status AS s ON (s.status_id = p.status_id) ' . (!empty($query) ? 
				$query : '') . '
			ORDER BY {raw:sort}
			LIMIT {int:start}, {int:limit}',
			$data
		);

		$result = [];
		while ($row = $smcFunc['db_fetch_assoc']($request))
			$result[$row['project_id']] = $row;

		$smcFunc['db_free_result']($request);

		return $result;
	}

	public static function countProjects()
	{
		global $smcFunc;

		$request = $smcFunc['db_query']('', '
			SELECT COUNT(*)
			FROM {db_prefix}taskspp_projects',
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
			DELETE FROM {db_prefix}taskspp_projects
			WHERE project_id = {int:id}',
			[
				'id' => (int) $_REQUEST['id'],
			]
		);

		// OUT!
		redirectexit('action=tasksmanager;area=projects;sa=index;deleted');
	}
}