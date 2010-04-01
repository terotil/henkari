<?php
/****************************************************************************
 * Copyright 2002, Tero Tilus <tero@tilus.net>
 *
 * This php class is distributed under the terms of the GNU General
 * Public License <http://www.gnu.org/licenses/gpl.html> and WITHOUT
 * ANY WARRANTY.
 **/

/****************************************************************************
 * CGI related functions
 **/ 
class CGI {

  /**************************************************************************
   * Return serialized version of $array
   * $array = 'get', 'post', 'cookie', 'env', 'server'
   */
  function getSerialized($array) {
    if ( version_compare(phpversion(), '4.1.0', '<') ) {
      switch ( $array ) {
      case 'get':
	global $HTTP_GET_VARS;
	return serialize($HTTP_GET_VARS);
	break;
      case 'post':
	global $HTTP_POST_VARS;
	return serialize($HTTP_POST_VARS);
	break;
      case 'cookie':
	global $HTTP_COOKIE_VARS;
	return serialize($HTTP_COOKIE_VARS);
	break;
      case 'env':
	global $HTTP_ENV_VARS;
	return serialize($HTTP_ENV_VARS);
	break;
      case 'server':
	global $HTTP_SERVER_VARS;
	return serialize($HTTP_SERVER_VARS);
	break;
      default:
	return '';
      }
    } else {
      switch ( $array ) {
      case 'get':
	return serialize($_GET);
	break;
      case 'post':
	return serialize($_POST);
	break;
      case 'cookie':
	return serialize($_COOKIE);
	break;
      case 'env':
	return serialize($_ENV);
	break;
      case 'server':
	return serialize($_SERVER);
	break;
      default:
	return '';
      }
    }
  }

  /**************************************************************************
   * Helper for defaulted array-retrieval from global namespace.  If
   * array named $array exists in global namespace and it has key
   * $element, return the value of the corresponding element.
   * Otherwise return $default.
   *
   * FIXME: Somehow this doesn't work with PHP 4.1.2
   **/
  function safeLookup($array, $element, $default=null) {
    global $$array;
    if ( is_array($$array) &&
	 isset(${$array}[$element]) ) {
      return ${$array}[$element];
    } else {
      return $default;
    }
  }

  /**************************************************************************
   * Helper for defaulted array-retrieval from GET variables.
   **/
  function safeLookupGet($element, $default=null) {
    if ( version_compare(phpversion(), '4.1.0', '<') ) {
      global $HTTP_GET_VARS;
      if ( isset($HTTP_GET_VARS[$element]) ) {
	return $HTTP_GET_VARS[$element];
      }
    } else {
      if ( isset($_GET[$element]) ) {
	return $_GET[$element];
      }
    }
    return $default;
  }

  /**************************************************************************
   * Helper for defaulted array-retrieval from POST variables.
   **/
  function safeLookupPost($element, $default=null) {
    if ( version_compare(phpversion(), '4.1.0', '<') ) {
      global $HTTP_POST_VARS;
      if ( isset($HTTP_POST_VARS[$element]) ) {
	return $HTTP_POST_VARS[$element];
      }
    } else {
      if ( isset($_POST[$element]) ) {
	return $_POST[$element];
      }
    }
    return $default;
  }

  /**************************************************************************
   * Helper for defaulted array-retrieval from GET/POST variables.
   **/
  function safeLookupGP($element, $default=null) {
    if ( ($retval = CGI::safeLookupGet($element, $default)) === $default ) {
      return CGI::safeLookupPost($element, $default);
    } else {
      return $retval;
    }
  }

  /**************************************************************************
   * Helper for defaulted array-retrieval from SERVER variables.
   **/
  function safeLookupS($element, $default=null) {
    if ( version_compare(phpversion(), '4.1.0', '<') ) {
      global $HTTP_SERVER_VARS;
      if ( isset($HTTP_SERVER_VARS[$element]) ) {
	return $HTTP_SERVER_VARS[$element];
      }
    } else {
      if ( isset($_SERVER[$element]) ) {
	return $_SERVER[$element];
      }
    }
    return $default;
  }
}
?>
