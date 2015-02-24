<?php
/**
 * @file
 * Installation hooks and callbacks of nodejs plugin.
 */

if(!defined('e107_INIT'))
{
	exit;
}


/**
 * Class nodejs_setup.
 */
class nodejs_setup
{

	/**
	 * This function is called before plugin table has been created
	 * by the nodejs_sql.php file.
	 *
	 * @param array $var
	 */
	function install_pre($var)
	{

	}

	/**
	 * This function is called after plugin table has been created
	 * by the nodejs_sql.php file.
	 *
	 * @param array $var
	 */
	function install_post($var)
	{
		// Scan plugin directories to check e_nodejs.php files.
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

	function uninstall_options()
	{

	}

	function uninstall_post($var)
	{

	}

	/**
	 * Trigger an upgrade alert or not.
	 *
	 * @param array $var
	 *
	 * @return bool
	 *  True to trigger an upgrade alert, and false to not.
	 */
	function upgrade_required($var)
	{
		return false;
	}


	function upgrade_pre($var)
	{

	}


	function upgrade_post($var)
	{

	}
}
