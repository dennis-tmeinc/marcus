<?php
	// SQL server
	// $sql_server="192.168.42.98" ;
	$sql_server="64.40.243.196" ;
	$sql_user="admin" ;
	$sql_password="" ;
	$sql_database="marcusmaindb" ;
	
	// List of timezone: http://php.net/manual/en/timezones.php
	$timezone = "America/Toronto" ;
	
	// bing map
	$map_credentials="AnC6aHa2J8jWAluq14HQu6HDH1cDshGtPEPrYiotanIf-q6ZdoSiVGd96wzuDutw" ;

	// session 
	$session_path= "r:\\marcus\\session";
	$session_timeout=900 ;
	
	// cache dir
	$cache_dir = "r:\\marcus\\cache";
	
	$remote_fileserver = "http://64.40.243.196/marcus/fileservice.php" ;	

	// marcus service (by Tongrui)
	$marcus_service = "http://" . $sql_server . ":48002/MService" ;
	$mservice_callback = "http://192.168.42.126:80/marcus/marcusevent.php" ;

	// script/excutable to create/remove com
	$ms_new    = "C:\\MarcusServerApps\\setup.exe create " ;
	$ms_clean  = "C:\\MarcusServerApps\\setup.exe remove " ;
	
	$log_service = true ;

?>