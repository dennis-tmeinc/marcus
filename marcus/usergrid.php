<?php
// usergrid.php 	
//		get users list for grid view
// Request:
//      rows : number or rows to retrieve
//      page : page number
//
//      _search : true/false
//      sidx : sort index
//		sord : sort order
// Return:
//      json 
//		{
//			"records":"32680",
//			"total":327,
//			"page":"1",
//			"rows":[
//				{
//					"id":"52552",
//					"cell":[ "admin", "user", "Tongrui Zhang" ]
//				},
//              ...
//				{...}
//			]
//		}
//
// By Dennis Chen @ TME	 - 2017-09-12
// Copyright 2017 Toronto MicroElectronics Inc.
//
// 
//
require 'session.php' ;
header("Content-Type: application/json");

if( $session_logon ) {
	// assume failed
	$resp['records'] = 0 ;
	$resp['total'] = 0 ;
	$resp['page'] = $_REQUEST['page'] ;
	$resp['rows'] = array() ;
	
	$conn = new mysqli("p:".$sql_server, $sql_user, $sql_password, $_SESSION['db'] );
	
	$username = $_SESSION['user'] ;
	$wherecl = '' ;

	if( $_SESSION['user_type'] != 'superpower' ) {
		$usertypes = array();
		if( get_privilege( "EditSuperpower" ) )
			$usertypes[] = "superpower" ;
		
		if( get_privilege( "EditPower" ) )
			$usertypes[] = "power" ;
		
		if( get_privilege( "EditAdmin" ) )
			$usertypes[] = "admin" ;

		if( get_privilege( "EditSensor" ) )
			$usertypes[] = "sensor" ;

		if( get_privilege( "EditUser" ) )
			$usertypes[] = "user" ;
		
		if( empty( $usertypes ) ) {
			$wherecl = " WHERE user_name = '$username' " ;
		}
		else {
			$wherecl = " WHERE user_name = '$username' OR user_type in (" ;
			foreach( $usertypes as $value ) {
				$wherecl .= "'$value'," ;
			}
			$wherecl .= "'x') " ;
		}
	}
	
	// total records query
	$sql="SELECT count(*) FROM app_user $wherecl" ;
	if( $result=$conn->query($sql) ) {
		if( $row = $result->fetch_array( MYSQLI_NUM ) ) {
			$resp['records'] = $row[0] ;
			$resp['total'] = ceil($row[0]/$_REQUEST['rows']) ;
			$resp['page'] = $_REQUEST['page'] ;
		}
		$result->free();
	}
	
	$sql = "SELECT * FROM app_user $wherecl" ;
	if( !empty( $_REQUEST['sidx'] && !empty( $_REQUEST['sord']) ) ) {
		$sql .= " ORDER BY `$_REQUEST[sidx]` $_REQUEST[sord]" ;
	}

	$start = $_REQUEST['rows'] * ($resp['page']-1) ;
	$sql .= " LIMIT $start, $_REQUEST[rows] " ;
	
	
	$resp['sql'] = $sql ;
	
	if($result=$conn->query($sql)) {
		while( $row=$result->fetch_array(MYSQLI_ASSOC) ) {
			$cell = array() ;
			$cell['id'] = $row['user_id'] ;
			$cell['cell'] = $row ;
			$resp['rows'][] = $cell ; 
		}
		$result->free();
	}
}
echo json_encode($resp);
?>