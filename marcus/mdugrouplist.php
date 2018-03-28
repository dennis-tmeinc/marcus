<?php
// mdugrouplist.php 	
//		get mdu group id list
// Request:
//      none
// Return:
//      json 
//
// By Dennis Chen @ TME	 - 2017-09-14
// Copyright 2017 Toronto MicroElectronics Inc.
//
require 'session.php' ;
header("Content-Type: application/json");

if( $session_logon ) {
	$conn = new mysqli("p:".$sql_server, $sql_user, $sql_password, $_SESSION['db'] );
	$sql = "SELECT `id` FROM `group`" ;
	if($result=$conn->query($sql)) {
		$resp['grouplist'] = array();
		while( $row=$result->fetch_array(MYSQLI_ASSOC) ) {
			$resp['grouplist'][] = $row['id'] ;
		}
		$resp['res'] = 1 ;
		$result->free();
	}
}
echo json_encode($resp);
?>