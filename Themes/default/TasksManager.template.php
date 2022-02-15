<?php

function template_list()
{
	echo '
	<div class="windowbg">
		We are adding a list here, or something similar
	</div>';
}

function template_main_above() {}

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

function template_project_manage()
{
	global $txt, $context;

	echo '
	<div class="cat_bar">
		<h3 class="catbg">
			', $txt['TasksManager_projects'], '
		</h3>
	</div>
	<div class="windowbg">
		<form>
				yayayayaya
		</form>
	</div>';
}

function template_manage()
{
	global $txt, $context;

	echo '
	<div class="windowbg">
		<form action="', $context['post_url'] , '" method="post">
			<dl class="settings">';

		// Settings
		foreach ($context['tasks_pp_settings'] as $name => $setting)
		{
			if (!empty($setting['label']))
				echo '
				<dt>
					<label for="tasks_', $name, '">', $setting['label'], '</label>
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

				foreach ($setting['options'] as $option => $value)
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

			// Time
			elseif ($setting['type']  == 'time')
			{
				echo '
					<div class="event_options_left" id="event_time_input">
						<input type="text" name="', $name, '" id="tasks_', $name, '" maxlength="10" value="', $setting['value'], '" tabindex="', $context['tabindex']++, '" class="date_input start" data-type="date">
					</div>';
			}
				
			// Text, number, etc
			else
				echo '
					<input type="', $setting['type'], '" id="tasks_', $name, '" name="', $name, '" value="', $setting['value'], '"', (!isset($setting['size']) ? '' : 'size="' . $setting['size'] . '"'), ' />';

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
		</form>
	</div>';
}