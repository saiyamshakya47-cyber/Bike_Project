<?php  
//initializing variables
      // function OpenCon() {
       	$servername = "localhost";  
       $username = "root";  
       $password = "";  
       $db ="bikerental.sql";
       //connect to server
       $conn = mysqli_connect ($servername , $username , $password,$db) or die("unable to connect to host");
        

       //return $conn;
       //}
?>   