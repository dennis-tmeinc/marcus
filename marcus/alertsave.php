<?php
// usersave.php - save one user information
// Requests:
//		group_id:	 	mdu group id
//		type:			alert type
//		to_email_addr:	email addresses
//		serialNo:		alert serialNo in the table
//		addalert:		true = add a new alert
// Return:
//      JSON object, res=1 for success
// By Dennis Chen @ TME	 - 2013-06-15
// Copyright 2013 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");
	
	if( $session_logon ) {

			$conn = new mysqli("p:".$sql_server, $sql_user, $sql_password, $_SESSION['db'] );

			// escaped sql values
			$esc_req=array();
			foreach( $_REQUEST as $key => $value )
			{
				$esc_req[$key]=$conn->escape_string($value);
			}
			
			if( isset($_REQUEST['serialNo']) ) {
				$sql = "UPDATE `alert_email_config` SET `group_id` = '$esc_req[group_id]', `type` = '$esc_req[type]' , `to_email_addr` = '$esc_req[to_email_addr]'  WHERE `serialNo` = $_REQUEST[serialNo] ;" ;
			}
			else {
				$sql = "INSERT INTO `alert_email_config` (`group_id`, `type`, `to_email_addr`) VALUES ('$esc_req[group_id]', $esc_req[type], '$esc_req[to_email_addr]')" ;
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