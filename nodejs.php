<?php
/**
 * @file
 * Provide a listener class to handle all messages from Node.js server.
 */

/**
 * Class NodejsListener.
 */
class NodejsListener extends Nodejs {

  /**
   * Constructor function.
   */
  public function __construct() {
    if (!isset($_POST['serviceKey']) || !Nodejs::is_valid_service_key($_POST['serviceKey'])) {
      header('Content-Type', 'application/json');
      echo json_encode(array('error' => 'Invalid service key.'));
      exit();
    }

    if (!isset($_POST['messageJson'])) {
      header('Content-Type', 'application/json');
      echo json_encode(array('error' => 'No message.'));
      exit();
    }

    $message = json_decode($_POST['messageJson']);
    self::message_handler($message);
  }

  /**
   * Callback: handles all messages from Node.js server.
   */
  public function message_handler($message) {
    $response = array();
    switch ($message['messageType']) {
      case 'authenticate':
        $response = Nodejs::auth_check($message);
        break;

      case 'userOffline':
        if (empty($message['uid'])) {
          $response['error'] = 'Missing uid for userOffline message.';
        }
        else if (!preg_match('/^\d+$/', $message['uid'])) {
          $response['error'] = 'Invalid (!/^\d+$/) uid for userOffline message.';
        }
        else {
          Nodejs::user_set_offline($message['uid']);
          $response['message'] = "User {$message['uid']} set offline.";
        }
        break;

      default:
        $handlers = array();

        // TODO: Ability to define custom message callbacks by other plugins.
        /**
        foreach ($modules as $module) {
          $function = $module . '_nodejs_message_callback';
          if (is_array($function($message['messageType']))) {
            $handlers += $function($message['messageType']);
          }
        }
         */

        foreach ($handlers as $callback) {
          $callback($message, $response);
        }
    }

    header('Content-Type', 'application/json');
    echo json_encode($response ? $response : array('error' => 'Not implemented'));
    exit();
  }

}

// Class installation.
new NodejsListener();
