<?php /* 141 Lines */

######################################################################################
# ™ Software                                                                         #
#              _   _   _              ____        ___ _                              #
#    __ _  ___| |_| | | |___   ___™  / ___|  ___ / __| |_  _     _  __ _ _ __  ___   #
#   / _` |/ __  | | | |  __ \ / _ \  \___ \ / _ \| _|| __/| | _ | |/ _` | '__|/ __\  #
#  | (_| | (__| | | |_| |__) | (_) |  ___) | (_) | | | |_ | || || | (_| | |  |  _|   #
#   \__,_|\___,_|_|___|_,___/ \___/  |____/ \___/|_|  \__| \_____/ \__,_|_|   \___/  #
#                                                                                    #
# Copyright © 2018 - all rights reserved                                             #
######################################################################################'

//  ENVIRONMENT SETUP
    ini_set('memory_limit', '-1');
    set_time_limit(0);
    $MODE = '';              // 'CWM' = no Passcode needet OR 'DEMO'
    $defaultCompress = true; // true OR false
    $forceDownload = false;  // true OR false

//////////////////////////////////////////////////////////////////////////////////////
////// BUT DO NOT EDIT ANYTHING IF YOU ARE NOT TOTALY SHURE WHAT YOU ARE DOING! //////
//////////////////////////////////////////////////////////////////////////////////////

// SET LANGUAGE
if ( isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) and
     file_exists('lang/'.substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2).'.php') ) {
   @include_once('lang/'.substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2).'.php');
} else {
  @include_once('lang/en.php');
}

// CHECK PHP VERSION
if ( version_compare(phpversion(), '4.3') < 0 ) {
	die('<br><code><b>'.$i18n['ERROR'].'</b>: '.$i18n['ERROR-PHP'].'</code>');
}

// CHECK GD
if ( !extension_loaded('gd') ) {
	echo '<br><code><b>'.$i18n['ERROR'].'</b>: '.$i18n['ERROR-GD'].'</code><pre>';
	print_r(gd_info());
	die('</pre>');
}

// HELPER
require_once 'assets/lib.php';

// VAR INI
$out = $msg = $pf = '';

// WITH OR WITHOUT PASSWORD AND ENCRYPTION
if ( $MODE == 'CWM' ) $h1 = $i18n['HEADLINE1'];
else {
  $h1 = $i18n['HEADLINE2'];
  $pf = '<br><strong>'.$i18n['Enter-Pass'].'</strong>';
  $pf .= '<input type="text" name="pc" placeholder="'.$i18n['Password'].'" class="text-input-field">';
}

// DEMO?
if ( $MODE == 'DEMO' )
  $h1 .= ' <br><span style="font-size:xx-small;background:crimson;color:white;padding:2px 7px;">'.
         ' <b>'.$i18n['DEMO-MODE'].'</b> &mdash; '.$i18n['NoOwnText'].'</span>';

// IF FORM SUBMIT
if ( @$_REQUEST['submit'] ) {

  // GET DEFAULT TEXT FROM TEMPLATE FOLDER OR READ TEXT FROM FORM
  if ( $_REQUEST['txt'] == '' || $MODE == 'DEMO')
    $txt = @file_get_contents( 'assets/default-text.txt' );
  else $txt = $_REQUEST['txt'];

  // IF PNG GET IMAGE FROM UPLOAD
  $img = @getimagesize( $_FILES['img']['tmp_name'] );
  if ( $img['mime'] == 'image/png' )
    $data = r( $_FILES['img']['tmp_name'] );
  else if ( isset($img['mime'])){
  
  // IF NO PNG, CONVERT AND GET IMAGE FROM CONVERTER
    move_uploaded_file($_FILES['img']['tmp_name'], 'tmp/'.$_FILES['img']['name']);
    require 'assets/converter.php';
    $image_converter  = new ImageConverter([
      'format'        => 'png', 
      'quality'       => 'high', 
      'size'          => 'original', 
      'image_name'    => 'tmp/'.$_FILES['img']['name'], 
      'output_folder' => 'tmp'
    ]);
    $img = $image_converter->convert();
    $data = r( $img );
    $path_parts = pathinfo('tmp/' . $_FILES['img']['name']);
    if ( is_file('tmp/' . $_FILES['img']['name'] ) )
      unlink( 'tmp/' . $_FILES['img']['name'] );
    if ( is_file('tmp/' . $path_parts['filename'].'.png' ) )
      unlink( 'tmp/' . $path_parts['filename'].'.png' );
  }else
  
  // OR GET DEFAULT IMAGE
    $data = r( 'assets/default-image.png' );

  // ENCRYPT TEXT
  if ( $MODE == '' || $MODE == 'DEMO' ) $txt = encodeCombined( $txt, @$_REQUEST['pc'] );

  // WRITE IMAGE WITH TEXT
  $pos = p( $txt, 'image.png', $data );

  // GET TEXT FROM IMAGE
  $text = t( 'image.png', $pos );

  // DECRYPT TEXT
  if ( $MODE == '' || $MODE == 'DEMO' ) $text = decodeCombined( $text, @$_REQUEST['pc'] );

  // PRINT DOWNLOAD OR SHOW IMAGE LINK ($forceDownload: TRUE = DOWNLOAD / FALSE = SHOW)
  if ($forceDownload == true)
  $out = '<br><a target="_blank" download="image.png" '.
         'href="download.php?file=image.png">'.$i18n['Download'].' &rarr;</a><br>';
  else $out = '<br><a target="_blank" href="image.png">'.$i18n['Show'].' &rarr;</a><br>';

  // PRINT PASSWORD & TEXT 
  $out .= '<br><div class="text">';
  if ( $MODE == '' || $MODE == 'DEMO' ) $out .= '<p>'.$i18n['Password'].': ' . @$_REQUEST['pc'] . '</p>';
  $out .= '<p>' . str_replace( "\n", '<br>', $text) . '</p></div>';

  // ERROR IF NO PASSWORD IS GIVEN
  if ( ( $MODE == '' || $MODE == 'DEMO' ) && empty( @$_REQUEST['pc'] ) ) {
    $out = '';
    $msg = '<div id="alert" class="alert"><span class="closebtn" 
      onclick="this.parentElement.style.display=\'none\';">&times;</span>
      <p id="clipboad-feedback">'.$i18n['PassHint'].'</p></div>';
  }
} else
  
  // PRINT HINTS
  $out = @file_get_contents( 'assets/readme.htm' );

// DEFAULT COMPRESS CHECKED?
if ( $defaultCompress === true ) $check = ' checked="yes"';
else $check = '';

// HTML TEMPLATE
require 'assets/index.phtml';
