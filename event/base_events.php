<?php
/**
 *
 * @package phpBB.de pastebin
 * @copyright (c) 2015 phpBB.de, gn#36
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace phpbbde\denyreply\event;

/**
 * @ignore
 */
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event listener
 */
class base_events implements EventSubscriberInterface
{
	static public function getSubscribedEvents()
	{
		return array(
			'core.viewtopic_modify_page_title' 	=> 'viewtopic_remove_button',
			'core.modify_posting_auth'			=> 'posting_deny',
		);
	}

	/** @var \phpbb\auth\auth */
	protected $auth;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\user */
	protected $user;

	/** @var int */
	protected $min_wait_time;

	/**
	 * Constructor
	 *
	 * @param \phpbb\auth\auth $auth
	 * @param \phpbb\db\driver\driver_interface $db
	 * @param \phpbb\template\template $template
	 * @param \phpbb\controller\helper $helper
	 * @param \phpbb\user $user
	 * @param unknown $phpbb_root_path
	 * @param unknown $php_ext
	 */
	public function __construct(\phpbb\auth\auth $auth,\phpbb\db\driver\driver_interface $db, \phpbb\template\template $template, \phpbb\user $user, $min_wait_time)
	{
		$this->template = $template;
		$this->user = $user;
		$this->db = $db;
		$this->min_wait_time = $min_wait_time;
		$this->auth = $auth;
	}

	public function viewtopic_remove_button($event)
	{
		if($this->auth->acl_get('m_', $event['forum_id']))
		{
			return;
		}

		if($event['topic_data']['topic_last_poster_id'] == $this->user->data['user_id'] && $event['topic_data']['topic_last_post_time'] + $this->min_wait_time > time())
		{
			$this->template->assign_vars(array(
				'S_DISPLAY_REPLY_INFO' 	=> false,
				'S_QUICK_REPLY' 		=> false,
			));
		}
	}

	public function posting_deny($event)
	{
		if($this->auth->acl_get('m_', $event['forum_id']))
		{
			return;
		}

		if($event['mode'] == 'reply')
		{
			// Since this is not provided by the event even though it is there, we will need to fetch it
			$sql = 'SELECT topic_last_poster_id, topic_last_post_time FROM ' . TOPICS_TABLE . ' WHERE topic_id = ' . $event['topic_id'];
			$result = $this->db->sql_query($sql);
			$row = $this->db->sql_fetchrow($result);
			$this->db->sql_freeresult($result);

			if($this->user->data['user_id'] == $row['topic_last_poster_id'] && $row['topic_last_post_time'] + $this->min_wait_time < time())
			{
				$event['is_authed'] = false;
			}
		}
	}
}
