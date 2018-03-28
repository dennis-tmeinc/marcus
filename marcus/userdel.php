<?php
// userdel.php - delete one user
// Requests:
//      user_name: user name to delete
// Return:
//      JSON object, res=1 for success
// By Dennis Chen @ TME	 - 2017-09-14
// Copyright 2017 Toronto MicroElectronics Inc.

    require 'session.php' ;
	header("Content-Type: application/json");
	
	if( $session_logon ) {
		if( $_SESSION['user'] == $_REQUEST['user_name'] ) {
			$resp['errormsg'] = "Can't delete current user!" ;
			goto done ;
		}

		$conn = new mysqli("p:".$sql_server, $sql_user, $sql_password, $_SESSION['db'] );

		// escaped sql values
		$user_name = $conn->escape_string( $_REQUEST['user_name'] ) ;
		
		$sql="DELETE FROM app_user WHERE user_name = '$user_name' ;";
		if( mysqli_query($conn, $sql) ) {
			$resp['res']=1 ;	// success
		}
		else {
			$resp['errormsg']=$conn->error;
		}
	}
done:	
	echo json_encode($resp);
?>