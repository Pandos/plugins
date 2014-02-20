<?php

if (!defined('WEDGE'))
	die('Hacking attempt...');

function topicSoldIllegalGuestPerms()
{
	global $context;

	$context['non_guest_permissions'][] = 'topicsold_own';
	$context['non_guest_permissions'][] = 'topicsold_any';
}
?>