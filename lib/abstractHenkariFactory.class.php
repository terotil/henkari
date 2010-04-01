<?php
/****************************************************************************
 * Copyright 2002, Tero Tilus <tero@tilus.net>
 *
 * This php class is distributed under the terms of the GNU General
 * Public License <http://www.gnu.org/licenses/gpl.html> and WITHOUT
 * ANY WARRANTY.
 **/

/****************************************************************************
 * Abstract baseclass for instantiators (factories)
 **/
class abstractHenkariFactory {
  // Constructor
  function abstractHenkariFactory($libdir='lib/') {
    $this->librarydir = $libdir;
  }

  // Default name for file where class of given name resides
  function getDefaultClassfile($classname) {
    return $this->librarydir . "$classname.class.php";
  }
}

?>
