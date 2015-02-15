<?php
/**
 * @file
 * Provide a listener class to handle all messages from Node.js server.
 */

require_once('../../class2.php');

/**
 * Class NodejsListener.
 */
class NodejsListener
{

	/**
	 * Constructor function.
	 */
	public function __construct()
	{
		if (!isset($_POST['serviceKey']) || !nodejs_is_valid_service_key($_POST['serviceKey']))
		{
			header('Content-Type: application/json');
			echo nodejs_json_encode(array('error' => 'Invalid service key.'));
			exit();
		}

		if (!isset($_POST['messageJson']))
		{
			header('Content-Type: application/json');
			echo nodejs_json_encode(array('error' => 'No message.'));
			exit();
		}

		$message = json_decode($_POST['messageJson'], true);
		$this->message_handler($message);
	}


	/**
	 * Callback: handles all messages from Node.js server.
	 */
	public function message_handler($message)
	{
		$response = array();

		switch ($message['messageType'])
		{
			case 'authenticate':
				$response = nodejs_auth_check($message);
				break;

			case 'userOffline':
				if (empty($message['uid']))
				{
					$response['error'] = 'Missing uid for userOffline message.';
				}
				else
				{
					if (!preg_match('/^\d+$/', $message['uid']))
					{
						$response['error'] = 'Invalid (!/^\d+$/) uid for userOffline message.';
					}
					else
					{
						nodejs_user_set_offline($message['uid']);
						$response['message'] = "User {$message['uid']} set offline.";
					}
				}
				break;

			default:
				$handlers = array();

				foreach (self::get_message_handlers() as $plugin => $handler)
				{
					if (isset($handler['path']) && isset($handler['function']))
					{
						$file = e_PLUGIN . $plugin . '/' . ltrim($handler['path'], '/');
						e107_require_once($file);

						if (function_exists($handler['function']))
						{
							if (is_array($handler['function']($message['messageType'])))
							{
								$handlers[] = $handler['function']($message['messageType']);
							}
						}
					}
				}

				foreach ($handlers as $callback)
				{
					$callback($message, $response);
				}
		}

		$var = $response ? $response : array('error' => 'Not implemented');

		header('Content-Type: application/json');
		echo nodejs_json_encode($var);
		exit();
	}


	/**
	 * Get a list of message handlers are defined in plugins.
	 */
	function get_message_handlers()
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

					if (class_exists($addonClass))
					{
						$addon = new $addonClass();

						if (method_exists($addon, 'jsHandlers'))
						{
							$handlers[$plugin] = (array)$addon->msgHandlers();
						}
					}
				}
			}
		}

		return $handlers;
	}

}

// Class installation.
new NodejsListener();
