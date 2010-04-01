<?php
/****************************************************************************
 * Copyright 2002, Tero Tilus <tero@tilus.net>
 *
 * This php class is distributed under the terms of the GNU General
 * Public License <http://www.gnu.org/licenses/gpl.html> and WITHOUT
 * ANY WARRANTY.
 **/

include('lib/Settings.class.php');

/****************************************************************************
 * Class for managing application-wide settings of henkari
 **/
class henkariSettings extends Settings {

  // Create settings and return array containing them.  This gets
  // called only once during single run of application.
  function makeSettings($param) {
    // This function is defined in main script.
    return makeHenkariSettings();
  }
}
