<?php
// Make the connection
try{
	$host = "localhost";
	$dbname = "CSC355FA24Magic";
	$username = "ndm7739";
	$password = "Luana4592";

	$db_conn_string = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
	$options = [
		PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Enable exceptions
		PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_BOTH,       // Set fetch mode to output data objects
		PDO::ATTR_EMULATE_PREPARES   => false,                  // Use native prepared statements
	];

	$dbc = new PDO($db_conn_string, $username, $password, $options);

	echo("DB Porpoised successfully");
} catch (PDOException $e){
	echo $e->getMessage();
}
?>