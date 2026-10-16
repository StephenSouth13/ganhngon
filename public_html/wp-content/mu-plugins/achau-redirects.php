<?php
/* achau-redirects.php — 301 redirects (Hermes). Delete this file to remove. */
add_action('template_redirect', function(){
  $bp=array("/dat-cho/"=>"https://ganhngon.com/dat-ban/","/ve-chung-toi/"=>"https://ganhngon.com/ve-ganh-ngon/","/ganh-ngon/"=>"https://ganhngon.com/ve-ganh-ngon/");
  $bi=array();
  $u=parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH); if($u && substr($u,-1)!=='/') $u.='/';
  if($u && isset($bp[$u])){ wp_redirect($bp[$u],301); exit; }
  $id=function_exists('get_queried_object_id')?get_queried_object_id():0;
  if($id && isset($bi[$id])){ wp_redirect($bi[$id],301); exit; }
}, 1);
