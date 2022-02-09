<?php

/**
 * @package Tasks Manager
 * @version 1.0
 * @author Diego Andrés <diegoandres_cortes@outlook.com>
 * @copyright Copyright (c) 2022, SMF Tricks
 * @license MIT
 */

	if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('SMF'))
		require_once(dirname(__FILE__) . '/SSI.php');

	elseif (!defined('SMF'))
		exit('<b>Error:</b> Cannot install - please verify you put this in the same place as SMF\'s index.php.');

	global $smcFunc, $context;

	db_extend('packages');

	if (empty($context['uninstalling']))
	{
		// Projects
		$tables[] = [
			'table_name' => '{db_prefix}taskspp_projects',
			'columns' => [
				[
					'name' => 'project_id',
					'type' => 'mediumint',
					'size' => 8,
					'auto' => true,
					'unsigned' => true,
					'not_null' => true,
				],
				[
					'name' => 'project_title',
					'type' => 'varchar',
					'size' => 255,
				],
				[
					'name' => 'project_picture',
					'type' => 'varchar',
					'size' => 255,
				],
				[
					'name' => 'view_type',
					'type' => 'tinyint',
					'size' => 2,
					'unsigned' => true,
					'not_null' => true,
					'default' => 0,
				],
				[
					'name' => 'category_id',
					'type' => 'mediumint',
					'size' => 8,
					'unsigned' => true,
					'not_null' => true,
					'default' => 0,
				],
				[
					'name' => 'start_date',
					'type' => 'date',
				],
				[
					'name' => 'end_date',
					'type' => 'date',
				],
				[
					'name' => 'description',
					'type' => 'text',
				],
				[
					'name' => 'related_items',
					'type' => 'text',
				],
				[
					'name' => 'type_id',
					'type' => 'mediumint',
					'size' => 8,
					'unsigned' => true,
					'not_null' => true,
					'default' => 0,
				],
				[
					'name' => 'status_id',
					'type' => 'mediumint',
					'size' => 8,
					'unsigned' => true,
					'not_null' => true,
					'default' => 0,
				]
			],
			'indexes' => [
				[
					'type' => 'primary',
					'columns' => ['project_id']
				],
				[
					'type' => 'index',
					'columns' => ['status_id', 'category_id', 'type_id']
				],
			],
			'if_exists' => 'ignore',
			'error' => 'fatal',
			'parameters' => [],
		];

		// Tasks
		$tables[] = [
			'table_name' => '{db_prefix}taskspp_tasks',
			'columns' => [
				[
					'name' => 'task_id',
					'type' => 'mediumint',
					'size' => 8,
					'auto' => true,
					'unsigned' => true,
					'not_null' => true,
				],
				[
					'name' => 'project_id',
					'type' => 'mediumint',
					'size' => 8,
					'unsigned' => true,
					'not_null' => true,
					'default' => 0,
				],
				[
					'name' => 'task_cat_id',
					'type' => 'mediumint',
					'size' => 8,
					'unsigned' => true,
					'not_null' => true,
					'default' => 0,
				],
				[
					'name' => 'task_name',
					'type' => 'varchar',
					'size' => 255,
				],
				[
					'name' => 'task_desc',
					'type' => 'text',
				],
				[
					'name' => 'start_date',
					'type' => 'date',
				],
				[
					'name'  => 'end_date',
					'type'  => 'date',
				],
				[
					'name' => 'estimated_hrs',
					'type'  => 'time',
				],
				[
					'name' => 'task_status_id',
					'type' => 'mediumint',
					'size' => 8,
					'unsigned' => true,
					'not_null' => true,
					'default' => 0,
				],
			],
			'indexes' => [
				[
					'type' => 'primary',
					'columns' => ['task_id']
				],
				[
					'type' => 'index',
					'columns' => ['task_cat_id', 'task_status_id']
				],
			],
			'if_exists' => 'ignore',
			'error' => 'fatal',
			'parameters' => [],
		];

		// Project Types
		$tables[] = [
			'table_name' => '{db_prefix}taskspp_project_types',
			'columns' => [
				[
					'name' => 'type_id',
					'type' => 'mediumint',
					'size' => 8,
					'auto' => true,
					'unsigned' => true,
					'not_null' => true,
				],
				[
					'name' => 'type_name',
					'type' => 'varchar',
					'size' => 80,
				],
			],
			'indexes' => [
				[
					'type' => 'primary',
					'columns' => ['type_id']
				],
			],
			'if_exists' => 'ignore',
			'error' => 'fatal',
			'parameters' => [],
		];

		// Project Categories
		$tables[] = [
			'table_name' => '{db_prefix}taskspp_project_categories',
			'columns' => [
				[
					'name' => 'category_id',
					'type' => 'mediumint',
					'size' => 8,
					'auto' => true,
					'unsigned' => true,
					'not_null' => true,
				],
				[
					'name' => 'category_name',
					'type' => 'varchar',
					'size' => 80,
				],
			],
			'indexes' => [
				[
					'type' => 'primary',
					'columns' => ['category_id']
				],
			],
			'if_exists' => 'ignore',
			'error' => 'fatal',
			'parameters' => [],
		];

		// Project Status
		$tables[] = [
			'table_name' => '{db_prefix}taskspp_project_status',
			'columns' => [
				[
					'name' => 'status_id',
					'type' => 'mediumint',
					'size' => 8,
					'auto' => true,
					'unsigned' => true,
					'not_null' => true,
				],
				[
					'name' => 'status_name',
					'type' => 'varchar',
					'size' => 50,
				],
			],
			'indexes' => [
				[
					'type' => 'primary',
					'columns' => ['status_id']
				],
			],
			'if_exists' => 'ignore',
			'error' => 'fatal',
			'parameters' => [],
		];

		// Task Categories
		$tables[] = [
			'table_name' => '{db_prefix}taskspp_task_categories',
			'columns' => [
				[
					'name' => 'task_cat_id',
					'type' => 'mediumint',
					'size' => 8,
					'auto' => true,
					'unsigned' => true,
					'not_null' => true,
				],
				[
					'name' => 'category_name',
					'type' => 'varchar',
					'size' => 80,
				],
			],
			'indexes' => [
				[
					'type' => 'primary',
					'columns' => ['task_cat_id']
				],
			],
			'if_exists' => 'ignore',
			'error' => 'fatal',
			'parameters' => [],
		];

		// Timesheet
		$tables[] = [
			'table_name' => '{db_prefix}taskspp_timesheet',
			'columns' => [
				[
					'name' => 'timesheet_id',
					'type' => 'mediumint',
					'size' => 8,
					'auto' => true,
					'unsigned' => true,
					'not_null' => true,
				],
				[
					'name' => 'task_id',
					'type' => 'mediumint',
					'size' => 8,
					'unsigned' => true,
					'not_null' => true,
					'default' => 0,
				],
				[
					'name' => 'ts_date',
					'type' => 'date',
				],
				[
					'name' => 'time_worked',
					'type' => 'time',
				],
			],
			'indexes' => [
				[
					'type' => 'primary',
					'columns' => ['timesheet_id']
				],
			],
			'if_exists' => 'ignore',
			'error' => 'fatal',
			'parameters' => [],
		];

		// Installing
		foreach ($tables as $table)
		$smcFunc['db_create_table']($table['table_name'], $table['columns'], $table['indexes'], $table['parameters'], $table['if_exists'], $table['error']);
	}