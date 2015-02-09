<?php
/**
 * @file
 * This file is loaded every time the core of e107 is included. ie. Wherever
 * you see require_once("class2.php") in a script. It allows a developer to
 * modify or define constants, parameters etc. which should be loaded prior to
 * the header or anything that is sent to the browser as output. It may also be
 * included in Ajax calls.
 */

// We include this plugin file every time, so we provide the ability to use
// Nodejs class for other plugins without file including.
require_once('classes/nodejs.main.class.php');

// Register events.
$event = e107::getEvent();
$event->register('login', 'nodejs_event_login_callback');
$event->register('logout', 'nodejs_event_logout_callback');

// Update last seen on every page load.
if (e107::getUser()->isUser()) {
  $sql = e107::getDb('nodejssessions');
  $sql->update('nodejs_sessions', 'timestamp=' . time());
}

/**
 * User login listener.
 *
 * @param array $data
 *  Array
 * (
 *   [user_id] => 1
 *   [user_name] => DisplayName
 *   [class_list] => 4,253,251,0,254,250
 *   [remember_me] => 0
 *   [user_admin] => 1
 *   [user_email] => useremail@asite.dev
 * )
 */
function usersession_on_user_login($data) {
  if (session_status() == PHP_SESSION_NONE) {
    session_start();
  }

  $user = e107::getSystemUser($data['user_id']);

  $insert = array(
    'uid' => $user->getId(),
    'sid' => session_id(),
    'timestamp' => time(),
  );

  $db = e107::getDb('nodejssessions');

  // Insert/replace if there is a current record.
  $db->replace('nodejs_sessions', $insert);
}

/**
 * User logout listener.
 *
 * @param array $data
 *  The users IP address.
 */
function nodejs_event_logout_callback($data) {
  if (isset($_SESSION['nodejs_config']['authToken'])) {
    Nodejs::logout_user($_SESSION['nodejs_config']['authToken']);
  }

  // Current user data is still available.
  $user = e107::getUser();

  $db = e107::getDb('nodejssessions');

  // Remove the DB session.
  $db->delete('nodejs_sessions', 'uid=' . $user->getId());
}
