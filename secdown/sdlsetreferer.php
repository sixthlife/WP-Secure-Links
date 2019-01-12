<?php 

require_once '../../../wp-load.php';

global $wp, $wpdb, $post;

include_once(dirname(__FILE__).DIRECTORY_SEPARATOR."sdlfunct.php");



if(isset($_GET['securl']) && $_GET['securl']!=""){

	 $client_ip = sdl_getip();

	 $current_url = $_GET['currenturl'];
	 
	 $postid = $_GET['postid'];

	 $dcodearray = explode('=', $_GET['securl']);

	 $dcode = trim($dcodearray[count($dcodearray)-1]);

	 $current_url = mysql_real_escape_string($current_url);


	 $query = "UPDATE {$wpdb->prefix}ipmap set refer = '{$current_url}' where ipaddress = '{$client_ip}' and dccode='{$dcode}' AND pageid={$postid} LIMIT 1";
	$wpdb->query($query);



}



?>