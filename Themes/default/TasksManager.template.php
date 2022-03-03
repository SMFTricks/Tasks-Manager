<?php

/**
 * @package Tasks Manager
 * @version 1.1
 * @author Diego Andrés <diegoandres_cortes@outlook.com>
 * @copyright Copyright (c) 2022, SMF Tricks
 * @license MIT
 */

function template_main_above() {}

/**
 * Wraps the tasks content with a little message at the end
 */
function template_main_below()
{
	global $context;

	echo '
		<br>
		<div style="text-align: center;">
			<span class="smalltext">
				', $context['tasksmanager']['copyright'], '
			</span>
		</div>';
}

/**
 * It creates settings for different areas of the tasks when needed
 */
function template_manage()
{
	global $txt, $context;

	echo '
	<div class="windowbg">';

	// Settings
	if (!empty($context['tasks_pp_settings']))
	{
		echo '
		<form action="', $context['post_url'] , '" method="post">
			<dl class="settings">';

		// Display the settings
		foreach ($context['tasks_pp_settings'] as $name => $setting)
		{
			if (!empty($setting['label']))
				echo '
				<dt>
					<label for="tasks_', $name, '">', $setting['label'], '</label>
					', (!empty($setting['description']) ? '<br><span class="smalltext">' . $setting['description'] . '</span>' : ''), '
				</dt>
				<dd>';
			else
				echo '
			</dl>';

			// Select
			if ($setting['type'] == 'select')
			{
					echo '
					<select name="', $name, '" id="tasks_', $name, '">';

				foreach ($setting['options'] as $value => $option)
					echo '
						<option value="', $value, '"', (isset($setting['value']) && $setting['value'] == $value ? ' selected="selected"' : ''), '>', $option, '</option>';

					echo '
					</select>';
			}

			// Textarea
			elseif ($setting['type'] == 'textarea')
			{
					echo '
					<textarea name="', $name, '" id="tasks_', $name, '">', isset($setting['value']) ? $setting['value'] : '', '</textarea>';
			}

			// Date picker using SMF's
			elseif ($setting['type']  == 'date')
			{
				echo '
					<div class="event_options_left" id="event_time_input">
						<input type="text" name="', $name, '" id="tasks_', $name, '" maxlength="10" value="', isset($setting['value']) ? $setting['value'] : '', '" tabindex="', $context['tabindex']++, '" class="date_input start" data-type="date" autocomplete="off">
					</div>';
			}
				
			// Text, number, etc
			else
				echo '
					<input type="', $setting['type'], '" id="tasks_', $name, '" name="', $name, '" value="', isset($setting['value']) ? $setting['value'] : '', '"', (!isset($setting['size']) ? '' : ' size="' . $setting['size'] . '"'), (!isset($setting['max']) ? '' : ' max="' . $setting['max'] . '"'), ' />';

			if (!empty($setting['label']))
				echo '
				</dd>';
			else
				echo '
			<dl>';
		}

		echo '
			</dl>
			<button class="button floatright">', $txt['save'], '</button>
		</form>';
	}

	else
		echo $txt['TasksManager_no_settings'];

	echo '
	</div>';
}

/**
 * The subtemplate for assigning a topic to a task
 */
function template_add_topic_above()
{
	global $context, $scripturl, $txt;

	echo '
		<div class="noticebox">
			', $txt['TasksManager_adding_topic_task'], '
			<a href="', $scripturl, '?topic=', $context['task_topic_information']['id_topic'] . '.0">
				', $context['task_topic_information']['subject'], '
			</a>
		</div>';
}

function template_add_topic_below() {}

/**
 * The layers for adding the filters
 */
function template_list_selector_above()
{
	global $context, $txt;

	// Form URL?
	if (empty($context['form_url']))
		return;

	echo '
		<form action="', $context['form_url'], '" method="post" class="floatright" style="margin: 0 auto 5px">
			', $txt['TasksManager_tp_filter'], ': ';

		// Categories
		if (!empty($context['tasks_category_list']))
		{
			echo '
			<select name="category" onchange="submit();">
				<option value="-1"'. (!isset($_REQUEST['category']) || $_REQUEST['category'] == -1 ? ' selected="selected"' : ''). '>'. $txt['TasksManager_categories_all']. '</option>
				<option value="0"'. (isset($_REQUEST['category']) && $_REQUEST['category'] == 0 ? ' selected="selected"' : ''). '>'. $txt['TasksManager_projects_no_category']. '</option>
				<optgroup label="'. $txt['TasksManager_categories']. '">';

			foreach ($context['tasks_category_list'] as $category)
				echo '
					<option value="'. $category['category_id'] .'"'. (isset($_REQUEST['category']) && $_REQUEST['category'] == $category['category_id'] ? ' selected="selected"' : ''). '>'. $category['category_name']. '</option>';

			echo '
				</optgroup>
			</select>';
		}

		// Status
		if (!empty($context['tasks_status_list']))
		{
			echo '
			<select name="status" onchange="submit();">
				<option value="-1"'. (!isset($_REQUEST['status']) || $_REQUEST['status'] == -1 ? ' selected="selected"' : ''). '>'. $txt['TasksManager_statuses_all']. '</option>
				<option value="0"'. (isset($_REQUEST['status']) && $_REQUEST['status'] == 0 ? ' selected="selected"' : ''). '>'. $txt['TasksManager_projects_no_status']. '</option>
					<optgroup label="'. $txt['TasksManager_status']. '">';

			foreach ($context['tasks_status_list'] as $status)
				echo '
					<option value="', $status['status_id'], '"', (isset($_REQUEST['status']) && $_REQUEST['status'] == $status['status_id'] ? ' selected="selected"' : ''), '>', $status['status_name'], '</option>';

			echo '
				</optgroup>
			</select>';
		}

		// Types
		if (!empty($context['tasks_type_list']))
		{
			echo '
			<select name="type" onchange="submit();">
				<option value="-1"'. (!isset($_REQUEST['type']) || $_REQUEST['type'] == -1 ? ' selected="selected"' : ''). '>'. $txt['TasksManager_types_all']. '</option>
				<option value="0"'. (isset($_REQUEST['type']) && $_REQUEST['type'] == 0 ? ' selected="selected"' : ''). '>'. $txt['TasksManager_projects_no_type']. '</option>
					<optgroup label="'. $txt['TasksManager_types']. '">';

			foreach ($context['tasks_type_list'] as $type)
				echo '
						<option value="', $type['type_id'], '"', (isset($_REQUEST['type']) && $_REQUEST['type'] == $type['type_id'] ? ' selected="selected"' : ''), '>', $type['type_name'], '</option>';

			echo '
				</optgroup>
			</select>';
		}

		// Projects
		if (!empty($context['tasks_projects_list']))
		{
			echo '
			<select name="project" onchange="submit();">
				<option value="-1"'. (!isset($_REQUEST['project']) || $_REQUEST['project'] == -1 ? ' selected="selected"' : ''). '>'. $txt['TasksManager_projects_all']. '</option>
				<option value="0"'. (isset($_REQUEST['project']) && $_REQUEST['project'] == 0 ? ' selected="selected"' : ''). '>'. $txt['TasksManager_tasks_no_project']. '</option>
					<optgroup label="'. $txt['TasksManager_projects']. '">';

			foreach ($context['tasks_projects_list'] as $project)
				echo '
						<option value="', $project['project_id'], '"'. (isset($_REQUEST['project']) && $_REQUEST['project'] == $project['project_id'] ? ' selected="selected"' : ''). '>', $project['project_title'], '</option>';

			echo  '
				</optgroup>
			</select>';
		}

		// Tasks
		if (!empty($context['tasks_tasks_list']))
		{
			echo '
			<select name="task" onchange="submit();">
				<option value="-1"'. (!isset($_REQUEST['task']) || $_REQUEST['task'] == -1 ? ' selected="selected"' : ''). '>'. $txt['TasksManager_tasks_all']. '</option>
				<optgroup label="'. $txt['TasksManager_tasks']. '">';

			foreach ($context['tasks_tasks_list'] as $task)
				echo '
					<option value="', $task['task_id'], '">', $task['task_name'], '</option>';

			echo  '
				</optgroup>
			</select>';
		}

		echo '
			<button class="button floatright">', $txt['go'], '</button>
		</form>
		<br class="clear" />';
}

function template_list_selector_below() {}