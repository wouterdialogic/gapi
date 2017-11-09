<?php

//this script makes use of the google maps api, 
//as input give 2 coordination and a radius,
//a grid of coordinates will be created what to crawl.
//important! google offers 1000 free results per day, 

//Nederland ligt tussen: NE: lat 53.775450, lng 3.268645
//						 NW: 53.828762, 6.986158
//						 SE: 50.749191, 2.956355
//						 SW: 50.647091, 6.986158
//oftewel: 				 lat neemt af
//						 lng neemt toe

//1km long (naar links / rechts) = ~ lng 0.014446
//1km lat (naar onder / boven) = ~ 0.008967

//function crawlBetweenTwoCoordinates werkt met de 4 coordinaten
//function takeSnapshot gebruiken om 1 coordinaat te crawlen

//to save to another database, make a new capsule in settings, and run this function one time:
createPlacesTable(); exit;

use Illuminate\Database\Capsule\Manager as Capsule;
use Models\Order as Order;
use Models\Place as Place;
use Models\Type as Type;
use Classes\GoogleMapsApi as GMA;

set_time_limit ( 0 );	

//to make sure we get everything, what is the percentage of overlap to be used?
//1 is no overlap, 0.90 is a 10% overlap
$overlap_between_squares = 0.95;

//the radius which we give to google api
$radius = 470;

//set the square what to crawl, this is Helmod:
$northeast_lat = 51.517318;
$northeast_lng = 5.582351;
$southwest_lat = 51.410371;
$southwest_lng = 5.699109;	 

//EINDHOVEN:			
//crawlBetweenTwoCoordinates(51.497642, 5.359402, 51.396241, 5.544558, 1000);

// takeSnapShot(51.478514, 5.651232, 5000, 1, 1);
crawlBetweenTwoCoordinates($northeast_lat, $northeast_lng, $southwest_lat, $southwest_lng, $radius, $overlap_between_squares);

function calculateSquareSideWithRadius($radius) {

	$lengthOfOneSideOfASqare = sqrt (( $radius ** 2 ) / 2 ) * 2 * $overlap_between_squares;
	
	return $lengthOfOneSideOfASqare;
}

function crawlBetweenTwoCoordinates($northeast_lat, $northeast_lng, $southwest_lat, $southwest_lng, $radius = 500, $overlap_between_squares = 0.95) {
	echo "<BR>STARTING LOCATIONS:";
	echo "<BR>NE lat: ".$northeast_lat;
	echo "<BR>NE lng: ".$northeast_lng;
	echo "<BR>SW lat: ".$southwest_lat;
	echo "<BR>SW lng: ".$southwest_lng;
	echo "<BR>radius: ".$radius;
	echo "<br>";
	$move_to_next_location_in_meters = calculateSquareSideWithRadius($radius, $overlap_between_squares);
	
	$one_km_in_lng = 0.014446;
	$one_km_in_lat = 0.008967;
	
	$one_meter_in_lng = $one_km_in_lng / 1000;
	$one_meter_in_lat = $one_km_in_lat / 1000;

	$move_to_next_lng = $move_to_next_location_in_meters * $one_meter_in_lng;
	$move_to_next_lat = $move_to_next_location_in_meters * $one_meter_in_lat;

	$current_lat = $northeast_lat;
	$current_lng = $northeast_lng;

	//only for testing purposes:
	$grid_lat = "A";
	$grid_lng = 1;

	//go from up to down
	while ($current_lat > $southwest_lat) {
		takeSnapShot($current_lat, $current_lng, $radius, $grid_lat, $grid_lng);
		++$grid_lng;
		//go from left to right
		while ($current_lng < $southwest_lng) {
			
			$current_lng += $move_to_next_lng;
			takeSnapShot($current_lat, $current_lng, $radius, $grid_lat, $grid_lng);
			++$grid_lng;
		}

		$current_lat -= $move_to_next_lat;
		$current_lng = $northeast_lng;
		$grid_lng = 1;
		++$grid_lat;
	}

	//one extra in lat:
	takeSnapShot($current_lat, $current_lng, $radius, $grid_lat, $grid_lng);
	++$grid_lng;
	while ($current_lng < $southwest_lng) {
		
		$current_lng += $move_to_next_lng;
		takeSnapShot($current_lat, $current_lng, $radius, $grid_lat, $grid_lng);
		++$grid_lng;
	}
}

function takeSnapShot($current_lat, $current_lng, $radius, $grid_lat, $grid_lng) {
	echo "<br>\r\n$grid_lat$grid_lng";
	echo "<br>*** CLICK! *** ".$current_lat . " - " . $current_lng . " - " . $radius;
	echo "<Br>";

	$GoogleMapsRequest = new GMA;
	$GoogleMapsRequest->url_required_parts["location_x"] = $current_lat;
	$GoogleMapsRequest->url_required_parts["location_y"] = $current_lng;
	$GoogleMapsRequest->url_required_parts["radius"] = $radius;
	
	//this works only if we have google credits to spend:
	//$output = $GoogleMapsRequest->do_one_request();
	//useGoogleOutput($output);

	echo "<pre>";
	print_r($output);
	echo "</pre>";	

	sleep(1);
}

function useGoogleOutput($output) {
	$results = $output->results;
	
	foreach ($results as $result) {
		echo "<pre>";
		print_r($result);
		echo "</pre>";
	
		$record = mapGoogleResultToDatabase($result);
		$record->types = flattenArray($record->types);
	
		echo "<h1>OUTPUT</h2>";
		echo $record->name;
		echo "<br>";
		echo "<pre>";
		print_r($record->toArray());
		echo "</pre>";
	
		$record->save();
	}

	$allNames = [];
	foreach ($results as $key => $result) {
		$allNames[$key] = $record->name;
	}

	echo "all names:";
	echo "<pre>";
	print_r($allNames);
	echo "</pre>"; 
}

function flattenArray(array $input) {
	$string = '';
	if (!empty($input)) {
		foreach($input as $key => $value) {
			$string .= $value.",";
		}
	}

	return $string;
}

function mapGoogleResultToDatabase($result) {
	$record = new Place;

	foreach($record->fillable as $fillMe => $value) {
		$path = $record->modelResponseMapping[$value];

		$record->$value = $result->{$path};
	}

	$record->geometry_location_lat = $result->geometry->location->lat;
	$record->geometry_location_lng = $result->geometry->location->lng;
	$record->geometry_viewport_northeast_lat = $result->geometry->viewport->northeast->lat;
	$record->geometry_viewport_northeast_lng = $result->geometry->viewport->northeast->lng;
	$record->geometry_viewport_southwest_lat = $result->geometry->viewport->southwest->lat;
	$record->geometry_viewport_southwest_lng = $result->geometry->viewport->southwest->lng;
	$record->opening_hours_weekday_text = $result->opening_hours->weekday_text;

	return $record;
}

function createTable() {
	Capsule::schema()->create('orders', function ($table) {
	   $table->increments('id');
	   $table->string('title');	
	});
}

function createTypesTable() {
	Capsule::schema()->create('types', function ($table) {
	   $table->increments('id');
	   $table->integer('place_id');
	   $table->string('type');	
	});
}

function createPlacesTable() {
	Capsule::schema()->create('places', function ($table) {
	   $table->increments('id');
	   $table->float('geometry_location_lat', 20, 15)->nullable();
	   $table->float('geometry_location_lng', 20, 15)->nullable();
	   $table->float('geometry_viewport_northeast_lat', 20, 15)->nullable();
	   $table->float('geometry_viewport_northeast_lng', 20, 15)->nullable();
	   $table->float('geometry_viewport_southwest_lat', 20, 15)->nullable();
	   $table->float('geometry_viewport_southwest_lng', 20, 15)->nullable();
	   $table->string('icon')->nullable();	
	   $table->string('google_id')->nullable();	
	   $table->string('name')->nullable();	
	   $table->string('opening_hours')->nullable();	
	   $table->string('opening_hours_weekday_text')->nullable();		   
	   $table->string('photos_photo_reference')->nullable();		  
	   $table->string('place_id')->nullable();	
	   $table->string('rating')->nullable();	
	   $table->text('reference')->nullable();	
	   $table->string('scope')->nullable();	
	   $table->string('vicinity')->nullable();	
	   $table->string('types')->nullable();	 //@@@@@@@@@UITWERKEN
	   $table->string('search_radius')->nullable();
	   $table->string('search_keyword')->nullable();
	   $table->float('search_lat', 20, 15)->nullable();
	   $table->float('search_lng', 20, 15)->nullable();
	   $table->string('search_overlap')->nullable();
	   $table->string('search_place')->nullable();
	});
}
