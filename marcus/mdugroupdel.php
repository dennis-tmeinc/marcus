<?php
// mdugroupdel.php - delete a mdu group
// Requests:
//      id	:	group id
// Return:
//      JSON object, res=1 for success
// By Dennis Chen @ TME	 - 2017-09-15
// Copyright 2017 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");
	
	if( $session_logon ) {
		$conn = new mysqli("p:".$sql_server, $sql_user, $sql_password, $_SESSION['db'] );

		// escaped sql values
		$eid = $conn->escape_string( $_REQUEST['id'] ) ;
		
		$sql="DELETE FROM `group` WHERE `id` = '$eid';";
		if( mysqli_query($conn, $sql) ) {
			$resp['res']=1 ;	// success
			unset( $resp['errormsg'] );
		}
		else {
			$resp['errormsg']=$conn->error;
		}
	}
done:	
	echo json_encode($resp);
?>