<?php
/**
 * Provides functionality for showing the calendar items in the topic display view.
 *
 * Wedge (http://wedge.org)
 * Copyright � 2010 Ren�-Gilles Deberdt, wedge.org
 * Portions are � 2011 Simple Machines.
 * License: http://wedge.org/license/
 */

if (!defined('WEDGE'))
	die('Hacking attempt...');

function calendar_display()
{
	global $context, $settings, $topic, $topicinfo;

	// Permissions
	$context['calendar_post'] = allowedTo('calendar_post');

	// If we want to show event information in the topic, prepare the data.
	if (allowedTo('calendar_view') && !empty($settings['cal_showInTopic']))
	{
		// First, try create a better time format, ignoring the "time" elements.
		if (preg_match('~%[AaBbCcDdeGghjmuYy](?:[^%]*%[AaBbCcDdeGghjmuYy])*~', we::$user['time_format'], $matches) == 0 || empty($matches[0]))
			$date_string = we::$user['time_format'];
		else
			$date_string = $matches[0];

		// Any calendar information for this topic?
		$request = wesql::query('
			SELECT cal.id_event, cal.start_date, cal.end_date, cal.title, cal.id_member, mem.real_name
			FROM {db_prefix}calendar AS cal
				LEFT JOIN {db_prefix}members AS mem ON (mem.id_member = cal.id_member)
			WHERE cal.id_topic = {int:current_topic}
			ORDER BY start_date',
			array(
				'current_topic' => $topic,
			)
		);
		$context['linked_calendar_events'] = array();
		while ($row = wesql::fetch_assoc($request))
		{
			// Prepare the dates for being formatted.
			$start_date = sscanf($row['start_date'], '%04d-%02d-%02d');
			$start_date = mktime(12, 0, 0, $start_date[1], $start_date[2], $start_date[0]);
			$end_date = sscanf($row['end_date'], '%04d-%02d-%02d');
			$end_date = mktime(12, 0, 0, $end_date[1], $end_date[2], $end_date[0]);

			$context['linked_calendar_events'][] = array(
				'id' => $row['id_event'],
				'title' => $row['title'],
				'can_edit' => allowedTo('calendar_edit_any') || ($row['id_member'] == MID && allowedTo('calendar_edit_own')),
				'modify_href' => '<URL>?action=post;msg=' . $topicinfo['id_first_msg'] . ';topic=' . $topic . '.0;calendar;eventid=' . $row['id_event'] . ';' . $context['session_query'],
				'start_date' => timeformat($start_date, $date_string, 'none'),
				'start_timestamp' => $start_date,
				'end_date' => timeformat($end_date, $date_string, 'none'),
				'end_timestamp' => $end_date,
				'is_last' => false
			);
		}
		wesql::free_result($request);

		if (!empty($context['linked_calendar_events']))
		{
			$context['linked_calendar_events'][count($context['linked_calendar_events']) - 1]['is_last'] = true;
			loadPluginLanguage('Wedge:Calendar', 'lang/Calendar');
			loadPluginTemplate('Wedge:Calendar', 'CalendarIntegration');
			wetem::after('topic_poll', 'linked_calendar');
		}
	}

	// Add it to the mod-level navigation buttons.
	$context['nav_buttons']['mod']['calendar'] = array(
		'test' => 'calendar_post',
		'text' => 'calendar_link',
		'url' => '<URL>?action=post;calendar;msg=' . $context['topic_first_message'] . ';topic=' . $context['current_topic'] . '.0',
	);
}

?>