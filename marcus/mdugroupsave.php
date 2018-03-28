<?php
// mdugroupsave.php - save one mdu group
// Requests:
//		id:				group id
//		mdu_id_list:	array of mdu ids
//		addgroup:		true = add a new group
// Return:
//      JSON object, res=1 for success
// By Dennis Chen @ TME	 - 2017-09-15
// Copyright 2017 Toronto MicroElectronics Inc.
    require 'session.php' ;
	header("Content-Type: application/json");
	
	if( $session_logon ) {
		$conn = new mysqli("p:".$sql_server, $sql_user, $sql_password, $_SESSION['db'] );
		$resp['req'] = $_REQUEST ;
		
		$eid = $conn->escape_string($_REQUEST['id']);
		$emdu = $conn->escape_string( implode(";", $_REQUEST['mdu_id_list']) );
		
		if( !empty($_REQUEST['addgroup']) ) {
			$sql = "INSERT INTO `group` (`id`, `mdu_id_list`) VALUES ('$eid', '$emdu')" ;
		}
		else {
			$sql = "UPDATE `group` SET `mdu_id_list` = '$emdu' WHERE `id` = '$eid' ;" ;
		}
		if( $conn->query($sql) ) {
			$resp['res']=1 ;	// success
			unset( $resp['errormsg'] );
		}
		else {
			$resp['errormsg']=$conn->error;
		}	
	}
	echo json_encode($resp);
?>