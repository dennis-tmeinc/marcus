<?php

	for( $i=0; $i<1000000; $i++ ) {

		echo "MESSAGE : $i" ;
		
		flush();
		ob_flush();
		
		set_time_limit ( 30 );
		
		usleep( 500000 );
	}
	
?>