<?php
/**
 * @file
 * Class installations to handle configuration forms on Admin UI.
 */

require_once('../../class2.php');

if (!getperms('P'))
{
	header('location:' . e_BASE . 'index.php');
	exit;
}

// [PLUGINS]/nodejs/languages/[LANGUAGE]/[LANGUAGE]_admin.php
e107::lan('nodejs', true, true);

/**
 * Class nodejs_admin.
 */
class nodejs_admin extends e_admin_dispatcher
{

	protected $modes = array(
		'main' => array(
			'controller' => 'nodejs_admin_main_ui',
			'path' => null,
		),
		'scan' => array(
			'controller' => 'nodejs_admin_scan_ui',
			'path' => null,
		),
	);

	protected $adminMenu = array(
		'main/prefs' => array(
			'caption' => LAN_AC_NODEJS_01,
			'perm' => 'P',
		),
		'scan/list' => array(
			'caption' => LAN_AC_NODEJS_03,
			'perm' => 'P',
		),
	);

	protected $menuTitle = LAN_PLUGIN__NODEJS_NAME;

}

/**
 * Class nodejs_admin_ui.
 */
class nodejs_admin_main_ui extends e_admin_ui
{

	protected $pluginTitle = LAN_PLUGIN__NODEJS_NAME;

	protected $pluginName = "nodejs";

	protected $preftabs = array(
		LAN_AC_NODEJS_02,
	);

	protected $prefs = array(
		// Node.js server tab.
		'nodejs_protocol' => array(
			'title' => LAN_AI_NODEJS_01,
			'description' => LAN_AD_NODEJS_01,
			'type' => 'boolean',
			'writeParms' => 'enabled=http&disabled=https',
			'data' => 'int',
			'tab' => 0,
		),
		'nodejs_host' => array(
			'title' => LAN_AI_NODEJS_02,
			'description' => LAN_AD_NODEJS_02,
			'type' => 'text',
			'data' => 'str',
			'tab' => 0,
		),
		'nodejs_port' => array(
			'title' => LAN_AI_NODEJS_03,
			'description' => LAN_AD_NODEJS_03,
			'type' => 'number',
			'data' => 'int',
			'tab' => 0,
		),
		'nodejs_resource' => array(
			'title' => LAN_AI_NODEJS_04,
			'description' => LAN_AD_NODEJS_04,
			'type' => 'text',
			'data' => 'str',
			'tab' => 0,
		),
		'nodejs_service_key' => array(
			'title' => LAN_AI_NODEJS_05,
			'description' => LAN_AD_NODEJS_05,
			'type' => 'text',
			'data' => 'str',
			'tab' => 0,
		),
	);

}

class nodejs_admin_scan_ui extends e_admin_ui
{

	protected $pluginTitle = LAN_PLUGIN__NODEJS_NAME;


	function listPage()
	{
		$fl = e107::getFile();
		$mes = e107::getMessage();

		$plugList = $fl->get_files(e_PLUGIN, "^plugin\.(php|xml)$", "standard", 1);
		$pluginList = array();
		$addonsList = array();

		// Remove Duplicates caused by having both plugin.php AND plugin.xml.
		foreach ($plugList as $num => $val)
		{
			$key = basename($val['path']);
			$pluginList[$key] = $val;
		}

		foreach ($pluginList as $p)
		{
			$p['path'] = substr(str_replace(e_PLUGIN, "", $p['path']), 0, -1);
			$plugin_path = $p['path'];

			if (is_readable(e_PLUGIN . $plugin_path . "/e_nodejs.php"))
			{
				$addonsList[] = $plugin_path;
			}
		}

		e107::getPlugConfig('nodejs')->set('nodejs_addon_list', $addonsList);

		$summ = count($addonsList);
		$message = str_replace('[summ]', $summ, LAN_AC_NODEJS_05);

		$mes->addInfo($message);
		$this->addTitle(LAN_AC_NODEJS_04);

		echo $mes->render();
	}

}

new nodejs_admin();

require_once(e_ADMIN . "auth.php");
e107::getAdminUI()->runPage();
require_once(e_ADMIN . "footer.php");
exit;
