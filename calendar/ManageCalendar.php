<?php
/**
 * The various aspects of configuration for the calendar.
 *
 * Wedge (http://wedge.org)
 * Copyright � 2010 Ren�-Gilles Deberdt, wedge.org
 * Portions are � 2011 Simple Machines.
 * License: http://wedge.org/license/
 */

if (!defined('WEDGE'))
	die('Hacking attempt...');

function calendarAdmin()
{
	global $admin_areas, $context, $txt;

	loadPluginLanguage('Wedge:Calendar', 'lang/ManageCalendar');

	$admin_areas['plugins']['areas']['managecalendar'] = array(
		'label' => $txt['manage_calendar'],
		'function' => 'ManageCalendar',
		'icon' => $context['plugins_url']['Wedge:Calendar'] . '/img/calendar.gif',
		'bigicon' => $context['plugins_url']['Wedge:Calendar'] . '/img/calendar.png',
		'permission' => array('admin_forum'),
		'subsections' => array(
			'holidays' => array($txt['manage_holidays']),
			'',
			'settings' => array($txt['calendar_settings']),
		),
	);
}

function calendarAdminSearch(&$settings_search)
{
	$settings_search['plugins'][] = array('ModifyCalendarSettings', 'area=managecalendar;sa=settings');
}

// The main controlling function doesn't have much to do... yet.
function ManageCalendar()
{
	global $context, $txt;

	isAllowedTo('admin_forum');

	// Default text.
	$context['explain_text'] = $txt['calendar_desc'];

	// Little short on the ground of functions here... but things can and maybe will change...
	$subActions = array(
		'editholiday' => 'EditHoliday',
		'holidays' => 'ModifyHolidays',
		'settings' => 'ModifyCalendarSettings'
	);

	$_REQUEST['sa'] = isset($_REQUEST['sa'], $subActions[$_REQUEST['sa']]) ? $_REQUEST['sa'] : 'holidays';

	// Set up the two tabs here...
	$context[$context['admin_menu_name']]['tab_data'] = array(
		'title' => $txt['manage_calendar'],
		'help' => 'calendar',
		'description' => $txt['calendar_settings_desc'],
		'tabs' => array(
			'holidays' => array(
				'description' => $txt['manage_holidays_desc'],
			),
			'settings' => array(
				'description' => $txt['calendar_settings_desc'],
			),
		),
	);

	$subActions[$_REQUEST['sa']]();
}

// The function that handles adding, and deleting holiday data
function ModifyHolidays()
{
	global $txt, $context, $settings;

	// Due to two buttons awkwardly inside the one form, we can end up here when we don't mean to be.
	if (isset($_REQUEST['add']))
	{
		$_REQUEST['sa'] = 'editholiday';
		unset($_REQUEST['delete']);
		$_REQUEST['title'] = '';
		return EditHoliday();
	}

	add_plugin_css_file('Wedge:Calendar', 'css/calendar', true);
	loadPluginSource('Wedge:Calendar', 'Subs-Calendar');
	loadPluginTemplate('Wedge:Calendar', 'ManageCalendar');
	loadPluginLanguage('Wedge:Calendar', 'lang/CalendarHolidays');

	// Submitting something...
	if (isset($_REQUEST['delete']) && !empty($_REQUEST['holiday']))
	{
		checkSession();

		foreach ($_REQUEST['holiday'] as $id => $value)
			$_REQUEST['holiday'][$id] = (int) $id;

		// Now the IDs are "safe" do the delete...
		removeHolidays($_REQUEST['holiday']);
	}

	$presets_to_show = array(
		'xmas', 'newyear', 'halloween', 'thanks', 'memorial', 'labor', 'dday', 'veteran', 'id4', '5may',
		'flag', 'category_parents', 'category_solstice', 'earth', 'un', 'pirate', 'cupid', 'stpat', 'april', 'groundhog',
	);
	call_hook('calendar_holidays_admin', array(&$presets_to_show));
	$presets_set = !empty($settings['cal_preset_holidays']) ? explode(',', $settings['cal_preset_holidays']) : array();
	$context['predefined_holidays'] = array_flip($presets_to_show);
	foreach ($context['predefined_holidays'] as $k => $v)
		$context['predefined_holidays'][$k] = in_array($k, $presets_set);

	if (isset($_REQUEST['preset_save']))
	{
		checkSession();

		if (empty($_REQUEST['preset']))
			$_REQUEST['preset'] = array();
		elseif (!is_array($_REQUEST['preset']))
			$_REQUEST['preset'] = (array) $_REQUEST['preset'];

		$new_hols = array();
		foreach ($context['predefined_holidays'] as $k => $v)
			if (!empty($_REQUEST['preset'][$k]))
			{
				$new_hols[] = $k;
				$context['predefined_holidays'][$k] = true;
			}
			else
				$context['predefined_holidays'][$k] = false;

		updateSettings(array('cal_preset_holidays' => implode(',', $new_hols)));
	}

	$listOptions = array(
		'id' => 'holiday_list',
		'title' => $txt['custom_holidays'],
		'items_per_page' => 20,
		'base_href' => SCRIPT . '?action=admin;area=managecalendar;sa=holidays',
		'default_sort_col' => 'name',
		'get_items' => array(
			'function' => 'list_getHolidays',
		),
		'get_count' => array(
			'function' => 'list_getNumHolidays',
		),
		'no_items_label' => $txt['holidays_no_entries'],
		'columns' => array(
			'name' => array(
				'header' => array(
					'value' => $txt['holidays_title'],
				),
				'data' => array(
					'sprintf' => array(
						'format' => '<a href="' . SCRIPT . '?action=admin;area=managecalendar;sa=editholiday;holiday=%1$d">%2$s</a>',
						'params' => array(
							'id_holiday' => false,
							'title' => false,
						),
					),
				),
				'sort' => array(
					'default' => 'title',
					'reverse' => 'title DESC',
				)
			),
			'date' => array(
				'header' => array(
					'value' => $txt['date'],
				),
				'data' => array(
					'function' => function ($rowData) {
						global $txt;

						// Recurring every year or just a single year?
						$year = $rowData['year'] == '0004' ? sprintf('(%1$s)', $txt['every_year']) : $rowData['year'];

						// Construct the date.
						return sprintf('%1$d %2$s %3$s', $rowData['day'], $txt['months'][(int) $rowData['month']], $year);
					},
					'class' => 'windowbg',
				),
				'sort' => array(
					'default' => 'event_date',
					'reverse' => 'event_date DESC',
				),
			),
			'check' => array(
				'header' => array(
					'value' => '<input type="checkbox" onclick="invertAll(this, this.form);">',
				),
				'data' => array(
					'sprintf' => array(
						'format' => '<input type="checkbox" name="holiday[%1$d]">',
						'params' => array(
							'id_holiday' => false,
						),
					),
					'style' => 'text-align: center',
				),
			),
		),
		'form' => array(
			'href' => SCRIPT . '?action=admin;area=managecalendar;sa=holidays',
		),
		'additional_rows' => array(
			array(
				'position' => 'below_table_data',
				'value' => '
					<input type="submit" name="add" value="' . $txt['holidays_add'] . '" class="new">
					<input type="submit" name="delete" value="' . $txt['quickmod_delete_selected'] . '" class="delete">',
				'style' => 'text-align: right;',
			),
		),
	);

	loadSource('Subs-List');
	createList($listOptions);

	//loadTemplate('ManageCalendar');
	$context['page_title'] = $txt['manage_holidays'];

	wetem::load('holidays');
}

// This function is used for adding/editing a specific holiday
function EditHoliday()
{
	global $txt, $context;

	loadPluginTemplate('Wedge:Calendar', 'ManageCalendar');

	$context['is_new'] = !isset($_REQUEST['holiday']);
	$context['page_title'] = $context['is_new'] ? $txt['holidays_add'] : $txt['holidays_edit'];
	wetem::load('edit_holiday');

	// Cast this for safety...
	if (isset($_REQUEST['holiday']))
		$_REQUEST['holiday'] = (int) $_REQUEST['holiday'];

	// Submitting?
	if (isset($_POST[$context['session_var']]) && (isset($_REQUEST['delete']) || $_REQUEST['title'] != ''))
	{
		checkSession();

		// Not too long good sir?
		$_REQUEST['title'] = westr::substr($_REQUEST['title'], 0, 60);
		$_REQUEST['holiday'] = isset($_REQUEST['holiday']) ? (int) $_REQUEST['holiday'] : 0;

		if (isset($_REQUEST['delete']))
			wesql::query('
				DELETE FROM {db_prefix}calendar_holidays
				WHERE id_holiday = {int:selected_holiday}',
				array(
					'selected_holiday' => $_REQUEST['holiday'],
				)
			);
		else
		{
			$date = strftime($_REQUEST['year'] <= 4 ? '0004-%m-%d' : '%Y-%m-%d', mktime(0, 0, 0, $_REQUEST['month'], $_REQUEST['day'], $_REQUEST['year']));
			if (isset($_REQUEST['edit']))
				wesql::query('
					UPDATE {db_prefix}calendar_holidays
					SET event_date = {date:holiday_date}, title = {string:holiday_title}
					WHERE id_holiday = {int:selected_holiday}',
					array(
						'holiday_date' => $date,
						'selected_holiday' => $_REQUEST['holiday'],
						'holiday_title' => $_REQUEST['title'],
					)
				);
			else
				wesql::insert('',
					'{db_prefix}calendar_holidays',
					array(
						'event_date' => 'date', 'title' => 'string-60',
					),
					array(
						$date, $_REQUEST['title'],
					),
					array('id_holiday')
				);
		}

		updateSettings(array(
			'calendar_updated' => time(),
		));

		redirectexit('action=admin;area=managecalendar;sa=holidays');
	}

	// Default states...
	if ($context['is_new'])
		$context['holiday'] = array(
			'id' => 0,
			'day' => date('d'),
			'month' => date('m'),
			'year' => '0000',
			'title' => ''
		);
	// If it's not new load the data.
	else
	{
		$request = wesql::query('
			SELECT id_holiday, YEAR(event_date) AS year, MONTH(event_date) AS month, DAYOFMONTH(event_date) AS day, title
			FROM {db_prefix}calendar_holidays
			WHERE id_holiday = {int:selected_holiday}
			LIMIT 1',
			array(
				'selected_holiday' => $_REQUEST['holiday'],
			)
		);
		while ($row = wesql::fetch_assoc($request))
			$context['holiday'] = array(
				'id' => $row['id_holiday'],
				'day' => $row['day'],
				'month' => $row['month'],
				'year' => $row['year'] <= 4 ? 0 : $row['year'],
				'title' => $row['title']
			);
		wesql::free_result($request);
	}

	// Last day for the drop down?
	$context['holiday']['last_day'] = (int) strftime('%d', mktime(0, 0, 0, $context['holiday']['month'] == 12 ? 1 : $context['holiday']['month'] + 1, 0, $context['holiday']['month'] == 12 ? $context['holiday']['year'] + 1 : $context['holiday']['year']));
}

function ModifyCalendarSettings($return_config = false)
{
	global $context, $txt;

	// Load the boards list.
	$boards = array('');
	$request = wesql::query('
		SELECT b.id_board, b.name AS board_name, c.name AS cat_name
		FROM {db_prefix}boards AS b
			LEFT JOIN {db_prefix}categories AS c ON (c.id_cat = b.id_cat)',
		array(
		)
	);
	while ($row = wesql::fetch_assoc($request))
		$boards[$row['id_board']] = $row['cat_name'] . ' - ' . $row['board_name'];
	wesql::free_result($request);

	// Look, all the calendar settings - of which there are many!
	$config_vars = array(
			// All the permissions:
			array('permissions', 'calendar_view'),
			array('permissions', 'calendar_post'),
			array('permissions', 'calendar_edit_own'),
			array('permissions', 'calendar_edit_any'),
		'',
			// How many days to show on board index, and where to display events etc?
			array('int', 'cal_days_for_index'),
			array('select', 'cal_showholidays', array(0 => $txt['setting_cal_show_never'], 1 => $txt['setting_cal_show_cal'], 3 => $txt['setting_cal_show_index'], 2 => $txt['setting_cal_show_all'])),
			array('select', 'cal_showevents', array(0 => $txt['setting_cal_show_never'], 1 => $txt['setting_cal_show_cal'], 3 => $txt['setting_cal_show_index'], 2 => $txt['setting_cal_show_all'])),
		'',
			// Linking events etc...
			array('select', 'cal_defaultboard', $boards),
			array('check', 'cal_daysaslink'),
			array('check', 'cal_allow_unlinked'),
			array('check', 'cal_showInTopic'),
		'',
			// Dates of calendar...
			array('int', 'cal_minyear'),
			array('int', 'cal_maxyear'),
		'',
			// Calendar spanning...
			array('check', 'cal_allowspan'),
			array('int', 'cal_maxspan'),
	);

	if ($return_config)
		return $config_vars;

	// Get the settings template fired up.
	loadSource('ManageServer');

	// Some important context stuff
	$context['page_title'] = $txt['calendar_settings'];
	wetem::load('show_settings');

	// Get the final touches in place.
	$context['post_url'] = SCRIPT . '?action=admin;area=managecalendar;save;sa=settings';
	$context['settings_title'] = $txt['calendar_settings'];

	// Saving the settings?
	if (isset($_GET['save']))
	{
		checkSession();
		saveDBSettings($config_vars);

		// Update the stats in case.
		updateSettings(array(
			'calendar_updated' => time(),
		));

		redirectexit('action=admin;area=managecalendar;sa=settings');
	}

	// Prepare the settings...
	prepareDBSettingContext($config_vars);
}

// If topics are moved between boards, we need to update that.
function moveCalendarTopics(&$topics, &$toBoard, &$isRecycleDest)
{
	wesql::query('
		UPDATE {db_prefix}calendar
		SET id_board = {int:id_board}
		WHERE id_topic IN ({array_int:topics})',
		array(
			'id_board' => $toBoard,
			'topics' => $topics,
		)
	);

	updateSettings(array(
		'calendar_updated' => time(),
	));
}

// If topics have been deleted, purge related calendar entries too.
function removeCalendarTopics(&$topics, &$decreasePostCount, &$ignoreRecycling)
{
	wesql::query('
		DELETE FROM {db_prefix}calendar
		WHERE id_topic IN ({array_int:topics})',
		array(
			'topics' => $topics,
		)
	);

	updateSettings(array(
		'calendar_updated' => time(),
	));
}

// If we're deleting a board, clean up after it.
function removeCalendarBoard(&$boards_to_remove)
{
	wesql::query('
		DELETE FROM {db_prefix}calendar
		WHERE id_board IN ({array_int:boards_to_remove})',
		array(
			'boards_to_remove' => $boards_to_remove,
		)
	);

	updateSettings(array(
		'calendar_updated' => time(),
	));
}

function repairBoards_calendar_tests(&$errorTests)
{
	loadPluginLanguage('Wedge:Calendar', 'lang/ManageCalendar');
	$errorTests['missing_calendar_topics'] = array(
		'substeps' => array(
			'step_size' => 1000,
			'step_max' => '
				SELECT MAX(id_topic)
				FROM {db_prefix}calendar'
		),
		'check_query' => '
			SELECT cal.id_topic, cal.id_event
			FROM {db_prefix}calendar AS cal
				LEFT JOIN {db_prefix}topics AS t ON (t.id_topic = cal.id_topic)
			WHERE cal.id_topic != 0
				AND cal.id_topic BETWEEN {STEP_LOW} AND {STEP_HIGH}
				AND t.id_topic IS NULL
			ORDER BY cal.id_topic',
		'fix_collect' => array(
			'index' => 'id_topic',
			'process' => function ($events) {
				wesql::query('
					UPDATE {db_prefix}calendar
					SET id_topic = 0, id_board = 0
					WHERE id_topic IN ({array_int:events})',
					array(
						'events' => $events
					)
				);
			},
		),
		'messages' => array('repair_missing_calendar_topics', 'id_event', 'id_topic'),
	);
}

function repairBoards_calendar_done()
{
	updateSettings(array(
		'calendar_updated' => time(),
	));
}

function mergeCalendarTopics(&$topics, &$id_topic, &$deleted_topics, &$target_board, &$first_msg, &$target_subject)
{
	wesql::query('
		UPDATE {db_prefix}calendar
		SET
			id_topic = {int:id_topic},
			id_board = {int:target_board}
		WHERE id_topic IN ({array_int:deleted_topics})',
		array(
			'deleted_topics' => $deleted_topics,
			'id_topic' => $id_topic,
			'target_board' => $target_board,
		)
	);
}

function calendarPermissions(&$permissionGroups, &$permissionList, &$leftPermissionGroups, &$hiddenPermissions, &$relabelPermissions)
{
	$leftPermissionGroups[] = 'calendar';

	$permissionGroups['membergroup']['simple'][] = 'post_calendar';
	$permissionGroups['membergroup']['classic'][] = 'calendar';
	$permissionList['membergroup'] += array(
		'calendar_view' => array(false, 'calendar', 'view_basic_info'),
		'calendar_post' => array(false, 'calendar', 'post_calendar'),
		'calendar_edit' => array(true, 'calendar', 'post_calendar', 'moderate_general'),
	);
}

function calendarMemberPrefs(&$config_vars, &$return_config)
{
	global $txt;

	loadPluginLanguage('Wedge:Calendar', 'lang/Calendar');

	$config_vars[] = array('select', 'calendar_start_day', array(
			0 => $txt['days'][0],
			1 => $txt['days'][1],
			6 => $txt['days'][6],
		), 'display' => 'looklayout');
}
?>