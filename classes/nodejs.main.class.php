<?php
/**
 * @file
 * Nodejs and NodejsMain classes.
 */

/**
 * Provides API callbacks.
 */
class Nodejs extends NodejsMain {

  /**
   * Generate a token for a piece of content.
   */
  public static function generate_content_token() {
    return NodejsMain::hmac_base64(NodejsMain::random_bytes(512), NodejsMain::get_private_key());
  }

  /**
   * Send a content change message to a content channel.
   */
  public static function send_content_channel_message($message) {
    NodejsMain::sendContentTokenMessage($message);
  }

  /**
   * Send a content channel token to Node.js.
   *
   * @param mixed $channel
   * @param mixed $notify_on_disconnect
   * @return mixed
   */
  public static function send_content_channel_token($channel, $notify_on_disconnect = FALSE) {
    $message = (object) array(
      'token' => self::generate_content_token(),
      'channel' => $channel,
      'notifyOnDisconnect' => $notify_on_disconnect,
    );

    // Http request went ok, process Node.js server response.
    if ($node_response = NodejsMain::sendContentToken($message)) {
      if ($node_response->status == 'ok') {
        // We always set this in e107Nodejs.settings, even though Ajax requests
        // will not see it. It's a bit ugly, but it means that setting the
        // tokens for full page requests will just work.
        // TODO: Add this to the e107Nodejs.settings.
        // array(array('nodejs' => array('contentTokens' => array($channel => $message->token))), array('type' => 'setting'));

        $node_response->token = $message->token;

        return $node_response;
      }
      else {
        $msg = 'Error sending content channel token for channel "' . $channel . '". Node.js server response: ' . $node_response->error;

        $log = e107::getLog();
        $log->add('nodejs', $node_response, E_LOG_INFORMATIVE, $msg);

        return FALSE;
      }
    }
    // Http request failed.
    else {
      return FALSE;
    }
  }

  /**
   * Get a list of users in a content channel.
   *
   * @param mixed $channel
   * @return mixed
   */
  public static function get_content_channel_users($channel) {
    $message = (object) array('channel' => $channel);

    // Http request went ok, process Node.js server response.
    if ($node_response = NodejsMain::getContentTokenUsers($message)) {
      if (isset($node_response->error)) {
        $msg = 'Error getting content channel users for channel "' . $channel . '" on the Node.js server. Server response: ' . $node_response->error;

        $log = e107::getLog();
        $log->add('nodejs', $node_response, E_LOG_INFORMATIVE, $msg);

        return FALSE;
      }
      else {
        return array(
          'uids' => !empty($node_response->users->uids) ? $node_response->users->uids : array(),
          'authTokens' => !empty($node_response->users->authTokens) ? $node_response->users->authTokens : array(),
        );
      }
    }
    // Http request failed.
    else {
      return FALSE;
    }
  }

  /**
   * Kick a user from the node.js server.
   *
   * @param mixed $uid
   * @return boolean
   *   TRUE if the user was kicked, FALSE otherwise.
   */
  public static function kick_user($uid) {
    // Http request went ok. Process Node.js server response.
    if ($node_response = NodejsMain::kickUser($uid)) {
      if ($node_response->status == 'success') {
        return TRUE;
      }
      else {
        $msg = 'Error kicking uid "' . $uid . '" from the Node.js server. Server response: ' . $node_response->error;

        $log = e107::getLog();
        $log->add('nodejs', $node_response, E_LOG_INFORMATIVE, $msg);

        return FALSE;
      }
    }
    // Http request failed, hence channel couldn't be added.
    else {
      return FALSE;
    }
  }

  /**
   * Logout any sockets associated with the given token from the node.js server.
   *
   * @param mixed $token
   * @return boolean
   *   TRUE if the user was logged out, FALSE otherwise.
   */
  public static function logout_user($token) {
    // Http request went ok. Process Node.js server response.
    if ($node_response = NodejsMain::logoutUser($token)) {
      if ($node_response->status == 'success') {
        return TRUE;
      }
      else {
        $msg = 'Error logging out token "' . $token . '" from the Node.js server. Server response: ' . $node_response->error;

        $log = e107::getLog();
        $log->add('nodejs', $node_response, E_LOG_INFORMATIVE, $msg);

        return FALSE;
      }
    }
    // Http request failed, hence channel couldn't be added.
    else {
      return FALSE;
    }
  }

  /**
   * Set the list of uids a user can see presence notifications for.
   *
   * @param int $uid
   * @param array $uids
   * @return boolean
   */
  public static function set_user_presence_list($uid, array $uids) {
    // Http request went ok. Process Node.js server response.
    if ($node_response = NodejsMain::setUserPresenceList($uid, $uids)) {
      if ($node_response->status == 'success') {
        return TRUE;
      }
      else {
        $msg = 'Error setting user presence list for "' . $uid . '" on the Node.js server. Server response: ' . $node_response->error;

        $log = e107::getLog();
        $log->add('nodejs', $node_response, E_LOG_INFORMATIVE, $msg);

        return FALSE;
      }
    }
    // Http request failed, hence channel couldn't be added.
    else {
      return FALSE;
    }
  }

  /**
   * Broadcast a message to all clients.
   *
   * @param string $subject
   * @param string $body
   */
  public static function broadcast_message($subject, $body) {
    $message = (object) array(
      'broadcast' => TRUE,
      'data' => (object) array(
        'subject' => $subject,
        'body' => $body,
      ),
      'channel' => 'nodejs_notify',
    );
    self::enqueue_message($message);
  }

  /**
   * Send a message to all users subscribed to a given channel.
   */
  public static function send_channel_message($channel, $subject, $body) {
    $message = (object) array(
      'data' => (object) array(
        'subject' => $subject,
        'body' => $body,
      ),
      'channel' => $channel,
    );
    self::enqueue_message($message);
  }

  /**
   * Send a message to given user.
   *
   * @param int $uid
   * @param string $subject
   * @param string $body
   */
  public static function send_user_message($uid, $subject, $body) {
    $message = (object) array(
      'data' => (object) array(
        'subject' => $subject,
        'body' => $body,
      ),
      'channel' => 'nodejs_user_' . $uid,
      'callback' => 'nodejsNotify',
    );
    self::enqueue_message($message);
  }

  /**
   * Send a message to multiple users.
   *
   * @param string|array $uids
   *   A list of uid seperated by comma (,) or an array of uids
   * @param string $subject
   * @param string $body
   */
  public static function send_user_message_multiple($uids, $subject, $body) {
    if (!is_array($uids)) {
      $uids = explode(',', $uids);
    }
    foreach ($uids as $uid) {
      self::send_user_message($uid, $subject, $body);
    }
  }

  /**
   * Send a message to users in a role.
   *
   * @param string $role_name
   * @param string $subject
   * @param string $body
   */
  public static function send_role_message($role_name, $subject, $body) {
    // TODO: Select User IDs associated with a role.
    $uids = array();

    self::send_user_message_multiple($uids, $subject, $body);
  }

  /**
   * Add a channel to the Node.js server.
   *
   * @param channel
   * @return boolean
   */
  public static function add_channel($channel) {
    // Http request went ok. Process Node.js server response.
    if ($node_response = NodejsMain::addChannel($channel)) {
      if ($node_response->status == 'success') {
        return TRUE;
      }
      else {
        $msg = 'Error adding channel to the Node.js server. Server response: ' . $node_response->error;

        $log = e107::getLog();
        $log->add('nodejs', $node_response, E_LOG_INFORMATIVE, $msg);

        return FALSE;
      }
    }
    // Http request failed, hence channel couldn't be added.
    else {
      return FALSE;
    }
  }

  /**
   * Checks whether a channel exists on the Node.js server.
   *
   * @param channel
   * @return boolean
   *  TRUE if the specified channel exists on the Node.js server, FALSE otherwise.
   */
  public static function check_channel($channel) {
    // Http request went ok. Process Node.js server response.
    if ($node_response = NodejsMain::checkChannel($channel)) {
      if ($node_response->status == 'success') {
        return $node_response->result;
      }
      else {
        $msg = 'Error checking channel on the Node.js server. Server response: ' . $node_response->error;

        $log = e107::getLog();
        $log->add('nodejs', $node_response, E_LOG_INFORMATIVE, $msg);

        return FALSE;
      }
    }
    // Http request failed.
    else {
      return FALSE;
    }
  }

  /**
   * Remove a channel from the Node.js server.
   *
   * @param channel
   * @return boolean
   */
  public static function remove_channel($channel) {
    // Http request went ok. Process Node.js server response.
    if ($node_response = NodejsMain::removeChannel($channel)) {
      if ($node_response->status == 'success') {
        return TRUE;
      }
      else {
        $msg = 'Error removing channel from the Node.js server. Server response: ' . $node_response->error;

        $log = e107::getLog();
        $log->add('nodejs', $node_response, E_LOG_INFORMATIVE, $msg);

        return FALSE;
      }
    }
    // Http request failed.
    else {
      return FALSE;
    }
  }

  /**
   * Enqueue a message for sending at the end of the request.
   *
   * @param StdClass $message
   *  Object contains message details.
   */
  public static function enqueue_message(StdClass $message) {
    $message->broadcast = isset($message->broadcast) ? $message->broadcast : FALSE;
    NodejsMain::enqueueMessage($message);
  }

  /**
   * Send a message immediately.
   *
   * @param StdClass $message
   *  Object contains message details.
   *
   * @return bool|mixed
   */
  public static function send_message(StdClass $message) {
    $message->broadcast = isset($message->broadcast) ? $message->broadcast : FALSE;
    return NodejsMain::sendMessage($message);
  }

  /**
   * Check if the given service key is valid.
   */
  public static function is_valid_service_key($service_key) {
    $plugPrefs = e107::getPlugConfig('nodejs')->getPref();
    return $service_key == $plugPrefs['nodejs_service_key'];
  }

  /**
   * Checks the given key to see if it matches a valid session.
   */
  public static function auth_check($message) {
    $uid = self::auth_check_callback($message['authToken']);

    $auth_user = new stdClass();
    $auth_user->uid = $uid > 0 ? $uid : 0;
    $auth_user->authToken = $message['authToken'];
    $auth_user->nodejsValidAuthToken = $uid !== FALSE;
    $auth_user->clientId = $message['clientId'];

    if ($auth_user->nodejsValidAuthToken) {
      // Get the list of channels I have access to.
      $auth_user->channels = array();

      if ((int) $auth_user->uid > 0) {
        $auth_user->channels[] = 'nodejs_user_' . $auth_user->uid;

        // TODO: Ability to add user to plugin defined channels.
      }

      // TODO: Get the list of users who can see presence notifications about me.
      $auth_user->presenceUids = array_unique(array());

      $nodejs_config = NodejsMain::get_config();
      $auth_user->serviceKey = $nodejs_config['serviceKey'];

      // TODO: Add this to http header.
      // array('NodejsServiceKey', $nodejs_config['serviceKey']);

      if ($auth_user->uid) {
        self::user_set_online($auth_user->uid);
      }
      $auth_user->contentTokens = isset($message['contentTokens']) ? $message['contentTokens'] : array();
    }

    return $auth_user;
  }

  /**
   * Default Node.js auth check callback implementation.
   */
  public static function auth_check_callback($auth_token) {
    $sql = e107::getDb();
    $result = $sql->retrieve('nodejs_sessions', 'uid', 'MD5(sid)="' . $auth_token . '"');
    return $result;
  }

  /**
   * Set the user as online.
   *
   * @param $uid
   */
  public static function user_set_online($uid) {
    try {
      $insert = array(
        'uid' => (int) $uid,
        'login_time' => time(),
      );

      $sql = e107::getDb();
      $sql->insert('nodejs_presence', $insert);
    } catch (Exception $e) {
    }
  }

  /**
   * Set the user as offline.
   *
   * @param $uid
   */
  public static function user_set_offline($uid) {
    try {
      $sql = e107::getDb();
      $sql->delete("nodejs_presence", "uid=" . (int) $uid);
    } catch (Exception $e) {
    }
  }

  /**
   * Remove a user from a channel.
   *
   * @param mixed $uid
   * @param mixed $channel
   * @return boolean
   */
  public static function remove_user_from_channel($uid, $channel) {
    // Http request went ok. Process Node.js server response.
    if ($node_response = NodejsMain::removeUserFromChannel($uid, $channel)) {
      if ($node_response->status == 'success') {
        return TRUE;
      }
      else {
        $params = array(
          'uid' => $uid,
          'channel' => $channel,
          'error' => $node_response->error,
        );

        $msg = 'Error removing user with uid: ' . $params['uid'] . ' from channel ' . $params['channel'] . ' on the Node.js server. Server response: ' . $params['error'];

        $log = e107::getLog();
        $log->add('nodejs', $params, E_LOG_INFORMATIVE, $msg);

        return FALSE;
      }
    }
    // Http request failed.
    else {
      return FALSE;
    }
  }

  /**
   * Add a user to a channel.
   *
   * @param mixed $uid
   * @param mixed $channel
   * @return boolean
   */
  public static function add_user_to_channel($uid, $channel) {
    // Http request went ok. Process Node.js server response.
    if ($node_response = NodejsMain::addUserToChannel($uid, $channel)) {
      if ($node_response->status == 'success') {
        return TRUE;
      }
      else {
        $params = array(
          'uid' => $uid,
          'channel' => $channel,
          'error' => $node_response->error,
        );

        $msg = 'Error adding user with uid: ' . $params['uid'] . ' to channel ' . $params['channel'] . ' on the Node.js server. Server response: ' . $params['error'];

        $log = e107::getLog();
        $log->add('nodejs', $params, E_LOG_INFORMATIVE, $msg);

        return FALSE;
      }
    }
    // Http request failed.
    else {
      return FALSE;
    }
  }
}

/**
 * Class NodejsMain.
 */
class NodejsMain {
  public static $messages = array();

  public static $config = NULL;

  public static $baseUrl = NULL;

  public static $headers = NULL;

  /**
   * Build configuration array.
   */
  public static function initConfig() {
    if (!isset(self::$config)) {
      self::$config = self::get_config();
      self::$headers = array('NodejsServiceKey' => self::$config['serviceKey']);
      self::$baseUrl = self::get_url(e_PLUGIN_ABS . 'nodejs/');
    }
  }

  /**
   * Getter function.
   *
   * @return array
   */
  public static function getMessages() {
    return self::$messages;
  }

  /**
   * Getter function.
   *
   * @param \StdClass $message
   */
  public static function enqueueMessage(StdClass $message) {
    self::$messages[] = $message;
  }

  /**
   * Send all pending messages.
   */
  public static function sendMessages() {
    foreach (self::$messages as $message) {
      self::sendMessage($message);
    }
  }

  /**
   * Send pending message.
   *
   * @param \StdClass $message
   *  Object contains message details.
   *
   * @return bool|mixed
   */
  public static function sendMessage(StdClass $message) {
    self::initConfig();

    $message->clientSocketId = self::get_client_socket_id();

    $options = array(
      'method' => 'POST',
      'data' => json_encode($message),
      'headers' => self::$headers,
      // This is server to server, so start with a low default timeout.
      'timeout' => !empty(self::$config['timeout']) ? self::$config['timeout'] : 5,
    );

    return self::httpRequest('nodejs/publish', $options);
  }

  /**
   * @param $uid
   * @param array $uids
   * @return bool|mixed
   */
  public static function setUserPresenceList($uid, array $uids) {
    self::initConfig();
    return self::httpRequest("nodejs/user/presence-list/$uid/" . implode(',', $uids), array('headers' => self::$headers));
  }

  /**
   * @param $token
   * @return bool|mixed
   */
  public static function logoutUser($token) {
    self::initConfig();
    return self::httpRequest("nodejs/user/logout/$token", array('headers' => self::$headers));
  }

  /**
   * @param $message
   * @return bool|mixed
   */
  public static function sendContentTokenMessage($message) {
    self::initConfig();

    $message->clientSocketId = self::get_client_socket_id();

    $options = array(
      'method' => 'POST',
      'data' => json_encode($message),
      'headers' => self::$headers,
      'options' => array('timeout' => 5.0),
    );

    return self::httpRequest('nodejs/content/token/message', $options);
  }

  /**
   * @param $message
   * @return bool|mixed
   */
  public static function sendContentToken($message) {
    self::initConfig();

    $options = array(
      'method' => 'POST',
      'data' => json_encode($message),
      'headers' => self::$headers,
    );

    return self::httpRequest('nodejs/content/token', $options);
  }

  /**
   * @param $message
   * @return bool|mixed
   */
  public static function getContentTokenUsers($message) {
    self::initConfig();

    $options = array(
      'method' => 'POST',
      'data' => json_encode($message),
      'headers' => self::$headers,
    );

    return self::httpRequest('nodejs/content/token/users', $options);
  }

  /**
   * @param $uid
   * @return bool|mixed
   */
  public static function kickUser($uid) {
    self::initConfig();

    return self::httpRequest("nodejs/user/kick/$uid", array('headers' => self::$headers));
  }

  /**
   * @param $uid
   * @param $channel
   * @return bool|mixed
   */
  public static function addUserToChannel($uid, $channel) {
    self::initConfig();

    return self::httpRequest("nodejs/user/channel/add/$channel/$uid", array('headers' => self::$headers));
  }

  /**
   * @param $uid
   * @param $channel
   * @return bool|mixed
   */
  public static function removeUserFromChannel($uid, $channel) {
    self::initConfig();

    return self::httpRequest("nodejs/user/channel/remove/$channel/$uid", array('headers' => self::$headers));
  }

  /**
   * @param $channel
   * @return bool|mixed
   */
  public static function addChannel($channel) {
    self::initConfig();

    return self::httpRequest("nodejs/channel/add/$channel", array('headers' => self::$headers));
  }

  /**
   * @param $channel
   * @return bool|mixed
   */
  public static function checkChannel($channel) {
    self::initConfig();

    return self::httpRequest("nodejs/channel/check/$channel", array('headers' => self::$headers));
  }

  /**
   * @param $channel
   * @return bool|mixed
   */
  public static function removeChannel($channel) {
    self::initConfig();

    return self::httpRequest("nodejs/channel/remove/$channel", array('headers' => self::$headers));
  }

  /**
   * Get the client socket id associated with this request.
   */
  public static function get_client_socket_id() {
    $client_socket_id = isset($_POST['nodejs_client_socket_id']) ? $_POST['nodejs_client_socket_id'] : '';
    return preg_match('/^[0-9a-z_-]+$/i', $client_socket_id) ? $client_socket_id : '';
  }

  /**
   * @param $url
   * @param $options
   * @return bool|mixed
   */
  public static function httpRequest($url, $options) {
    self::initConfig();

    $response = self::http_request(self::$baseUrl . $url, $options);

    // If a http error occurred, and logging of http errors is enabled, log it.
    if (isset($response->error)) {
      if (self::$config['log_http_errors']) {
        $params = array(
          'code' => $response->code,
          'error' => $response->error,
          'url' => $url,
        );

        $log_message = 'Error reaching the Node.js server at "' . $params['url'] . '": [' . $params['code'] . '] ' . $params['error'] . '.';
        if (!empty($options['data'])) {
          $params['data'] = $options['data'];
          $log_message = 'Error reaching the Node.js server at "' . $params['url'] . '" with data "' . $params['data'] . '": [' . $params['code'] . '] ' . $params['error'] . '.';
        }

        $log = e107::getLog();
        $log->add('nodejs', $params, E_LOG_INFORMATIVE, $log_message);
      }

      return FALSE;
    }

    // No errors, so return Node.js server response.
    return json_decode($response->data);
  }

  /**
   * Get nodejs server config.
   *
   * @return array
   */
  public static function get_config() {
    $plugPrefs = e107::getPlugConfig('nodejs')->getPref();

    $defaults = array(
      'nodejs' => array(
        'scheme' => (bool) $plugPrefs['nodejs_protocol'] ? 'http' : 'https',
        'secure' => (bool) $plugPrefs['nodejs_protocol'] ? 0 : 1,
        'host' => $plugPrefs['nodejs_host'],
        'port' => $plugPrefs['nodejs_port'],
      ),
      'client' => array(
        'scheme' => (bool) $plugPrefs['nodejs_protocol'] ? 'http' : 'https',
        'secure' => (bool) $plugPrefs['nodejs_protocol'] ? 0 : 1,
        'host' => $plugPrefs['nodejs_host'],
        'port' => $plugPrefs['nodejs_port'],
      ),
      'resource' => !empty($plugPrefs['nodejs_resource']) ? $plugPrefs['nodejs_resource'] : '/socket.io',
      'authToken' => self::auth_get_token(),
      'serviceKey' => $plugPrefs['nodejs_service_key'],
      'websocketSwfLocation' => e_PLUGIN_ABS . 'nodejs' . '/socket_io/socket.io/support/socket.io-client/lib/vendor/web-socket-js/WebSocketMain.swf',
      'log_http_errors' => (bool) $plugPrefs['nodejs_log_http_errors'],
    );

    return $defaults;
  }

  /**
   * Get an auth token for the current user.
   */
  public static function auth_get_token() {
    if (session_status() == PHP_SESSION_NONE) {
      session_start();
    }

    return md5(session_id());
  }

  /**
   * Get the URL of a Node.js callback.
   *
   * @param string $callback
   *   The path to call on Node.js server (with leading /).
   *
   * @return string
   */
  public static function get_url($callback = '/') {
    self::initConfig();

    return self::$config['nodejs']['scheme'] . '://' . self::$config['nodejs']['host'] . ':' . self::$config['nodejs']['port'] . $callback;
  }

  /**
   * Ported and modified function from Drupal 7 core.
   *
   * Performs an HTTP request.
   *
   * This is a flexible and powerful HTTP client implementation. Correctly
   * handles GET, POST, PUT or any other HTTP requests. Handles redirects.
   *
   * @param $url
   *   A string containing a fully qualified URI.
   * @param array $options
   *   (optional) An array that can have one or more of the following elements:
   *   - headers: An array containing request headers to send as name/value pairs.
   *   - method: A string containing the request method. Defaults to 'GET'.
   *   - data: A string containing the request body, formatted as
   *     'param=value&param=value&...'. Defaults to NULL.
   *   - max_redirects: An integer representing how many times a redirect
   *     may be followed. Defaults to 3.
   *   - timeout: A float representing the maximum number of seconds the function
   *     call may take. The default is 30 seconds. If a timeout occurs, the error
   *     code is set to the HTTP_REQUEST_TIMEOUT constant.
   *   - context: A context resource created with stream_context_create().
   *
   * @return object
   *   An object that can have one or more of the following components:
   *   - request: A string containing the request body that was sent.
   *   - code: An integer containing the response status code, or the error code
   *     if an error occurred.
   *   - protocol: The response protocol (e.g. HTTP/1.1 or HTTP/1.0).
   *   - status_message: The status message from the response, if a response was
   *     received.
   *   - redirect_code: If redirected, an integer containing the initial response
   *     status code.
   *   - redirect_url: If redirected, a string containing the URL of the redirect
   *     target.
   *   - error: If an error occurred, the error message. Otherwise not set.
   *   - headers: An array containing the response headers as name/value pairs.
   *     HTTP header names are case-insensitive (RFC 2616, section 4.2), so for
   *     easy access the array keys are returned in lower case.
   *   - data: A string containing the response body that was received.
   */
  public static function http_request($url, array $options = array()) {
    $result = new stdClass();

    // Parse the URL and make sure we can handle the schema.
    $uri = @parse_url($url);

    if ($uri == FALSE) {
      $result->error = 'unable to parse URL';
      $result->code = -1001;
      return $result;
    }

    if (!isset($uri['scheme'])) {
      $result->error = 'missing schema';
      $result->code = -1002;
      return $result;
    }

    self::timer_start(__FUNCTION__);

    // Merge the default options.
    $options += array(
      'headers' => array(),
      'method' => 'GET',
      'data' => NULL,
      'max_redirects' => 3,
      'timeout' => 30.0,
      'context' => NULL,
    );

    // Merge the default headers.
    $options['headers'] += array(
      'User-Agent' => 'e107 (+http://e107.org/)',
    );

    // stream_socket_client() requires timeout to be a float.
    $options['timeout'] = (float) $options['timeout'];

    // Use a proxy if one is defined and the host is not on the excluded list.
    $proxy_server = '';
    if ($proxy_server && self::http_use_proxy($uri['host'])) {
      // Set the scheme so we open a socket to the proxy server.
      $uri['scheme'] = 'proxy';
      // Set the path to be the full URL.
      $uri['path'] = $url;
      // Since the URL is passed as the path, we won't use the parsed query.
      unset($uri['query']);

      // Add in username and password to Proxy-Authorization header if needed.
      if ($proxy_username = '') {
        $proxy_password = '';
        $options['headers']['Proxy-Authorization'] = 'Basic ' . base64_encode($proxy_username . (!empty($proxy_password) ? ":" . $proxy_password : ''));
      }
      // Some proxies reject requests with any User-Agent headers, while others
      // require a specific one.
      $proxy_user_agent = '';
      // The default value matches neither condition.
      if ($proxy_user_agent === NULL) {
        unset($options['headers']['User-Agent']);
      }
      elseif ($proxy_user_agent) {
        $options['headers']['User-Agent'] = $proxy_user_agent;
      }
    }

    switch ($uri['scheme']) {
      case 'proxy':
        // Make the socket connection to a proxy server.
        $socket = 'tcp://' . $proxy_server . ':' . 8080;
        // The Host header still needs to match the real request.
        $options['headers']['Host'] = $uri['host'];
        $options['headers']['Host'] .= isset($uri['port']) && $uri['port'] != 80 ? ':' . $uri['port'] : '';
        break;

      case 'http':
      case 'feed':
        $port = isset($uri['port']) ? $uri['port'] : 80;
        $socket = 'tcp://' . $uri['host'] . ':' . $port;
        // RFC 2616: "non-standard ports MUST, default ports MAY be included".
        // We don't add the standard port to prevent from breaking rewrite rules
        // checking the host that do not take into account the port number.
        $options['headers']['Host'] = $uri['host'] . ($port != 80 ? ':' . $port : '');
        break;

      case 'https':
        // Note: Only works when PHP is compiled with OpenSSL support.
        $port = isset($uri['port']) ? $uri['port'] : 443;
        $socket = 'ssl://' . $uri['host'] . ':' . $port;
        $options['headers']['Host'] = $uri['host'] . ($port != 443 ? ':' . $port : '');
        break;

      default:
        $result->error = 'invalid schema ' . $uri['scheme'];
        $result->code = -1003;
        return $result;
    }

    if (empty($options['context'])) {
      $fp = @stream_socket_client($socket, $errno, $errstr, $options['timeout']);
    }
    else {
      // Create a stream with context. Allows verification of a SSL certificate.
      $fp = @stream_socket_client($socket, $errno, $errstr, $options['timeout'], STREAM_CLIENT_CONNECT, $options['context']);
    }

    // Make sure the socket opened properly.
    if (!$fp) {
      // When a network error occurs, we use a negative number so it does not
      // clash with the HTTP status codes.
      $result->code = -$errno;
      $result->error = trim($errstr) ? trim($errstr) : t('Error opening socket @socket', array('@socket' => $socket));

      return $result;
    }

    // Construct the path to act on.
    $path = isset($uri['path']) ? $uri['path'] : '/';
    if (isset($uri['query'])) {
      $path .= '?' . $uri['query'];
    }

    // Only add Content-Length if we actually have any content or if it is a POST
    // or PUT request. Some non-standard servers get confused by Content-Length in
    // at least HEAD/GET requests, and Squid always requires Content-Length in
    // POST/PUT requests.
    $content_length = strlen($options['data']);
    if ($content_length > 0 || $options['method'] == 'POST' || $options['method'] == 'PUT') {
      $options['headers']['Content-Length'] = $content_length;
    }

    // If the server URL has a user then attempt to use basic authentication.
    if (isset($uri['user'])) {
      $options['headers']['Authorization'] = 'Basic ' . base64_encode($uri['user'] . (isset($uri['pass']) ? ':' . $uri['pass'] : ':'));
    }

    $request = $options['method'] . ' ' . $path . " HTTP/1.0\r\n";
    foreach ($options['headers'] as $name => $value) {
      $request .= $name . ': ' . trim($value) . "\r\n";
    }
    $request .= "\r\n" . $options['data'];
    $result->request = $request;
    // Calculate how much time is left of the original timeout value.
    $timeout = $options['timeout'] - self::timer_read(__FUNCTION__) / 1000;
    if ($timeout > 0) {
      stream_set_timeout($fp, floor($timeout), floor(1000000 * fmod($timeout, 1)));
      fwrite($fp, $request);
    }

    // Fetch response. Due to PHP bugs like http://bugs.php.net/bug.php?id=43782
    // and http://bugs.php.net/bug.php?id=46049 we can't rely on feof(), but
    // instead must invoke stream_get_meta_data() each iteration.
    $info = stream_get_meta_data($fp);
    $alive = !$info['eof'] && !$info['timed_out'];
    $response = '';

    while ($alive) {
      // Calculate how much time is left of the original timeout value.
      $timeout = $options['timeout'] - self::timer_read(__FUNCTION__) / 1000;
      if ($timeout <= 0) {
        $info['timed_out'] = TRUE;
        break;
      }
      stream_set_timeout($fp, floor($timeout), floor(1000000 * fmod($timeout, 1)));
      $chunk = fread($fp, 1024);
      $response .= $chunk;
      $info = stream_get_meta_data($fp);
      $alive = !$info['eof'] && !$info['timed_out'] && $chunk;
    }
    fclose($fp);

    if ($info['timed_out']) {
      $result->code = -1;
      $result->error = 'request timed out';
      return $result;
    }
    // Parse response headers from the response body.
    // Be tolerant of malformed HTTP responses that separate header and body with
    // \n\n or \r\r instead of \r\n\r\n.
    list($response, $result->data) = preg_split("/\r\n\r\n|\n\n|\r\r/", $response, 2);
    $response = preg_split("/\r\n|\n|\r/", $response);

    // Parse the response status line.
    $response_status_array = self::parse_response_status(trim(array_shift($response)));
    $result->protocol = $response_status_array['http_version'];
    $result->status_message = $response_status_array['reason_phrase'];
    $code = $response_status_array['response_code'];

    $result->headers = array();

    // Parse the response headers.
    while ($line = trim(array_shift($response))) {
      list($name, $value) = explode(':', $line, 2);
      $name = strtolower($name);
      if (isset($result->headers[$name]) && $name == 'set-cookie') {
        // RFC 2109: the Set-Cookie response header comprises the token Set-
        // Cookie:, followed by a comma-separated list of one or more cookies.
        $result->headers[$name] .= ',' . trim($value);
      }
      else {
        $result->headers[$name] = trim($value);
      }
    }

    $responses = array(
      100 => 'Continue',
      101 => 'Switching Protocols',
      200 => 'OK',
      201 => 'Created',
      202 => 'Accepted',
      203 => 'Non-Authoritative Information',
      204 => 'No Content',
      205 => 'Reset Content',
      206 => 'Partial Content',
      300 => 'Multiple Choices',
      301 => 'Moved Permanently',
      302 => 'Found',
      303 => 'See Other',
      304 => 'Not Modified',
      305 => 'Use Proxy',
      307 => 'Temporary Redirect',
      400 => 'Bad Request',
      401 => 'Unauthorized',
      402 => 'Payment Required',
      403 => 'Forbidden',
      404 => 'Not Found',
      405 => 'Method Not Allowed',
      406 => 'Not Acceptable',
      407 => 'Proxy Authentication Required',
      408 => 'Request Time-out',
      409 => 'Conflict',
      410 => 'Gone',
      411 => 'Length Required',
      412 => 'Precondition Failed',
      413 => 'Request Entity Too Large',
      414 => 'Request-URI Too Large',
      415 => 'Unsupported Media Type',
      416 => 'Requested range not satisfiable',
      417 => 'Expectation Failed',
      500 => 'Internal Server Error',
      501 => 'Not Implemented',
      502 => 'Bad Gateway',
      503 => 'Service Unavailable',
      504 => 'Gateway Time-out',
      505 => 'HTTP Version not supported',
    );
    // RFC 2616 states that all unknown HTTP codes must be treated the same as the
    // base code in their class.
    if (!isset($responses[$code])) {
      $code = floor($code / 100) * 100;
    }
    $result->code = $code;

    switch ($code) {
      case 200: // OK
      case 304: // Not modified
        break;
      case 301: // Moved permanently
      case 302: // Moved temporarily
      case 307: // Moved temporarily
        $location = $result->headers['location'];
        $options['timeout'] -= self::timer_read(__FUNCTION__) / 1000;
        if ($options['timeout'] <= 0) {
          $result->code = -1;
          $result->error = 'request timed out';
        }
        elseif ($options['max_redirects']) {
          // Redirect to the new location.
          $options['max_redirects']--;
          $result = self::http_request($location, $options);
          $result->redirect_code = $code;
        }
        if (!isset($result->redirect_url)) {
          $result->redirect_url = $location;
        }
        break;
      default:
        $result->error = $result->status_message;
    }

    return $result;
  }

  /**
   * Ported and modified function from Drupal 7 core.
   *
   * Splits an HTTP response status line into components.
   *
   * See the @link http://www.w3.org/Protocols/rfc2616/rfc2616-sec6.html status
   * line definition @endlink in RFC 2616.
   *
   * @param string $response
   *   The response status line, for example 'HTTP/1.1 500 Internal Server Error'.
   *
   * @return array
   *   Keyed array containing the component parts. If the response is malformed,
   *   all possible parts will be extracted. 'reason_phrase' could be empty.
   *   Possible keys:
   *   - 'http_version'
   *   - 'response_code'
   *   - 'reason_phrase'
   */
  public static function parse_response_status($response) {
    $response_array = explode(' ', trim($response), 3);
    // Set up empty values.
    $result = array(
      'reason_phrase' => '',
    );
    $result['http_version'] = $response_array[0];
    $result['response_code'] = $response_array[1];
    if (isset($response_array[2])) {
      $result['reason_phrase'] = $response_array[2];
    }
    return $result;
  }

  /**
   * Ported and modified function from Drupal 7 core.
   *
   * Helper function for determining hosts excluded from needing a proxy.
   *
   * @return string $host
   *   TRUE if a proxy should be used for this host.
   */
  public static function http_use_proxy($host) {
    $proxy_exceptions = array('localhost', '127.0.0.1');
    return !in_array(strtolower($host), $proxy_exceptions, TRUE);
  }

  /**
   * Ported and modified function from Drupal 7 core.
   *
   * Starts the timer with the specified name.
   *
   * If you start and stop the same timer multiple times, the measured intervals
   * will be accumulated.
   *
   * @param $name
   *   The name of the timer.
   */
  public static function timer_start($name) {
    global $timers;

    $timers[$name]['start'] = microtime(TRUE);
    $timers[$name]['count'] = isset($timers[$name]['count']) ? ++$timers[$name]['count'] : 1;
  }

  /**
   * Ported and modified function from Drupal 7 core.
   *
   * Reads the current timer value without stopping the timer.
   *
   * @param $name
   *   The name of the timer.
   *
   * @return string
   *   The current timer value in ms.
   */
  public static function timer_read($name) {
    global $timers;

    if (isset($timers[$name]['start'])) {
      $stop = microtime(TRUE);
      $diff = round(($stop - $timers[$name]['start']) * 1000, 2);

      if (isset($timers[$name]['time'])) {
        $diff += $timers[$name]['time'];
      }
      return $diff;
    }

    return $timers[$name]['time'];
  }

  /**
   * Ported and modified function from Drupal 7 core.
   *
   * Stops the timer with the specified name.
   *
   * @param $name
   *   The name of the timer.
   *
   * @return
   *   A timer array. The array contains the number of times the timer has been
   *   started and stopped (count) and the accumulated timer value in ms (time).
   */
  public static function timer_stop($name) {
    global $timers;

    if (isset($timers[$name]['start'])) {
      $stop = microtime(TRUE);
      $diff = round(($stop - $timers[$name]['start']) * 1000, 2);
      if (isset($timers[$name]['time'])) {
        $timers[$name]['time'] += $diff;
      }
      else {
        $timers[$name]['time'] = $diff;
      }
      unset($timers[$name]['start']);
    }

    return $timers[$name];
  }

  /**
   * Ported and modified function from Drupal 7 core.
   *
   * Calculates a base-64 encoded, URL-safe sha-256 hmac.
   *
   * @param string $data
   *   String to be validated with the hmac.
   * @param string $key
   *   A secret string key.
   *
   * @return string
   *   A base-64 encoded sha-256 hmac, with + replaced with -, / with _ and
   *   any = padding characters removed.
   */
  public static function hmac_base64($data, $key) {
    // Casting $data and $key to strings here is necessary to avoid empty string
    // results of the hash function if they are not scalar values. As this
    // function is used in security-critical contexts like token validation it is
    // important that it never returns an empty string.
    $hmac = base64_encode(hash_hmac('sha256', (string) $data, (string) $key, TRUE));
    // Modify the hmac so it's safe to use in URLs.
    return strtr($hmac, array('+' => '-', '/' => '_', '=' => ''));
  }

  /**
   * Ported and modified function from Drupal 7 core.
   *
   * Returns a string of highly randomized bytes (over the full 8-bit range).
   *
   * This function is better than simply calling mt_rand() or any other built-in
   * PHP function because it can return a long string of bytes (compared to < 4
   * bytes normally from mt_rand()) and uses the best available pseudo-random
   * source.
   *
   * @param $count
   *  The number of characters (bytes) to return in the string.
   *
   * @return string
   *  Returns a string of highly randomized bytes (over the full 8-bit range).
   */
  public static function random_bytes($count) {
    static $random_state, $bytes, $has_openssl;

    $missing_bytes = $count - strlen($bytes);

    if ($missing_bytes > 0) {
      // PHP versions prior 5.3.4 experienced openssl_random_pseudo_bytes()
      // locking on Windows and rendered it unusable.
      if (!isset($has_openssl)) {
        $has_openssl = version_compare(PHP_VERSION, '5.3.4', '>=') && function_exists('openssl_random_pseudo_bytes');
      }

      // openssl_random_pseudo_bytes() will find entropy in a system-dependent
      // way.
      if ($has_openssl) {
        $bytes .= openssl_random_pseudo_bytes($missing_bytes);
      }

      // Else, read directly from /dev/urandom, which is available on many *nix
      // systems and is considered cryptographically secure.
      elseif ($fh = @fopen('/dev/urandom', 'rb')) {
        // PHP only performs buffered reads, so in reality it will always read
        // at least 4096 bytes. Thus, it costs nothing extra to read and store
        // that much so as to speed any additional invocations.
        $bytes .= fread($fh, max(4096, $missing_bytes));
        fclose($fh);
      }

      // If we couldn't get enough entropy, this simple hash-based PRNG will
      // generate a good set of pseudo-random bytes on any system.
      // Note that it may be important that our $random_state is passed
      // through hash() prior to being rolled into $output, that the two hash()
      // invocations are different, and that the extra input into the first one -
      // the microtime() - is prepended rather than appended. This is to avoid
      // directly leaking $random_state via the $output stream, which could
      // allow for trivial prediction of further "random" numbers.
      if (strlen($bytes) < $count) {
        // Initialize on the first call. The contents of $_SERVER includes a mix of
        // user-specific and system information that varies a little with each page.
        if (!isset($random_state)) {
          $random_state = print_r($_SERVER, TRUE);
          if (function_exists('getmypid')) {
            // Further initialize with the somewhat random PHP process ID.
            $random_state .= getmypid();
          }
          $bytes = '';
        }

        do {
          $random_state = hash('sha256', microtime() . mt_rand() . $random_state);
          $bytes .= hash('sha256', mt_rand() . $random_state, TRUE);
        } while (strlen($bytes) < $count);
      }
    }

    $output = substr($bytes, 0, $count);
    $bytes = substr($bytes, $count);

    return $output;
  }

  /**
   * Ensures the private key variable used to generate tokens is set.
   *
   * @return string
   *   The private key.
   */
  public static function get_private_key() {
    // TODO: Provide ability to use own key.
    $key = self::random_key();
    return $key;
  }

  /**
   * Returns a URL-safe, base64 encoded string of highly randomized bytes
   * (over the full 8-bit range).
   *
   * @param $byte_count
   *   The number of random bytes to fetch and base64 encode.
   *
   * @return string
   *   The base64 encoded result will have a length of up to 4 * $byte_count.
   */
  public static function random_key($byte_count = 32) {
    return self::base64_encode(self::random_bytes($byte_count));
  }

  /**
   * Returns a URL-safe, base64 encoded version of the supplied string.
   *
   * @param $string
   *   The string to convert to base64.
   *
   * @return string
   */
  public static function base64_encode($string) {
    $data = base64_encode($string);
    // Modify the output so it's safe to use in URLs.
    return strtr($data, array('+' => '-', '/' => '_', '=' => ''));
  }
}
