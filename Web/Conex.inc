<?php
   $servidor = "mysql.inf.uct.cl";
   $user     = "evejar";
   $password = "7snRMxTUNHwz*0*um";
   $basedato = "A2024_evejar";
   
   
$db = mysqli_connect($servidor, $user, $password, $basedato);

   if(!$db) {
         echo "Error de conexion a la BD";
         exit();
   }
?>