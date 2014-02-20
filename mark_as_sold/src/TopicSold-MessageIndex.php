<?php

if (!defined('WEDGE'))
	die('Hacking attempt...');

function topicSoldMessageIndex()
{
	global $context, $board_info, $settings;

	loadPluginLanguage('Arantor:ItemSold', 'lang/TopicSold-MessageIndex');

	// Check if the current board is in the list of boards practising Topic Sold, leave if not.
	$board_list = !empty($settings['topicsold_boards']) ? unserialize($settings['topicsold_boards']) : array();
	if (!in_array($board_info['id'], $board_list))
		return;

	if (empty($context['topics']))
		return;

	$topic_ids = array_keys($context['topics']);
	$request = wesql::query('
		SELECT id_topic
		FROM {db_prefix}topicsold
		WHERE id_topic IN ({array_int:topics})',
		array(
			'topics' => $topic_ids,
		)
	);
	while (list ($id) = wesql::fetch_row($request))
	{
		$context['topics'][$id]['style'] .= ' sold';
		$context['topics'][$id]['first_post']['icon_url'] = $context['plugins_url']['Arantor:ItemSold'] . '/img/tick.png';
	}

	if (wesql::num_rows($request) > 0 && !empty($settings['topicsold_bg1']) && !empty($settings['topicsold_bg2']) && !empty($settings['topicsold_fg']))
		add_css('
	.sold { color: ' . $settings['topicsold_fg'] . ' } .windowbg.sold { background-color: ' . $settings['topicsold_bg1'] . ' } .windowbg2.sold { background-color: ' . $settings['topicsold_bg2'] . ' }');
}

// Since the usual case for this function is message index, save something by putting this here.
function topicSoldQuickModeration(&$quickmod)
{
	global $txt, $board, $board_info, $settings;

	loadPluginLanguage('Arantor:ItemSold', 'lang/TopicSold-MessageIndex');

	$board_list = !empty($settings['topicsold_boards']) ? unserialize($settings['topicsold_boards']) : array();
	if (empty($board_list))
		return;

	// Do permission test for 'any' in this board (or for multiple boards if it is search)
	if (!empty($board))
	{
		if ((!allowedTo('topicsold_any') && !allowedTo('topicsold_own')) || !in_array($board_info['id'], $board_list))
			return;
		$can = true;
	}
	else
	{
		$boards_can = boardsAllowedTo(array('topicsold_any', 'topicsold_own'));
		if (!in_array(0, $boards_can['topicsold_any']))
		{
			$can = false;
			foreach ($boards_can as $perm => $boards)
			{
				$boards_can[$perm] = array_intersect($boards_can[$perm], $board_list);
				if (!empty($boards_can[$perm]))
					$can = true;
			}
		}
		else
			$can = true;
	}

	if ($can)
		$quickmod['marksold'] = $txt['quick_mod_marksold'];
}

?>