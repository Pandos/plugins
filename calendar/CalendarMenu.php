<?php
/**
 * Per-page processing, e.g. adding the menu item and potentially banned permissions too.
 *
 * Wedge (http://wedge.org)
 * Copyright � 2010 Ren�-Gilles Deberdt, wedge.org
 * Portions are � 2011 Simple Machines.
 * License: http://wedge.org/license/
 */

if (!defined('WEDGE'))
	die('Hacking attempt...');

function calendarMenu(&$items)
{
	global $context, $txt;
	loadPluginLanguage('Wedge:Calendar', 'lang/Calendar');

	$context['allow_calendar'] = allowedTo('calendar_view');

	$menu_item = array(
		'calendar' => array(
			'title' => $txt['calendar'],
			'href' => SCRIPT . '?action=calendar',
			'show' => $context['allow_calendar'],
			'sub_items' => array(
				'view' => array(
					'title' => $txt['calendar_menu'],
					'href' => SCRIPT . '?action=calendar',
					'show' => allowedTo('calendar_post'),
				),
				'post' => array(
					'title' => $txt['calendar_post_event'],
					'href' => SCRIPT . '?action=calendar;sa=post',
					'show' => allowedTo('calendar_post'),
				),
			),
		),
	);

	$items = array_insert($items, 'media', $menu_item, false);
	add_css('#m_calendar { float: left; width: 16px; height: 16px; padding: 0; margin: 4px 4px 0 2px; background: url(' . $context['plugins_url']['Wedge:Calendar'] . '/img/calendar.gif) 0 2px no-repeat; }');
}

// Remove these permissions if post-banned.
function bannedCalendar(&$denied_permissions)
{
	$denied_permissions += array('calendar_post', 'calendar_edit_own', 'calendar_edit_any');
}
