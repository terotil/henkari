<?php
/****************************************************************************
 * Copyright 2002, Tero Tilus <tero@tilus.net>
 *
 * This php class is distributed under the terms of the GNU General
 * Public License <http://www.gnu.org/licenses/gpl.html> and WITHOUT
 * ANY WARRANTY.
 **/

require_once('lib/abstractAuth.class.php');

/****************************************************************************
 * Base class for authentication manager
 **/
class henkariAuth extends abstractAuth {

  // Constructor
  function henkariAuth() {
    $this->abstractAuth();
  }

  // Authentication, success returned.
  function authenticateUser() {
    $ui = UI::getInstance();
    if ( isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW']) &&
	 parent::authenticateUser($_SERVER['PHP_AUTH_USER'], 
				  $_SERVER['PHP_AUTH_PW']) ) {
      // User OK
      return true;
    }
    global $config;
    // Not logged in, do basic http authentication
    header("WWW-Authenticate: Basic realm=\"Henkari :: {$config->root}\"");
    header('HTTP/1.0 401 Unauthorized');
    exit;
    return false;
  }

  // Returns true if (possibly unverified) login was sent with
  // request.  This method is supposed to tell when a user is assumed
  // to be an administrator.
  function canProvideLogin() {
    // Browsers do not send authentication tokens if not issued
    // "WWW-Authenticate: ..."  So we must assume everybody to be
    // admins and ruin wonderfull web-pages with "Edit this page"
    // -links.
    //
    // Desired functionality could be achieved by setting
    // "authenticated" -cookie or something like that.
    return true;
  }

}
