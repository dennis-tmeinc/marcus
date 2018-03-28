<?php
// usersave.php - save one user information
// Requests:
//		user_name:	user name
//		user_password:	password (******** for default)
//		user_type:		user type
//		real_name:		contact name
//		adduser:		true = add a new user
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
				
			if( $_REQUEST['user_password']  == '********' ) {		// use default password ?
				if( !empty($_REQUEST['adduser']) ) {
					$key='' ;
				}
				else {
					$key='' ;
					$keyupdstr = " " ;
				}
			}
			else {
				$salt='' ;
				$hexchar="0123456789abcdefghijklmnopqrstuvwxyz" ;
				for( $i=0; $i<8; $i++) {
					$salt .= $hexchar[mt_rand(0,35)] ;
				}
				// key: $1$<md5(user:salt:password)>$<salt>
				$key = '$1$'. md5( $_REQUEST['user_name'].':'.$salt.':'.$_REQUEST['user_password']  ) . '$'.  $salt ;
				$key = $conn->escape_string($key);
				$keyupdstr = " `user_password` = '$key', " ;
			}
			
			if( !empty($_REQUEST['adduser']) ) {
				$sql = "INSERT INTO `app_user` (`user_name`, `user_password`, `user_type`, `real_name`) VALUES ('$esc_req[user_name]', '$key', '$esc_req[user_type]', '$esc_req[real_name]')" ;
			}
			else {
				$sql="UPDATE app_user SET $keyupdstr `user_type` = '$esc_req[user_type]', `real_name` = '$esc_req[real_name]'  WHERE `user_name` = '$esc_req[user_name]' ;" ;
			}

			if( $conn->query($sql) ) {
				$resp['res']=1 ;	// success
				unset( $resp['errormsg'] );
			}
			else {
				$resp['sql'] = $sql ;
				$resp['errormsg']=$conn->error;
			}			
		
	}
	echo json_encode($resp);
?>