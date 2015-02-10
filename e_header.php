<?php
/**
 * @file
 * Class instantiation to prepare JavaScript configurations and include css/js
 * files to page header.
 */

if (!defined('e107_INIT')) {
  exit;
}

// Load required main class of plugin.
require_once("classes/nodejs.main.php");

/**
 * Class nodejs_e_header.
 */
class nodejs_e_header {

  function __construct() {
    self::include_components();
  }

  /**
   * Include necessary CSS and JS files
   */
  function include_components() {
    if (self::include_components_check()) {
      $_SESSION['nodejs_config'] = $nodejs_config = nodejs_get_config();

      if (isset($nodejs_config['serviceKey'])) {
        unset($nodejs_config['serviceKey']);
      }

      $socket_io_config = self::get_socketio_js_config($nodejs_config);
      $js_config = 'var e107Nodejs = e107Nodejs || { settings: ' . nodejs_json_encode($nodejs_config) . ' };';

      e107::js('url', $socket_io_config['path'], NULL, 2);
      e107::js('inline', $js_config, NULL, 3);
      e107::js('nodejs', 'js/nodejs.js', 'jquery', 4);

      // TODO: Provide ability to add custom js handlers.
      foreach (self::get_js_handlers() as $handler_file) {
        e107::js('url', $handler_file, NULL, 5);
      }
    }
  }

  /**
   * Check if we should add the node.js js to the page.
   *
   * We check the url, and whether or not the admin has closed down access to
   * auth users only.
   */
  function include_components_check() {
    // TODO: Provide ability to exclude paths.
    $valid_page = TRUE;

    // TODO: Provide ability to exclude anonymous users.
    if ($authenticated_users_only = FALSE) {
      $valid_user = USERID > 0;
    }
    else {
      $valid_user = TRUE;
    }

    return $valid_page && $valid_user;
  }

  /**
   * Return the path to the socket.io client js.
   */
  function get_socketio_js_config($nodejs_config) {
    $socket_io_config = array(
      'path' => FALSE,
    );

    if (!$socket_io_config['path']) {
      $socket_io_config['path'] = $nodejs_config['client']['scheme'] . '://';
      $socket_io_config['path'] .= $nodejs_config['client']['host'] . ':';
      $socket_io_config['path'] .= $nodejs_config['client']['port'];
      $socket_io_config['path'] .= $nodejs_config['resource'];
      $socket_io_config['path'] .= '/socket.io.js';
    }

    return $socket_io_config;
  }

  /**
   * Get a list of javascript handler files.
   */
  function get_js_handlers() {
    $handlers = array();
    return $handlers;
  }
}

// Class instantiation.
new nodejs_e_header;
