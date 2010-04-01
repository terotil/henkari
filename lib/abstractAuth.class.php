<?php
/****************************************************************************
 * Copyright 2002, Tero Tilus <tero@tilus.net>
 *
 * This php class is distributed under the terms of the GNU General
 * Public License <http://www.gnu.org/licenses/gpl.html> and WITHOUT
 * ANY WARRANTY.
 **/

/****************************************************************************
 * Base class for authentication manager
 **/
class abstractAuth {

  // Constructor
  function abstractAuth($hash='md5') {
    $this->hash = $hash;
    $this->users = array();
    $this->uname = false;
  }

  // Only md5 hash supported
  function crypt($str,$salt='') {
    switch ( $this->hash ) {
    case 'md5':
      return md5($str);
    case 'clear':
    default:
      return $str;
    }
  }

  // Do we have this user
  function userExists($uname) {
    return isset($this->users[$uname]);
  }

  // Compare user given password and the one found from user db.
  function isValidLogin($uname, $pw, $clearpw=true) {
    if ( $this->userExists($uname) ) {
      return $this->users[$uname] == (($clearpw)?($this->crypt($pw)):($pw));
    }
    return false;
  }

  // Authentication, success returned.
  function authenticateUser($uname, $pw, $clearpw=true) {
    if ( $this->isValidLogin($uname, $pw, $clearpw) ) {
      $this->uname = $uname;
      // Pages requiring user authentication are not cached
      global $config;
      $config->cache = false;
      return true;
    }
    return false;
  }

  // Add user.  Returns true on success.
  function addUser($uname, $pw, $clearpw=true) {
    if ( $this->userExists($uname) ) {
      // User with given uname already exists.  Can't add user.
      return false;
    }
    // If cleartext password is given it's first encrypted
    $this->users[$uname] = (($clearpw)?($this->crypt($pw)):($pw));
    return true;
  }

  // Change password.  Returns true on success.
  function changePassword($uname, $oldpw, $newpw, $clearpw=true) {
    if ( $this->isValidLogin($uname, $oldpw, $clearpw) ) {
      $this->users[$uname] = (($clearpw)?($this->crypt($newpw)):($newpw));
      return true;
    }
    return false;
  }

  // Returns true if (possibly unverified) login was sent with
  // request.  This method is supposed to tell when a user is assumed
  // to be an administrator.
  function loginRecieved() {
    return false;
  }

  // Was (possibly unverified) login provided?
  function canProvideLogin() {
    return false;
  }
}
?>
