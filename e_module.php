<?php
/**
 * @file
 * This file is loaded every time the core of e107 is included. ie. Wherever
 * you see require_once("class2.php") in a script. It allows a developer to
 * modify or define constants, parameters etc. which should be loaded prior to
 * the header or anything that is sent to the browser as output. It may also be
 * included in Ajax calls.
 */

e107_require_once(e_PLUGIN . 'nodejs/nodejs.main.php');

// Register events.
$event = e107::getEvent();
$event->register('logout', 'nodejs_event_logout_callback');
$event->register('admin_plugin_install', 'nodejs_update_addon_list');
$event->register('admin_plugin_uninstall', 'nodejs_update_addon_list');
$event->register('admin_plugin_upgrade', 'nodejs_update_addon_list');
$event->register('admin_plugin_refresh', 'nodejs_update_addon_list');
$event->register('system_plugins_table_updated', 'nodejs_update_addon_list');

// Start session.
$session_started = session_id() === '' ? false : true;
if($session_started)
{
	session_start();
}

// Update session in database.
nodejs_session_db_handler();

// Register a shutdown function to send queued messages to nodejs server.
register_shutdown_function(array('Nodejs', 'sendMessages'));

// Save nodejs settings into session.
$_SESSION['nodejs_config'] = $nodejs_config = nodejs_get_config();

/**
 * User logout listener.
 *
 * @param array $data
 *  The users IP address.
 */
function nodejs_event_logout_callback($data)
{
	if(isset($_SESSION['nodejs_config']['authToken']))
	{
		nodejs_logout_user($_SESSION['nodejs_config']['authToken']);
	}
}

/**
 * Callback function to update nodejs addon list.
 */
function nodejs_update_addon_list()
{
	$fl = e107::getFile();

	$plugList = $fl->get_files(e_PLUGIN, "^plugin\.(php|xml)$", "standard", 1);
	$pluginList = array();
	$addonsList = array();

	// Remove Duplicates caused by having both plugin.php AND plugin.xml.
	foreach($plugList as $num => $val)
	{
		$key = basename($val['path']);
		$pluginList[$key] = $val;
	}

	foreach($pluginList as $p)
	{
		$p['path'] = substr(str_replace(e_PLUGIN, '', $p['path']), 0, -1);
		$plugin_path = $p['path'];

		if(is_readable(e_PLUGIN . $plugin_path . '/e_nodejs.php'))
		{
			$addonsList[] = $plugin_path;
		}
	}

	e107::getPlugConfig('nodejs')->set('nodejs_addon_list', $addonsList)->save();
}
