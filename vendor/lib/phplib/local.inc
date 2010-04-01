<?php
/*
 * Session Management for PHP3
 *
 * Copyright (c) 1998-2000 NetUSE AG
 *                    Boris Erdmann, Kristian Koehntopp
 *
 * $Id: local.inc,v 1.3 2000/07/12 18:25:43 kk Exp $
 *
 */ 

class DB_Example extends DB_Sql {
  var $Host     = "localhost";
  var $Database = "example_database";
  var $User     = "example_user";
  var $Password = "";
}

class Example_CT_Sql extends CT_Sql {
  var $database_class = "DB_Example";          ## Which database to connect...
  var $database_table = "active_sessions"; ## and find our session data in this table.
}

#class Example_CT_Split_Sql extends CT_Split_Sql {
#  var $database_class = "DB_Example";         ## Which database to connect...
#  var $database_table = "active_sessions_split"; ## and find our session data in this table.
#  var $split_length = 4096                    ## Split rows every 4096 bytes
#}

#class Example_CT_Shm extends CT_Shm {
#  var $max_sessions   = 500;               ## number of maximum sessions
#  var $shm_key        = 0x123754;          ## unique shm identifier
#  var $shm_size       = 64000;             ## size of segment
#}

#class Example_CT_Ldap extends CT_Ldap {
#   var $ldap_host = "localhost";
#   var $ldap_port = 389;
#   var $basedn    = "dc=your-domain, dc=com";
#   var $rootdn    = "cn=root, dc=your-domain, dc=com";
#   var $rootpw    = "secret";
#   var $objclass  = "phplibdata";
#}

#class Example_CT_Dbm extends CT_DBM {
#   var $dbm_file  = "must_exist.dbm";
#}

class Example_Session extends Session {
  var $classname = "Example_Session";

  var $cookiename     = "";                ## defaults to classname
  var $magic          = "Hocuspocus";      ## ID seed
  var $mode           = "cookie";          ## We propagate session IDs with cookies
  var $fallback_mode  = "get";
  var $lifetime       = 0;                 ## 0 = do session cookies, else minutes
  var $that_class     = "Example_CT_Sql"; ## name of data storage container
  var $gc_probability = 5;  
}

class Example_User extends User {
  var $classname = "Example_User";

  var $magic          = "Abracadabra";     ## ID seed
  var $that_class     = "Example_CT_Sql"; ## data storage container
}

class Example_Auth extends Auth {
  var $classname      = "Example_Auth";

  var $lifetime       =  15;

  var $database_class = "DB_Example";
  var $database_table = "auth_user";
  
  function auth_loginform() {
    global $sess;
    global $_PHPLIB;

    include($_PHPLIB["libdir"] . "loginform.ihtml");
  }
  
  function auth_validatelogin() {
    global $username, $password;

    if(isset($username)) {
      $this->auth["uname"]=$username;        ## This provides access for "loginform.ihtml"
    }
    
    
    $uid = false;
    
    $this->db->query(sprintf("select user_id, perms ".
                             "        from %s ".
                             "       where username = '%s' ".
                             "         and password = '%s'",
                          $this->database_table,
                          addslashes($username),
                          addslashes($password)));

    while($this->db->next_record()) {
      $uid = $this->db->f("user_id");
      $this->auth["perm"] = $this->db->f("perms");
    }
    return $uid;
  }
}

class Example_Default_Auth extends Example_Auth {
  var $classname = "Example_Default_Auth";
  
  var $nobody    = true;
}

class Example_Challenge_Auth extends Auth {
  var $classname      = "Example_Challenge_Auth";

  var $lifetime       =  1;

  var $magic          = "Simsalabim";  ## Challenge seed
  var $database_class = "DB_Example";
  var $database_table = "auth_user";

  function auth_loginform() {
    global $sess;
    global $challenge;
    global $_PHPLIB;
    
    $challenge = md5(uniqid($this->magic));
    $sess->register("challenge");
    
    include($_PHPLIB["libdir"] . "crloginform.ihtml");
  }
  
  function auth_validatelogin() {
    global $username, $password, $challenge, $response;

    if(isset($username)) {
      $this->auth["uname"]=$username;        ## This provides access for "loginform.ihtml"
    }
    $this->db->query(sprintf("select user_id,perms,password ".
                "from %s where username = '%s'",
                          $this->database_table,
                          addslashes($username)));

    while($this->db->next_record()) {
      $uid   = $this->db->f("user_id");
      $perm  = $this->db->f("perms");
      $pass  = $this->db->f("password");
    }
    $exspected_response = md5("$username:$pass:$challenge");

    ## True when JS is disabled
    if ($response == "") {
      if ($password != $pass) {
        return false;
      } else {
        $this->auth["perm"] = $perm;
        return $uid;
      }
    }
    
    ## Response is set, JS is enabled
    if ($exspected_response != $response) {
      return false;
    } else {
      $this->auth["perm"] = $perm;
      return $uid;
    }
  }
}

##
## Example_Challenge_Crypt_Auth: Keep passwords in md5 hashes rather 
##                           than cleartext in database
## Author: Jim Zajkowski <jim@jimz.com>

class Example_Challenge_Crypt_Auth extends Auth {
  var $classname      = "Example_Challenge_Crypt_Auth";

  var $lifetime       =  1;

  var $magic          = "Frobozzica";  ## Challenge seed
  var $database_class = "DB_Example";
  var $database_table = "auth_user_md5";

  function auth_loginform() {
    global $sess;
    global $challenge;
    
    $challenge = md5(uniqid($this->magic));
    $sess->register("challenge");
    
    include("crcloginform.ihtml");
  }
  
  function auth_validatelogin() {
    global $username, $password, $challenge, $response;

    $this->auth["uname"]=$username;        ## This provides access for "loginform.ihtml"
    
    $this->db->query(sprintf("select user_id,perms,password ".
                "from %s where username = '%s'",
                          $this->database_table,
                          addslashes($username)));

    while($this->db->next_record()) {
      $uid   = $this->db->f("user_id");
      $perm  = $this->db->f("perms");
      $pass  = $this->db->f("password");   ## Password is stored as a md5 hash
    }
    $exspected_response = md5("$username:$pass:$challenge");

    ## True when JS is disabled
    if ($response == "") {
      if (md5($password) != $pass) {       ## md5 hash for non-JavaScript browsers
        return false;
      } else {
        $this->auth["perm"] = $perm;
        return $uid;
      }
    }
    
    ## Response is set, JS is enabled
    if ($exspected_response != $response) {
      return false;
    } else {
      $this->auth["perm"] = $perm;
      return $uid;
    }
  }
}

class Example_Perm extends Perm {
  var $classname = "Example_Perm";
  
  var $permissions = array(
                            "user"       => 1,
                            "author"     => 2,
                            "editor"     => 4,
                            "supervisor" => 8,
                            "admin"      => 16
                          );

  function perm_invalid($does_have, $must_have) {
    global $perm, $auth, $sess;
    global $_PHPLIB;
    
    include($_PHPLIB["libdir"] . "perminvalid.ihtml");
  }
}

##
## Example_Menu may extend Menu.
## If you name this class differently, you must also
## rename its constructor function - this is a PHP language 
## design stupidity.
##
## To use this, you must enable the require statement for
## menu.inc in prepend.php3.
##

# class Example_Menu extends Menu {
#   var $classname = "Example_Menu";
#
#   # Map of PHP_SELF URL strings to menu positions
#   var $urlmap = array(
#     "/menu/index.php3"   => "",
#     "/menu/item1.php3"   => "/1",
#     "/menu/item11.php3"  => "/1/1",
#     "/menu/item12.php3"  => "/1/2",
#     "/menu/item13.php3"  => "/1/3",
#     "/menu/item2.php3"   => "/2",
#     "/menu/item21.php3"  => "/2/1",
#     "/menu/item22.php3"  => "/2/2",
#     "/menu/item221.php3" => "/2/2/1",
#     "/menu/item222.php3" => "/2/2/2",
#     "/menu/item23.php3"  => "/2/3",
#     "/menu/item24.php3"  => "/2/4"
#   );
#   
#   # Information about each menu item
#   var $item = array(
#     ""      => array("title" => "Main"),
#     "/1"    => array("title" => "Text 1"),
#     "/1/1"  => array("title" => "Text 1.1"),
#     "/1/2"  => array("title" => "Text 1.2"),
#     "/1/3"  => array("title" => "Text 1.3"),
#     "/2"    => array("title" => "Text 2"),
#     "/2/1"  => array("title" => "Text 2.1"),
#     "/2/2"  => array("title" => "Text 2.2"),
#     "/2/2/1"=> array("title" => "Text 2.2.1"),
#     "/2/2/2"=> array("title" => "Text 2.2.2"),
#     "/2/3"  => array("title" => "Text 2.3"),
#     "/2/4"  => array("title" => "Text 2.4")
#   );
#   
#   function Example_Menu() {
#     $this->setup();
#   }
# }

?>
