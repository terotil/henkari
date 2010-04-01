<?php
/***********************************************************************
 * Copyright 2002, Tero Tilus <tero@tilus.net>
 *
 * This php class is distributed under the terms of the GNU General
 * Public License <http://www.gnu.org/licenses/gpl.html> and WITHOUT
 * ANY WARRANTY.
 **/

class MIME {

  // This class has singleton behavior (it gets instantiated only
  // once).  Instead of instantiating MIME directly, obtain instance
  // by calling MIME::getInstance(); Remember to use =& when assigning
  // return value!

  function MIME() {
    $this->generic_type = 'application/octet-stream';
    $this->ext2type = 
      array(
	    // Text
	    'htm'  => 'text/html',
	    'html' => 'text/html',
	    'asc'  => 'text/plain',
	    'txt'  => 'text/plain',
	    'css'  => 'text/css',
	    'tex'  => 'application/x-tex',
	    'ltx'  => 'application/x-latex',
	    'latex'=> 'application/x-latex',
	    'rtf'  => 'text/rtf',
	    'xml'  => 'text/xml',
	    'sgml' => 'text/sgml',
	    'sgm'  => 'text/sgml',
	    // Images
	    'gif'  => 'image/gif',
	    'jpeg' => 'image/jpeg',
	    'jpe'  => 'image/jpeg',
	    'jpg'  => 'image/jpeg',
	    'png'  => 'image/png',
	    'bmp'  => 'image/bmp',
	    'tif'  => 'image/tiff',
	    'tiff' => 'image/tiff',
	    // Audio
	    'wav' => 'audio/wav',
	    'mid' => 'audio/midi',
	    'midi'=> 'audio/midi',
	    'kar' => 'audio/midi',
	    'mp2' => 'audio/mpeg',
	    'mp3' => 'audio/mpeg',
	    'mpga'=> 'audio/mpeg',
	    'ra'  => 'audio/x-realaudio',
	    // Video
	    'avi' => 'video/x-msvideo',
	    'mpeg'=> 'video/mpeg',
	    'mpe' => 'video/mpeg',
	    'mpg' => 'video/mpeg',
	    'qt'  => 'video/quicktime',
	    'mov' => 'video/quicktime',
	    'rm'  => 'application/vnd.rn-realmedia',
	    // Packages
	    'tar'  => 'application/x-tar',
	    'tgz'  => 'application/gzip',
	    'gz'   => 'application/gzip',
	    'hqx'  => 'application/mac-binhex40',
	    'bz2'  => 'application/x-bzip2',
	    'zip'  => 'application/zip',
	    // Application spesific
	    'pdf'  => 'application/pdf',
	    'doc'  => 'application/msword',
	    'eps'  => 'application/postscript',
	    'ps'   => 'application/postscript',
	    'ai'   => 'application/postscript',
	    'dvi'  => 'application/x-dvi',
	    'spl'  => 'application/x-futuresplash',
	    'dcr'  => 'application/x-director',
	    'dir'  => 'application/x-director',
	    'dxr'  => 'application/x-director',
	    'swf'  => 'application/x-shockwave-flash',
	    // Scripts
	    'ejsc' => 'application/x-ejsc',
	    'php'  => 'application/x-httpd-php',
	    'php4' => 'application/x-httpd-php',
	    'php3' => 'application/x-httpd-php',
	    'phps' => 'application/x-httpd-php-source',
	    '##generic##' => $this->generic_type
	    );
    // Not currently needed
    // $this->type2ext = array_flip($this->ext2type);
  }

  // Returns an educated guess on mimetype based on filename.
  function getMimeTypeByFilename($filename) {
    ereg('.*\.([^\.]+)$', $filename, $match);
    if (isset($match[1])) {
      return $this->getMimeTypeByExtension($match[1]);
    } else {
      return $this->getMimeTypeByExtension('##generic##');
    }
  }

  // Returns an educated guess on mimetype based on file extension.
  function getMimeTypeByExtension($extension) {
    // Case insensitive 
    $extension = strtolower($extension);
    if ( isset($this->ext2type[$extension]) ) {
      return $this->ext2type[$extension];
    } else {
      return $this->generic_type;
    }
  }

  // Registers a new extension - MIME-type combination.
  function bindExtensionToType($ext, $type) {
    if ( is_string($ext) and is_string($type) ) {
      // Case insensitive
      $this->ext2type[strtolower($ext)] = strtolower($type);
    }
  }

  // Returns an (singleton) instance of MIME.
  function &getInstance() {
    static $mimeInstance = null;
    if ( $mimeInstance === null ) {
      $mimeInstance = new MIME();
    }
    return $mimeInstance;
  }
}

?>
