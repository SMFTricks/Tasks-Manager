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

class Book
{
	/**
	 * @var array The subactions of the page
	 */
	private $_subactions;

	/**
	 * @var array The tasks in the filter
	 */
	private $_tasks = [];

	/**
	 * Book::main()
	 * 
	 * Setup the booking area and load the actions
	 * @return array
	 */
	public function main()
	{
		global $context, $txt, $modSettings;

		// Can you manage tasks?
		isAllowedTo('tasksmanager_can_edit');

		// Page setup
		View::page_setup('booking', null, null, null, 'calendar');

		// Tabs
		$context[$context['tasks_menu_name']]['tab_data']['tabs'] = [
			'log' => ['description' => $txt['TasksManager_booking_log_desc']],
			'booktime' => ['description' => $txt['TasksManager_booking_booktime_desc']],
		];

		// Subactions
		$this->_subactions = [
			'log' => 'list',
			'booktime' => 'manage',
			'save' => 'save',
			'delete' => 'delete',
		];

		// Tasks
		$this->_tasks = Tasks::getTasks(0, !empty($modSettings['tppm_items_filter']) ? $modSettings['tppm_items_filter'] : 25, !empty($modSettings['tppm_filter_sort']) ? 'tk.task_name' : 'tk.task_id DESC');

		// Get the current action
		call_helper(__CLASS__ . '::' . $this->_subactions[isset($_GET['sa'], $this->_subactions[$_GET['sa']]) ? $_GET['sa'] : 'log'] . '#');
	}

	/**
	 * Book:list()
	 * 
	 * List the bookings
	 * @return void
	 */
	public function list()
	{
		global $scripturl, $context, $context, $sourcedir, $modSettings, $txt;

		// Page setup
		View::page_setup('booking', 'show_list', 'booking_log', '?action=tasksmanager;area=booking;sa=log', 'scheduled');
		
		// Setup the list
		require_once($sourcedir . '/Subs-List.php');
		$context['default_list'] = 'book_log';

		// Add the filter
		if (!empty($this->_tasks))
		{
			$context['tasks_tasks_list'] = $this->_tasks;
			$context['template_layers'][] = 'list_selector';
		}

		// Form URL
		$context['form_url'] = $scripturl . '?action=tasksmanager;area=booking;sa=log' . (isset($_REQUEST['task']) && $_REQUEST['task'] >= 0 ? ';task=' . $_REQUEST['task'] : '' );

		// List
		$listOptions = [
			'id' => 'book_log',
			'items_per_page' => !empty($modSettings['tppm_items_per_page']) ? $modSettings['tppm_items_per_page'] : 20,
			'base_href' => $context['form_url'],
			'default_sort_col' => 'date',
			'get_items' => [
				'function' => __NAMESPACE__ . '\Book::getLog',
				'params' => ['WHERE ' . (isset($_REQUEST['task']) && $_REQUEST['task'] >= 0 ? 'ts.task_id = {int:task}' : ' 1=1'), ['task' => (int) isset($_REQUEST['task']) ? $_REQUEST['task'] : 0]],
			],
			'get_count' => [
				'function' => __NAMESPACE__ . '\Book::countLog',
				'params' => ['WHERE ' . (isset($_REQUEST['task']) && $_REQUEST['task'] >= 0 ? 'task_id = {int:task}' : ' 1=1'), ['task' => (int) isset($_REQUEST['task']) ? $_REQUEST['task'] : 0]],
			],
			'no_items_label' => $txt['TasksManager_no_booking_time'],
			'no_items_align' => 'center',
			'columns' => [
				'task' => [
					'header' => [
						'value' => $txt['TasksManager_tasks_name'],
						'class' => 'lefttext',
					],
					'data' => [
						'function' => function($row)
						{
							$title = '<strong>' . $row['task_name'] . '</strong>';

							return  $title;
						},
						'style' => 'width: 15%;',
					],
					'sort' => [
						'default' => 'task_name',
						'reverse' => 'task_name DESC',
					],
				],
				'time_worked' => [
					'header' => [
						'value' => $txt['TasksManager_booking_time'],
					],
					'data' => [
						'function' => function($row)
						{
							return  sprintf('%02d', $row['hours_worked']) . ':' . sprintf('%02d', $row['minutes_worked']);
						},
						'class' => 'centertext',
					],
					'sort' => [
						'default' => 'time_worked',
						'reverse' => 'time_worked DESC',
					],
				],
				'comments' =>[
					'header' => [
						'value' => $txt['TasksManager_booking_comments'],
						'class' => 'lefttext',
					],
					'data' => [
						'db' => 'time_comments',
						'class' => 'lefttext',
					],
				],
				'date' => [
					'header' => [
						'value' => $txt['TasksManager_booking_date'],
					],
					'data' => [
						'function' => function($row)
						{
							return  timeformat($row['ts_date']);
						},
						'class' => 'centertext',
					],
					'sort' => [
						'default' => 'ts_date DESC',
						'reverse' => 'ts_date',
					],
				],
				'delete' => [
					'header' => [
						'value' => $txt['delete'],
						'class' => 'centertext',
						'style' => 'width: 12%;',
					],
					'data' => [
						'sprintf' => [
							'format' => '<a href="' . $scripturl . '?action=tasksmanager;area=booking;sa=delete;id=%1$s;' . $context['session_var'] . '=' . $context['session_id'] . '" onclick="return confirm(\'' . $txt['quickmod_confirm'] . '\');">' . $txt['delete'] . '</a>',
							'params' => [
								'timesheet_id' => false,
							],
						],
						'class' => 'centertext',
					],
					'sort' => [
						'default' => 'timesheet_id DESC',
						'reverse' => 'timesheet_id',
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
			$listOptions['additional_rows']['updated']['value'] .= $txt['TasksManager_booking_' . (!isset($_REQUEST['deleted']) ? (!isset($_REQUEST['added']) ? (!isset($_REQUEST['updated']) ? '' : 'updated') : 'added') : 'deleted')] . '</div>';
		}

		createList($listOptions);
	}

	/**
	 * Book::manage()
	 * 
	 * Create a log entry
	 * @return void
	 */
	public function manage()
	{
		global $context, $scripturl, $txt, $modSettings;

		// Page setup
		View::page_setup('booking', 'manage', 'booking_' . $_REQUEST['sa'], '?action=tasksmanager;area=booking;sa=' . $_REQUEST['sa'], 'settings');

		// Any tasks?
		if (empty($this->_tasks))
			fatal_lang_error('TasksManager_no_tasks', false);

		loadCSSFile('jquery-ui.datepicker.css', [], 'smf_datepicker');
		loadJavaScriptFile('jquery-ui.datepicker.min.js', ['defer' => true], 'smf_datepicker');
		loadJavaScriptFile('jquery.timepicker.min.js', ['defer' => true], 'smf_timepicker');
		loadJavaScriptFile('datepair.min.js', ['defer' => true], 'smf_datepair');
		addInlineJavaScript('
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
			'task_id' => [
				'label' => $txt['TasksManager_tasks'],
				'type' => 'select',
				'options' => View::itemSelect($this->_tasks, 'task_id', 'task_name'),
			],
			'time_worked_hours' => [
				'label' => $txt['TasksManager_booking_time_hours'],
				'type' => 'number',
			],
			'time_worked_minutes' => [
				'label' => $txt['TasksManager_booking_time_minutes'],
				'type' => 'number',
				'max' => 59,
			],
			'time_comments' => [
				'label' => $txt['TasksManager_booking_comments'],
				'type' => 'textarea',
			],
			'time_booked' => [
				'label' => $txt['TasksManager_booking_date'],
				'description' => $txt['TasksManager_booking_date_desc'],
				'type' => 'date',
			]
		];

		// Add the session
		$context['tasks_pp_settings'][$context['session_var']] = [
			'value' => $context['session_id'],
			'type' => 'hidden',
		];

		// Post URL
		$context['post_url'] = $scripturl . '?action=tasksmanager;area=booking;sa=save';
	}

	/**
	 * Book::save()
	 * 
	 * Add a log entry
	 * @return void
	 */
	public static function save()
	{
		global $smcFunc;

		checkSession();

		// Task name?
		if (empty($_REQUEST['task_id']) || !isset($_REQUEST['task_id']))
			fatal_lang_error('TasksManager_no_task', false);

		// Check time worked
		if (!isset($_REQUEST['time_worked_hours']) && !isset($_REQUEST['time_worked_minutes']))
			fatal_lang_error('TasksManager_no_time_worked', false);

		// Comments are limited to 80 characters
		if (isset($_REQUEST['time_comments']) && strlen($_REQUEST['time_comments']) > 80)
			fatal_lang_error('TasksManager_booking_comments_too_long', false);

		// Convert the date to unix
		if (isset($_REQUEST['time_booked']) && !empty($_REQUEST['time_booked']))
			$time_booked = strtotime($_REQUEST['time_booked']);
		else
			$time_booked = time();

		// Book the time
		$smcFunc['db_insert']('',
			'{db_prefix}taskspp_timesheet',
			[
				'task_id' => 'int',
				'ts_date' => 'int',
				'hours_worked' => 'int',
				'minutes_worked' => 'int',
				'time_comments' => 'string',
			],
			[
				(int) $_REQUEST['task_id'],
				(int) $time_booked,
				(int) !empty($_REQUEST['time_worked_hours']) ? ($_REQUEST['time_worked_hours'] > 255 ? 255 : $_REQUEST['time_worked_hours']) : 0,
				(int) !empty($_REQUEST['time_worked_minutes']) ? ($_REQUEST['time_worked_minutes'] > 59 ? 59 : $_REQUEST['time_worked_minutes']) : 0,
				!empty($_REQUEST['time_comments']) ? $smcFunc['htmlspecialchars']($_REQUEST['time_comments'], ENT_QUOTES) : '',
			],
			[]
		);

		// Redirect
		redirectexit('action=tasksmanager;area=booking;sa=log;added');
	}

	/**
	 * Book::getLog()
	 * 
	 * Get the booking log
	 * @param int $start The start of the list
	 * @param int $limit The limit of the list
	 * @param string $sort The sort order
	 * @param string $query Any additional queries
	 * @param array $values The values to be used in the query
	 * @return void
	 */
	public static function getLog($start, $limit, $sort, $query = null, $values = null)
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
				ts.timesheet_id, ts.task_id, ts.ts_date, ts.hours_worked, ts.minutes_worked, ts.time_comments, tk.task_name
			FROM {db_prefix}taskspp_timesheet AS ts
				LEFT JOIN {db_prefix}taskspp_tasks AS tk ON (tk.task_id = ts.task_id) ' . (!empty($query) ? 
				$query : '') . '
			ORDER BY {raw:sort}
			LIMIT {int:start}, {int:limit}',
			$data
		);

		$result = [];
		while ($row = $smcFunc['db_fetch_assoc']($request))
			$result[$row['timesheet_id']] = $row;

		$smcFunc['db_free_result']($request);

		return $result;
	}

	/**
	 * Book::countLog()
	 * 
	 * Get the booking log count
	 * @param string $query Any additional queries
	 * @param array $values The values to be used in the query
	 * @return int The total number of log entries
	 */
	public static function countLog($query = null, $values = null)
	{
		global $smcFunc;

		// Timesheet data
		$data = [];

		// Get the other values as well
		if (!empty($values) && is_array($values))
			$data = array_merge($data, $values);

		$request = $smcFunc['db_query']('', '
			SELECT COUNT(*)
			FROM {db_prefix}taskspp_timesheet ' . (!empty($query) ? 
			$query : ''),
			$data
		);
		list($rows) = $smcFunc['db_fetch_row']($request);
		$smcFunc['db_free_result']($request);

		return $rows;
	}

	/**
	 * Book::delete()
	 * 
	 * Delete a log entry from the bookings
	 * @return void
	 */
	public function delete()
	{
		global $smcFunc;

		// Can you manage tasks?
		isAllowedTo('tasksmanager_can_edit');

		// Check the id?
		if (!isset($_REQUEST['id']) || empty($_REQUEST['id']))
			fatal_lang_error('TasksManager_no_booking', false);

		// Sesh
		checkSession('get');

		// Delete the category
		$smcFunc['db_query']('','
			DELETE FROM {db_prefix}taskspp_timesheet
			WHERE timesheet_id = {int:id}',
			[
				'id' => (int) $_REQUEST['id'],
			]
		);

		// OUT!
		redirectexit('action=tasksmanager;area=booking;sa=log;deleted');
	}
}