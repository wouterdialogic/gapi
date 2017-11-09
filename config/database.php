<?php 

use Illuminate\Database\Capsule\Manager as Capsule;

$capsule = new Capsule;


//echo "<br>dir: ".__DIR__;
//$path = dir(__DIR__.'\..\database.sqlite');
//echo "<br>path: ".$path;
//exit;


// $capsule->addConnection([
//     "driver" => "sqlite",
//     "database" => 'database.sqlite',
//     //'prefix' => ''  
// ], 'default');


//$capsule->addConnection([
//	"driver" => "sqlite",
//	"datanase" => __DIR__.'/../database.sqlite',
//	'prefix' => ''	
//], "sqlite");

$capsule->addConnection([
   'driver'    => 'mysql',
   'host'      => 'localhost',
   'database'  => 'google_places',
   'username'  => 'google_places',
   'password'  => 'google_places',
   'charset'   => 'utf8',
   'collation' => 'utf8_unicode_ci',
   //'prefix'    => '',
], 'default');

$capsule->setAsGlobal();
$capsule->bootEloquent();
