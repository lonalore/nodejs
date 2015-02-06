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

// Implements "logout" e107 event triggering.
e107::getEvent()->register('logout', 'nodejs_event_logout_callback');

/**
 * Callback function to handle custom event registration.
 *
 * @param array $data
 *  The users IP address.
 */
function nodejs_event_logout_callback($data) {
  if (isset($_SESSION['nodejs_config']['authToken'])) {
    Nodejs::logout_user($_SESSION['nodejs_config']['authToken']);
  }
}
