<?php
/**
 * @file
 * Class to implement e107 cron handler.
 */

if(!defined('e107_INIT'))
{
	exit;
}


/**
 * Class nodejs_cron.
 */
class nodejs_cron
{

	function config()
	{
		$cron = array();

		$cron[] = array(
			'name'        => 'Cleanup Node.js session table.',
			'function'    => 'nodejs_cleanup_session_table',
			'category'    => 'user',
			'description' => 'Cleanup session table (6 hours with no activity).',
		);

		return $cron;
	}

	/**
	 * Cleanup session table (6 hours with no activity).
	 */
	public function nodejs_cleanup_session_table()
	{
		$sql = e107::getDb('nodejssessions');
		$sql->delete("nodejs_sessions", "timestamp <= (UNIX_TIMESTAMP() - 6*60*60)");
	}

}
