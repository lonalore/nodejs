<?php
/**
 * @file
 * Class instantiation to prepare JavaScript configurations and include css/js
 * files to page header.
 */

if (!defined('e107_INIT'))
{
	exit;
}

// Load required main class of plugin.
e107_require_once(e_PLUGIN . 'nodejs/classes/nodejs.main.php');

/**
 * Class nodejs_e_header.
 */
class nodejs_e_header
{

	function __construct()
	{
		self::include_components();
	}


	/**
	 * Include necessary CSS and JS files
	 */
	function include_components()
	{
		if (self::include_components_check())
		{
			$nodejs_config = nodejs_get_config();

			if (isset($nodejs_config['serviceKey']))
			{
				unset($nodejs_config['serviceKey']);
			}

			$socket_io_config = self::get_socketio_js_config($nodejs_config);
			$js_config = 'var e107Nodejs = e107Nodejs || { settings: ' . nodejs_json_encode($nodejs_config) . ' };';

			e107::js('url', $socket_io_config['path'], null, 2);
			e107::js('inline', $js_config, null, 3);
			e107::js('nodejs', 'js/nodejs.js', 'jquery', 4);

			// Add custom js handlers.
			foreach (self::get_js_handlers() as $plugin => $files)
			{
				foreach ($files as $file)
				{
					e107::js($plugin, $file, null, 5);
				}
			}
		}
	}


	/**
	 * Check if we should add the node.js js to the page.
	 *
	 * We check the url, and whether or not the admin has closed down access to
	 * auth users only.
	 */
	function include_components_check()
	{
		// TODO: Provide ability to exclude paths.
		$valid_page = true;

		// TODO: Provide ability to exclude anonymous users.
		if ($authenticated_users_only = true)
		{
			$valid_user = USERID > 0;
		}
		else
		{
			$valid_user = true;
		}

		return $valid_page && $valid_user;
	}


	/**
	 * Return the path to the socket.io client js.
	 */
	function get_socketio_js_config($nodejs_config)
	{
		$socket_io_config = array(
			'path' => false,
		);

		if (!$socket_io_config['path'])
		{
			$socket_io_config['path'] = $nodejs_config['client']['scheme'] . '://';
			$socket_io_config['path'] .= $nodejs_config['client']['host'];
			$socket_io_config['path'] .= ':' . $nodejs_config['client']['port'];
			$socket_io_config['path'] .= $nodejs_config['resource'];
			$socket_io_config['path'] .= '/socket.io.js';
		}

		return $socket_io_config;
	}


	/**
	 * Get a list of javascript handler files.
	 */
	function get_js_handlers()
	{
		$sql = e107::getDb();

		$handlers = array();
		$enabledPlugins = array();

		// Get list of enabled plugins.
		$sql->select("plugin", "*", "plugin_id !='' order by plugin_path ASC");
		while ($row = $sql->fetch())
		{
			if ($row['plugin_installflag'] == 1)
			{
				$enabledPlugins[] = $row['plugin_path'];
			}
		}

		$addonList = e107::getPlugConfig('nodejs')->get('nodejs_addon_list', array());
		foreach ($addonList as $plugin)
		{
			if (in_array($plugin, $enabledPlugins))
			{
				$file = e_PLUGIN . $plugin . '/e_nodejs.php';

				if (is_readable($file))
				{
					e107_require_once($file);
					$addonClass = $plugin . '_nodejs';

					if (class_exists($addonClass)) {
						$addon = new $addonClass();

						if (method_exists($addon, 'jsHandlers')) {
							$handlers[$plugin] = (array)$addon->jsHandlers();
						}
					}
				}
			}
		}

		return $handlers;
	}
}

// Class instantiation.
new nodejs_e_header;
