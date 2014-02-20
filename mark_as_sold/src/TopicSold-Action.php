<?php

if (!defined('WEDGE'))
	die('Hacking attempt...');

function topicSoldAction()
{
	global $board, $topic, $settings;

	if (empty($topic) || empty($board))
		redirectexit();

	$board_list = !empty($settings['topicsold_boards']) ? unserialize($settings['topicsold_boards']) : array();
	if (!in_array($board, $board_list))
		redirectexit();

	// So, we need to know whether it is sold. Load.php will already have identified whether we can see the topic.
	$request = wesql::query('
		SELECT t.id_member_started, ts.sold
		FROM {db_prefix}topics AS t
			LEFT JOIN {db_prefix}topicsold AS ts ON (t.id_topic = ts.id_topic)
		WHERE t.id_topic = {int:topic}',
		array(
			'topic' => $topic,
		)
	);
	list ($topic_starter, $sold) = wesql::fetch_row($request);
	wesql::free_result($request);

	// Can we mark this sold?
	// !!! Nicer error
	if (!allowedTo('topicsold_any') && ($topic_starter != MID || !allowedTo('topicsold_own')))
		fatal_lang_error('no_access');

	if (empty($sold))
	{
		wesql::insert('replace',
			'{db_prefix}topicsold',
			array(
				'id_topic' => 'int', 'sold' => 'int', 'id_member' => 'int',
			),
			array(
				$topic, time(), MID,
			),
			array('id_topic')
		);
		logAction('sold', array('topic' => $topic), 'moderate');
		redirectexit('topic=' . $topic . '.0');
	}
	else
	{
		wesql::query('
			DELETE FROM {db_prefix}topicsold
			WHERE id_topic = {int:topic}',
			array(
				'topic' => $topic,
			)
		);
		logAction('unsold', array('topic' => $topic), 'moderate');
		redirectexit('topic=' . $topic . '.0');
	}
}

?>