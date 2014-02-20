<?php

if (!defined('WEDGE'))
	die('Hacking attempt...');

function topicSoldDisplay()
{
	global $context, $txt, $board, $topic, $topicinfo, $settings;

	loadPluginLanguage('Arantor:ItemSold', 'lang/TopicSold-Display');

	// Check if the current board is in the list of boards practising Topic Sold, leave if not.
	$board_list = !empty($settings['topicsold_boards']) ? unserialize($settings['topicsold_boards']) : array();
	if (!in_array($board, $board_list))
		return;

	$request = wesql::query('
		SELECT id_topic, sold, ts.id_member, mem.real_name
		FROM {db_prefix}topicsold AS ts
			LEFT JOIN {db_prefix}members AS mem ON (ts.id_member = mem.id_member)
		WHERE id_topic = {int:topic}',
		array(
			'topic' => $topic,
		)
	);
	if (wesql::num_rows($request) != 0)
	{
		$context['topic_sold'] = wesql::fetch_assoc($request);
		// Generate the right message
		if (empty($context['topic_sold']['id_member']))
			$context['topic_sold']['message'] = sprintf($txt['topic_was_sold_missing_author'], on_timeformat($context['topic_sold']['sold']));
		elseif ($topicinfo['id_member_started'] == $context['topic_sold']['id_member'])
			$context['topic_sold']['message'] = sprintf($txt['topic_was_sold_author'], on_timeformat($context['topic_sold']['sold']));
		else
			$context['topic_sold']['message'] = sprintf($txt['topic_was_sold_non_author'], '<a href="<URL>?action=profile;u=' . $context['topic_sold']['id_member'] . '">' . $context['topic_sold']['real_name'] . '</a>', on_timeformat($context['topic_sold']['sold']));

		wetem::before('report_success', 'topic_sold_warning');

		add_css('
	.sold { color: ' . $settings['topicsold_fg'] . '; background-color: ' . $settings['topicsold_bg1'] . ' }');
	}

	if (allowedTo('topicsold_any') || (allowedTo('topicsold_own') && $topicinfo['id_member_started'] == MID))
	{
		$context['can_sold'] = true;
		$nav = array(
			'marksold' => array(
				'test' => 'can_sold',
				'text' => !empty($context['topic_sold']) ? 'topic_mark_unsold' : 'topic_mark_sold',
				'url' => '<URL>?action=marksold;topic=' . $context['current_topic'],
			),
		);
		add_css('
	#modbuttons a.marksold { background-image: url(' . $context['plugins_url']['Arantor:ItemSold'] . '/img/' . (!empty($context['topic_sold']) ? 'un' : '') . 'sold.gif) }');

		$context['nav_buttons']['mod'] = array_insert($context['nav_buttons']['mod'], 'move', $nav, false);
	}
}

// Yes, yes, I know this is naughty. But loading an extra file for this silly little function? REALLY?
function template_topic_sold_warning()
{
	global $context;

	echo '
		<div class="description sold">
			<img src="', $context['plugins_url']['Arantor:ItemSold'], '/img/tick.png"> ', $context['topic_sold']['message'], '
		</div>';
}

?>