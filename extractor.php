<?php /* 563 Lines */

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
    $MODE = ''; // 'CWM' = no Passcode needet --- or 'DEMO'
    $defaultCompress = true; // true OR false

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
	if(is_array($i18n))die('<br><code><b>'.$i18n['ERROR'].'</b>: '.$i18n['ERROR-PHP'].'</code>');
	else die('<br><code><b>ERROR</b>: PHP 4.3 or greater is required!</code>');
}

// CHECK GD
if ( !extension_loaded('gd') ) {
	if(is_array($i18n))echo '<br><code><b>'.$i18n['ERROR'].'</b>: '.$i18n['ERROR-GD'].'</code><pre>';
	else echo '<br><code><b>ERROR</b>: GD Lib is required for Image Support!</code><pre>';
	print_r(gd_info());
	die('</pre>');
}

// HELPER
function decodeRand($str, $seed) {
  mt_srand($seed);
  $blocks = explode('-', $str);
  $out = array();
  foreach ($blocks as $block)
    $out[] = chr((intval($block) - mt_rand(350, 16000)) / 3);
  mt_srand();
  return implode('', $out);
}

function decodeStrtr($str, $seed=1234567) {
  $from = '';
  $to = '';
  for ($x=0; $x<256; $x++)
    $from[$x] = chr($x);
  $to = $from;
  $to = seededShuffle($to, $seed);
  $from = @implode('', $from); // @ 
  $to = implode('', $to);         
  $len = strlen($str);
  if ( $len < 1 ) return '';
  $start = mt_rand(0, $len-1);
  $end = mt_rand($start, $len-1);
  $str1 = substr($str, 0, $start);
  $str2 = substr($str, $start, $end-$start);
  $str3 = substr($str, $end);
  return $str1 . strtr($str2, $to, $from) . $str3;
}
     
function seededShuffle($arr, $seed) {
  mt_srand($seed);
  $c = @count($arr); // @ 
  $out = array();
  foreach ((array)$arr as $val) { // (array) 
    $newKey = null;
    while ($newKey === null) {
      $newKey = mt_rand(0, $c * 10000);
      if (isset($out[$newKey]))
        $newKey = null;
    }
    $out[$newKey] = $val;
  }
  ksort($out);
  return array_values($out);
}

function removeJunk($str, $seed, $chance=0.6, $minlength=1, $maxlength=6) {
  mt_srand($seed);
  $chance = max(min(floatval($chance), 1.0), 0) * 100;
  $out = array();
  $countJunkAdded = 0;
  for ($x=0, $l=strlen($str); $x<$l; ++$x) {
    $hasAdded = (mt_rand(0, 100) <= $chance ? true : false);
    if ($hasAdded) {
      $length = mt_rand($minlength, $maxlength);
      for ($j=0; $j<$length; $j++)
        mt_rand();
      $x = $x + $length;
    }
    @$out[$x+$countJunkAdded] = $str[$x]; // @ 
  }
  return implode('', $out);
}

function decodeCombined($str, $password, $compress=1, $strtrIterations=100, $junkIterations=2, $junkChance=0.2, $junkMinlength=1, $junkMaxlength=3) {
  $seed = pass2seed($password);
  for ($x=$junkIterations-1; $x>=0; --$x)
    $str = removeJunk($str, $seed + (($x * 7) % 13351), $junkChance, $junkMinlength, $junkMaxlength);
  for ($x=$strtrIterations-1; $x>=0; --$x)
    $str = decodeStrtr($str, $seed + (($x * 7) % 15251));
  $str = decodeRand($str, $seed);
  if (function_exists('gzcompress') && ($compress == 1 OR is_compressed($str) == true )) // 
    $str = @gzuncompress($str); // 
  return $str;
}

function is_compressed($in) { /* https://stackoverflow.com/a/29268776/5201919 */
  if ( mb_strpos($in , "\x1f"."\x8b"."\x08") === 0 ) return true;
  else if ( @gzuncompress($in) !== false ) return true;
  else return false;
}

if (!function_exists('mb_ord')) {
  function mb_ord($char, $encoding = 'UTF-8') {
    if ($encoding === 'UCS-4BE') {
      list(, $ord) = (strlen($char) === 4) ? @unpack('N', $char) : @unpack('n', $char);
      return $ord;
    } else
      return mb_ord(mb_convert_encoding($char, 'UCS-4BE', $encoding), 'UCS-4BE');
  }
}

function pass2seed($str) {
  $r = '';
  for ($i=0; $i<strlen($str); $i++)
    $r .= mb_ord(substr($str, $i, 1)); 
  return ceil((int)$r/strlen($str));
}

// VAR INI
$out = $msg = $pf = '';

// WITH OR WITHOUT PASSWORD AND ENCRYPTION
if ( $MODE == 'CWM' ) {
  if(is_array($i18n)) $h1 = $i18n['HEADLINE3'];
  else $h1 = 'Extract hidden Copyright-Watermark';
} else {
  if(is_array($i18n)) $h1 = $i18n['HEADLINE4'];
  else $h1 = 'Extract hidden Text from Image';
  if(is_array($i18n)) $pf = '<br><strong>'.$i18n['Enter-Pass'].'</strong><input type="text" ';
  else $pf = '<br><strong>Enter your Passcode</strong><input type="text" ';
  if(is_array($i18n)) $pf .= 'name="pc" placeholder="'.$i18n['Password'].'" class="text-input-field">';
  else $pf .= 'name="pc" placeholder="Passcode" class="text-input-field">';
}

// DEMO?
if ( $MODE == 'DEMO' ) {
  $h1 .= ' <br><span style="font-size:xx-small;background:crimson;color:white;padding:2px 7px;">';
  if(is_array($i18n)) $h1 .= ' <b>'.$i18n['DEMO-MODE'].'</b> &mdash; '.$i18n['NoOwnImg'].'</span>';
  else $h1 .= ' <b>DEMO-MODE</b> &mdash; No own Image !</span>';
}

// IF FORM SUBMIT
if ( @$_REQUEST['submit'] ) {
  // GET IMAGE
  if ( $MODE == 'DEMO' )
    $img = @getimagesize( 'image.png' );
  else
    $img = @getimagesize( $_FILES['img']['tmp_name'] );
  
  // IF PNG
  if ( $img['mime'] == 'image/png' ) {
    
    // GET PASSWORD
    if ( empty( $_REQUEST['pc'] ) || !isset( $_REQUEST['pc'] ) )
      $_REQUEST['pc'] = 0;
      
    // GET TEXT FROM IMAGE
    if ( $MODE == 'DEMO' )
      $txt = helper( 'image.png', 0 );
    else
      $txt = helper( $_FILES['img']['tmp_name'], 0 );
    
    // DECRYPT TEXT
    if ( $MODE == '' || $MODE == 'DEMO' )
      $txt = decodeCombined( $txt, $_REQUEST['pc'], $_REQUEST['compress'] );
    
    // IF NO TEXT PRINT ERROR
    if ( $txt == '' ){
      if(is_array($i18n)) $txt = '<b>'.$i18n['noText'].'</b>';
      else $txt = '<b>Sorry, no TEXT found !</b>';
      }
    
    // PRINT TEXT TO BROWSER
    $out = '<div class="text"><p>'.
         str_replace( "\n", '<br>', $txt) . 
         '</p></div>';
  } else {
    
    // ERROR IF NO PNG IS FOUND
    if(is_array($i18n)) $out = '<div class="text"><p><b>'.$i18n['noImage'].'</b></p></div>';
    else $out = '<div class="text"><p><b>Sorry, no PNG-Image found !</b></p></div>';
  }
  
  // ERROR IF NO PASSWORD IS GIVEN
  if ( ( $MODE == '' || $MODE == 'DEMO' ) && empty( $_REQUEST['pc'] ) ) {
    $out = '';
    if(is_array($i18n)) $hint = $i18n['noPass'];
    else $hint = 'Please enter the needed Password !';
    $msg = '
      <div id="alert" class="alert">
      <span class="closebtn" 
      onclick="this.parentElement.style.display=\'none\';">&times;</span>
      <p id="clipboad-feedback">'.$hint.'</p>
      </div>
    ';
  }
}

// HELPER
function helper( $filename, $pos ) {
  $data = file_get_contents( $filename, false, null, $pos);
  $text = '';
  for ( $i = 0; $i < strlen($data); $i += 2 ) {
    if ( substr( $data, $i, 2) === 'C2' && substr( $data, $i + 2, 2 ) === 'B5' ){
      $marker = true; 
    }
    if ( !isset( $marker ) )
      continue;
    $text .= chr( hexdec( substr( $data, $i, 2 ) ) );
  }
  if ($text == ''){
    for ( $i = 1; $i < strlen($data); $i += 2 ) {
      if ( substr( $data, $i, 2) === 'C2' && substr( $data, $i + 2, 2 ) === 'B5' ){
        $marker = true;
      }
      if ( !isset( $marker ) )
        continue;
      $text .= chr( hexdec( substr( $data, $i, 2 ) ) );
    }
  }
  return ltrim( $text, 'µ' );
}

// DEFAULT COMPRESS CHECKED?
if ( $defaultCompress === true )
  $check = ' checked="yes"';
else
  $check = '';

// HTML TEMPLATE
if(is_array($i18n)) {
  $title  = $i18n['Title2'];
  $select = $i18n['Select'];
  $comp   = $i18n['UseComp'];
  $go     = $i18n['GO'];
  $footer = $i18n['Title1'];
}else{
  $title  = 'Extractor';
  $select = 'Select your Image file!';
  $comp   = 'Use Compression?';
  $go     = 'GO';
  $footer = 'Encryptor';
}
echo '<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>'.$title.'</title>
    <link rel="icon" type="image/x-icon" href="assets/favicon.ico">
    <style>
      /* PAGE */
      body {
        font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
        color: #333;
        padding: 1em;
        margin: 0 auto;
        width: 30%;
      }
      body > a {
        background-color: #333;
        color: white;
        display: inline-block;
        padding: 1em;
        text-decoration: none;
      }
      body > a:hover {
        background-color: orange;
      }
      h1 {
        font-size: 1.5em;
        font-weight: 300;
        letter-spacing: 1px;
        margin-top: 2em;
        text-transform: uppercase;
      }
      /* FORM */
      textarea, button {
        font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
      }
      form {
        margin-top: 2.5em;
        max-width: 30em;
      }
      label[for=comment], strong {
        color: #339966;
        display: block;
        font-style: italic;
        margin-bottom: 0.375em;
      }
      button {
        background-color: #4daf7c;
        border: 0;
        border-radius: 3px;
        color: white;
        cursor: pointer;
        display: block;
        font-size: 1em;
        font-weight: 400;
        letter-spacing: 1px;
        margin-top: 0.25em;
        padding: 1em;
        text-transform: uppercase;
        width: 100%;
      }
      button:hover { /* NEW */
        background: #3d8c63;
      }
      /* FILE */
      .file-upload-wrapper {
        position: relative;
        width: 100%;
        height: 60px;
      }
      .file-upload-wrapper:after {
        content: attr(data-text);
        font-size: 18px;
        position: absolute;
        top: 0;
        left: 0;
        background: #f6f6f6;
        padding: 10px 15px;
        display: block;
        width: calc(100% - 40px);
        pointer-events: none;
        z-index: 20;
        height: 40px;
        line-height: 40px;
        color: #999;
        border-radius: 3px 10px 10px 3px;
        font-weight: 300;
      }
      .file-upload-wrapper:before {
        content: "Upload";
        position: absolute;
        top: 0;
        right: 0;
        display: inline-block;
        height: 60px;
        background: #4daf7c;
        color: #fff;
        font-weight: 700;
        z-index: 25;
        font-size: 16px;
        line-height: 60px;
        padding: 0 15px;
        text-transform: uppercase;
        pointer-events: none;
        border-radius: 0 5px 5px 0;
      }
      .file-upload-wrapper:hover:before {
        background: #3d8c63;
      }
      .file-upload-wrapper input {
        opacity: 0;
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
        z-index: 99;
        height: 40px;
        margin: 0;
        padding: 0;
        display: block;
        cursor: pointer;
        width: 100%;
      }
      .text-input-field {
        border: 0;
        font-size: 18px;
        font-weight: 300;
        margin: 20px 0;
        background: #f6f6f6;
        padding: 10px 15px;
        display: block;
        width: calc(100% - 30px);
        z-index: 20;
        height: 40px;
        line-height: 40px;
        color: #999;
        border-radius: 3px 10px 10px 3px;
      }
      /* TEXT */
      .text {
        background-color:#000;
        animation:colorPulse 5s infinite ease-in-out;
        background-image:radial-gradient(#444, #111);
        box-shadow:0 0 5vmin 1vmin #000 inset;
        box-sizing:border-box;
        font-family:monospace;
        font-size:20px;
        *height:100vh;
        overflow:hidden;
        padding:10vmin;
        width:95%;
      }
      p:last-child:after {
        animation:blink 1s infinite;
        content:"_";
      }
      @keyframes colorPulse {
        0%, 100% {
          color:#0c0;
        }
        48%, 52% {
          color:#090;
        }
        50% {
          color:#060;
        }
      }
      @keyframes wave {
        0% {
          box-shadow:0 -10vh 20vh #0c0;
          top:-100vh;
        }
        48%, 52% {
          box-shadow:0 -10vh 20vh #090;
        }
        50% {
          box-shadow:0 -10vh 20vh #060;
        }
        100% {
          box-shadow:0 -10vh 20vh #0c0;
          top:200vh;
        }
      }
      @keyframes blink {
        50% {
          opacity:0;
        }
      }
      /* FOOTER */
      small, small a {
        color:grey;
        text-decoration:none;
      }
      textarea:active, textarea:focus, button:active, button:focus {
        outline: 0;
        border: 1px solid silver;
      }
      /* MESSAGE */
      .alert {
        *display: none;
        background-color: #444; /* #f44336 Red */
        color: white;
        margin-bottom: 15px;
        border-radius: 3px;
        width:100%;
      }
      .alert p {
        font-family: Sans-Serif;
        padding: 20px ;
      }
      .closebtn {
        margin-top: 17px;
        margin-left: 15px;
        margin-right: 15px;
        color: white;
        font-weight: bold;
        float: right;
        font-size: 22px;
        line-height: 20px;
        cursor: pointer;
        transition: 0.3s;
      }
      .closebtn:hover {
        color: red;
      }
      /* CHECKBOX */
      input.apple-switch {
        position: relative;
        -webkit-appearance: none;
        outline: none;
        width: 50px;
        height: 30px;
        background-color: #fff;
        border: 1px solid #D9DADC;
        border-radius: 50px;
        box-shadow: inset -20px 0 0 0 #fff;
      }
      input.apple-switch:after {
        content: "";
        position: absolute;
        top: 1px;
        left: 1px;
        background: transparent;
        width: 26px;
        height: 26px;
        border-radius: 50%;
        box-shadow: 2px 4px 6px rgba(0,0,0,0.2);
      }
      input.apple-switch:checked {
        box-shadow: inset 20px 0 0 0 #4daf7c;
        border-color: #4daf7c;
      }      
      input.apple-switch:checked:after {
        left: 20px;
        box-shadow: -2px 4px 3px rgba(0,0,0,0.05);
      }
    </style>
  </head>
  <body>
    <h1>' . $h1 . '</h1>
    <form method="post" enctype="multipart/form-data" class="form">
      ' . $pf . $msg . '
      <div class="file-upload-wrapper" data-text="'.$select.'">
        <input name="img" type="file" class="file-upload-field" value="" accept="image/png">
      </div><br>
      ';
    if ( $MODE == '' || $MODE == 'DEMO' )
      echo '
      <strong>'.$comp.'</strong>
      <input name="compress" value="0" type="hidden">
      <input class="apple-switch" type="checkbox" name="compress" value="1"' . $check . '><br>
      ';
    echo '
      <button type="submit" name="submit" value="submit">'.$go.'</button><br>
    </form>
    <script src="//code.jquery.com/jquery-2.1.4.min.js"></script>
    <script>
      $(".file-upload-field").on("change", function(){
        $(this).parent(".file-upload-wrapper").attr("data-text",
          $(this).val().replace(/.*(\/|\\\\)/, "") );
      });
    </script>
    <a target="_blank" href="index.php">' . $i18n['Title1'] . ' &UpperRightArrow;</a><br>
    <br>
    '.$out.'
    <br>
    <small>
      &copy; '.date("Y").' <a href="#">Text-Image Encryptor</a>
    </small>
  </body>
</html>';
