<?php
/***********************************************************************
 * Copyright 2002, Tero Tilus <tero@tilus.net>
 *
 * This php class is distributed under the terms of the GNU General
 * Public License <http://www.gnu.org/licenses/gpl.html> and WITHOUT
 * ANY WARRANTY.
 **/

/***********************************************************************
 * Singleton class designed to hold an bunch of key-value pairs.
 * Inherit this and override makeSettings() to create you own Settings
 * -class.
 *
 **/
class Settings {
  /*********************************************************************
   * Debug output.  Toggling output on and off and printting are done
   * with the same function.  Boolean value enables or disables output
   * and all the rest is printed if debug output is on.
   **/
  function debug($output) {
    static $debug = false;
    if ( gettype($output) === 'boolean') {
      $debug = $output;
    } else {
      if ( $debug ) { print_r($output); }
    }
  }

  /*********************************************************************
   * Constructor.  No need to override.  Calls makeSettings to obtain
   * initial values for settings only when class is first time
   * instantiated.
   **/
  function Settings($param=null) {
    $this->debug($param);
    $this->debug("Settings::Settings()\n");
    static $requires_instantiation = true;
    if ( $requires_instantiation ) {
      $this->_updateSettings_($this->makeSettings($param));
      $requires_instantiation = false;
    }
  }

  /*********************************************************************
   * This function is used only internally.  Setting and getting
   * values is made with $this->{Get|Set} functions.  Settings are
   * stored in static scope of this function.
   *
   * If $new_setting is an array, sets static $settings array to
   * $new_settings.  Returns array containing settings.
   **/
  function _updateSettings_($new_settings=null) {
    $this->debug("Settings::_updateSettings_()\n");
    $this->debug($new_settings);
    static $settings = null;
    if ( is_array($new_settings) ) {
      $settings = $new_settings;
    }
    return $settings;
  }

  /*********************************************************************
   * Override this to return an array containing your own defaults.
   * makeSettings() is called only when class is instantiated first
   * time.  
   **/
  function makeSettings($param) {
    $this->debug("Settings::makeSettings()\n");
    $this->debug($param);
    return array();
  }

  /*********************************************************************
   * Set the the value of variable of name $variable to $value.
   **/
  function Set($variable, $value) {
    $this->debug("Settings::Set('$variable', '$value')\n");
    $tmp_settings = $this->_updateSettings_();
    $tmp_settings[$variable] = $value;
    $this->_updateSettings_($tmp_settings);
  }

  /*********************************************************************
   * Get the value of variable of name $variable.
   **/
  function Get($variable) {
    $this->debug("Settings::Get('$variable')\n");
    $tmp_settings = $this->_updateSettings_();
    if ( is_array($tmp_settings) && 
	 isset($tmp_settings[$variable]) ) {
      return $tmp_settings[$variable];
    } else {
      return null;
    }
  }
}
?>
