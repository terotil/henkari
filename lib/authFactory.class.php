<?php
/****************************************************************************
 * Copyright 2002, Tero Tilus <tero@tilus.net>
 *
 * This php class is distributed under the terms of the GNU General
 * Public License <http://www.gnu.org/licenses/gpl.html> and WITHOUT
 * ANY WARRANTY.
 **/
require_once('lib/abstractHenkariFactory.class.php');
require_once('lib/abstractAuth.class.php');
require_once('lib/errorHandler.class.php');

/****************************************************************************
 * Base class for authentication manager
 **/

class authFactory extends abstractHenkariFactory {

  // Constructor
  function authFactory() {
    $this->abstractHenkariFactory();
    $this->authclass = '';
    $this->authclassfile = '';
    $this->authinstance = null;
  }

  function registerAuth($classname, $classfile='') {
    $this->authclass = $classname;
    if ( $classfile == '' ) {
      // Generate default classfile from classname
      $this->authclassfile = $this->getDefaultClassfile($classname);
    } else {
      $this->authclassfile = $classfile;
    }
    // Remove old auth-class instance
    $this->authinstance = null;
  }

  function &getAuth() {
    if ( $this->authinstance != null ) {
      return $this->authinstance;
    } else {
      if ( ($this->authclassfile != '') &&
	   ($this->authclass != '') ) {
	include($this->authclassfile);
	$this->authinstance =& new $this->authclass();
	return $this->authinstance;
      } else {
	// No auth implementation registered.
	return new dummyAuth();
      }
    }
  }

  // Returns an (singleton) instance of authFactory.
  function &getInstance() {
    static $Instance = null;
    if ( $Instance === null ) {
      $Instance = new authFactory();
    }
    return $Instance;
  }
}

class dummyAuth extends abstractAuth {
  function isValidLogin($uname, $pw, $clearpw=true) {
    return false;
  }
  function authenticateUser($uname, $pw, $clearpw=true) {
    $eh = new errorHandler('', '', '');
    $eh->handle('User authentication not available.  No auth implementation registered.');
  }
}

?>
