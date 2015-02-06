<?php
/**
 * @file
 * Class installations to handle configuration forms on Admin UI.
 */

require_once('../../class2.php');

if (!getperms('P')) {
  header('location:' . e_BASE . 'index.php');
  exit;
}

// [PLUGINS]/nodejs/languages/[LANGUAGE]/[LANGUAGE]_admin.php
e107::lan('nodejs', TRUE, TRUE);

/**
 * Class nodejs_admin.
 */
class nodejs_admin extends e_admin_dispatcher {

  protected $modes = array(
    'main' => array(
      'controller' => 'nodejs_admin_ui',
      'path' => NULL,
    ),
  );

  protected $adminMenu = array(
    'main/prefs' => array(
      'caption' => LAN_AC_NODEJS_01,
      'perm' => 'P',
    ),
  );

  protected $menuTitle = LAN_PLUGIN__NODEJS_NAME;

}

/**
 * Class nodejs_admin_ui.
 */
class nodejs_admin_ui extends e_admin_ui {

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

new nodejs_admin();

require_once(e_ADMIN . "auth.php");
e107::getAdminUI()->runPage();
require_once(e_ADMIN . "footer.php");
exit;
