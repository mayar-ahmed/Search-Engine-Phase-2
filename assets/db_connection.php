<?php
	$dbhost="localhost";
	$dbuser="root";
	$dbpass="";
	$dbname="search_engine";

// change these parameters based on your password and username
	 $connection = mysqli_connect($dbhost , $dbuser , $dbpass , $dbname);
global $connection;
	
	
	if (mysqli_connect_errno()){
		die ("Database connection failed: " . mysqli_connect_error() .
			"(" . mysqli_connect_errno() . ")" );
		
	}
?>