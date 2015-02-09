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
$event->register('logout', 'nodejs_event_logout_callback');

if (session_status() == PHP_SESSION_NONE) {
  session_start();
}

$db = e107::getDb('nodejssessions');

// Update last seen on every page load.
$updated = $db->update('nodejs_sessions', 'timestamp=' . time() . ' WHERE sid=' . session_id());

// If no updated record.
if (!$updated) {
  $insert = array(
    'uid' => USERID,
    'sid' => session_id(),
    'timestamp' => time(),
  );

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
}
