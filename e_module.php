<?php
/**
 * @file
 * This file is loaded every time the core of e107 is included. ie. Wherever
 * you see require_once("class2.php") in a script. It allows a developer to
 * modify or define constants, parameters etc. which should be loaded prior to
 * the header or anything that is sent to the browser as output. It may also be
 * included in Ajax calls.
 */

e107_require_once(e_PLUGIN . 'nodejs/classes/nodejs.main.php');

// Register events.
$event = e107::getEvent();
$event->register('logout', 'nodejs_event_logout_callback');

if (session_status() == PHP_SESSION_NONE) {
  session_start();
}

// Update session in database.
nodejs_session_db_handler();

/**
 * User logout listener.
 *
 * @param array $data
 *  The users IP address.
 */
function nodejs_event_logout_callback($data) {
  if (isset($_SESSION['nodejs_config']['authToken'])) {
    $res = nodejs_logout_user($_SESSION['nodejs_config']['authToken']);
  }
}
