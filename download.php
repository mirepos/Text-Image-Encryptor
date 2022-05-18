<?php /* 29 Lines */

if ( isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) and
     file_exists('lang/'.substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2).'.php') ) {
   @include_once('lang/'.substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2).'.php');
} else {
  @include_once('lang/en.php');
}

function download($file){
  header('Content-Description: File Transfer');
  header('Content-Type: application/octet-stream');
  header('Content-Disposition: attachment; filename="' . basename($file) . '"');
  header('Expires: 0');
  header('Cache-Control: must-revalidate');
  header('Pragma: public');
  header('Content-Length: ' . filesize($file));
  readfile($file);
  return true;
}

if(isset($_GET['file'])) {
  $file_parts = pathinfo($_GET['file']);
  $file = $file_parts['filename'].'.'.$file_parts['extension'];
  if (file_exists($file))
    if (download($file)) unlink($file);
  else header('Location: ./index.php?message='.$i18n['downloaded']);
}
