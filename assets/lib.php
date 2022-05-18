<?php /* 235 Lines */

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

if ( count( get_included_files() ) === 1 ) die("Direct access forbidden");

//////////////////////////////////////////////////////////////////////////////////////
////// BUT DO NOT EDIT ANYTHING IF YOU ARE NOT TOTALY SHURE WHAT YOU ARE DOING! //////
//////////////////////////////////////////////////////////////////////////////////////

function encodeRand($str, $seed) {
  mt_srand($seed);
  $out = array();
  for ($x=0, $l=strlen($str); $x<$l; $x++)
    $out[$x] = (ord($str[$x]) * 3) + mt_rand(350, 16000);
  mt_srand();
  return implode('-', $out);
}
     
function decodeRand($str, $seed) {
  mt_srand($seed);
  $blocks = explode('-', $str);
  $out = array();
  foreach ($blocks as $block) {
    $ord = (intval($block) - mt_rand(350, 16000)) / 3;
    $out[] = chr($ord);
  }    
  mt_srand();
  return implode('', $out);
}
     
function encodeStrtr($str, $seed) {
  $from = '';
  $to = '';
  for ($x=0; $x<256; $x++)
    $from[$x] = chr($x);
  $to = $from;
  $to = seededShuffle($to, $seed);
  $from = @implode('', $from); // @ 
  $to = implode('', $to); // @ 
  $len = strlen($str);
  $start = mt_rand(0, $len-1);
  $end = mt_rand($start, $len-1);
  $str1 = substr($str, 0, $start);
  $str2 = substr($str, $start, $end-$start);
  $str3 = substr($str, $end);
  return $str1 . strtr($str2, $from, $to) . $str3;
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
          
function addJunk($str, $seed, $chance=0.6, $minlength=1, $maxlength=6) {
  mt_srand($seed);
  $chance = max(min(floatval($chance), 1.0), 0) * 100;
  $out = array();
  $countJunkAdded = 0;
  for ($x=0, $l=strlen($str); $x<$l; ++$x) {
    $isAdding = (mt_rand(0, 100) <= $chance ? true : false);
    if ($isAdding) {
      $length = mt_rand($minlength, $maxlength);
      for ($j=0; $j<$length; ++$j) {
        $out[$x + $countJunkAdded] = chr(mt_rand(0, 256));
        $countJunkAdded++;
      }
    }
    $out[$x+$countJunkAdded] = $str[$x];
  }
  return implode('', $out);
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
     
function encodeCombined($str, $password, $compress=1, $strtrIterations=100, $junkIterations=2, $junkChance=0.2, $junkMinlength=1, $junkMaxlength=3) {
  if (function_exists('gzcompress') && $compress == 1) // 
    $str = gzcompress($str, 9); // 
  $seed = pass2seed($password);
  $str = encodeRand($str, $seed);
  for ($x=0; $x<$strtrIterations; ++$x)
    $str = encodeStrtr($str, $seed + (($x * 7) % 15251));
  for ($x=0; $x<$junkIterations; ++$x)
    $str = addJunk($str, $seed + (($x * 7) % 13351), $junkChance, $junkMinlength, $junkMaxlength);
  return $str;
}
     
function decodeCombined($str, $password, $compress=1, $strtrIterations=100, $junkIterations=2, $junkChance=0.2, $junkMinlength=1, $junkMaxlength=3) {
  $seed = pass2seed($password);
  for ($x=$junkIterations-1; $x>=0; --$x)
    $str = removeJunk($str, $seed + (($x * 7) % 13351), $junkChance, $junkMinlength, $junkMaxlength);
  for ($x=$strtrIterations-1; $x>=0; --$x)
    $str = decodeStrtr($str, $seed + (($x * 7) % 15251));
  $str = decodeRand($str, $seed);
  if (function_exists('gzcompress') && $compress == 1) // 
    $str = @gzuncompress($str); // 
  return $str;
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

function t( $file, $pos ) {
  $data = file_get_contents( $file, FALSE, NULL, $pos + 4 );
  $text = '';
  for ( $i=0; $i < strlen( $data ); $i += 2 )
    $text .= chr( hexdec( substr( $data, $i, 2 ) ) );
  return $text;
}

function r( $n ) {
  if ( $fh = fopen( $n, 'r' ) ) {
    $data = '';
    while ( $fh && !feof( $fh ) ) {
      $chunk = fread( $fh, 1000000 ); /* altering this number may affect performance */
      for ( $i=0; $i < strlen( $chunk ); $i++ ) {
        $byte = dechex( ord( $chunk[$i] ) );
        $data .= str_repeat( '0' , 2 - strlen( $byte ) ).$byte;
      }
    }
    fclose( $fh );
  } else
    $data = '89504e470d0a1a0a0000000d494844520000000100000001010300000025db56ca00000003504c5445000000a77a3dda0000000174524e530040e6d8660000000a4944415408d76360000000020001e221bc330000000049454e44ae426082';
  return $data;
}

function p( $text, $filename, $data ) {
  $fh = fopen( $filename, 'wb' );
  fwrite( $fh, hex2bin( $data ) );
  fwrite( $fh, a2h( $text ) );
  fclose( $fh );
  return strlen( $data ) / 2;
}

function a2h( $ascii ) {
  $ascii = 'µ' . $ascii;
  $string = '';
  for ( $i = 0; $i < strlen( $ascii ); $i++ ) {
    $byte = strtoupper( dechex( ord( substr( $ascii, $i, 1 ) ) ) );
    $string .= str_repeat( '0', 2 - strlen( $byte ) ) . $byte;
  }
  return $string;
}
