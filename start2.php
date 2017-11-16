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
//createPlacesTable(); exit;

use Illuminate\Database\Capsule\Manager as Capsule;
use Models\Order as Order;
use Models\Place as Place;
use Models\Type as Type;
use Classes\GoogleMapsApi as GMA;

set_time_limit ( 0 );	

$settings = [];
//to make sure we get everything, what is the percentage of overlap to be used?
//1 is no overlap, 0.90 is a 10% overlap
$settings["overlap"] = 0.95;

//the radius which we give to google api
$settings["radius"] = 470;

//set the square what to crawl, this is Helmod:
//$settings["northeast_lat"] = 51.517318;
//$settings["northeast_lng"] = 5.582351;
//$settings["southwest_lat"] = 51.410371;
//$settings["southwest_lng"] = 5.699109;	 

//centrum eindhoven
//$settings["places"]["eindhoven"]["northeast_lat"] = 51.453235;
//$settings["places"]["eindhoven"]["northeast_lng"] = 5.448834;
//$settings["places"]["eindhoven"]["southwest_lat"] = 51.423889;
//$settings["places"]["eindhoven"]["southwest_lng"] = 5.504739;	 

//helmond totaal:
//51.509547, 5.570446, , 
$settings["places"]["helmond"]["northeast_lat"] = 51.509547;
$settings["places"]["helmond"]["northeast_lng"] = 5.570446;
$settings["places"]["helmond"]["southwest_lat"] = 51.437964;
$settings["places"]["helmond"]["southwest_lng"] = 5.736652;	 

//eindhoven totaal:
//51.495469, 5.416563 51.405234, 5.527776
$settings["places"]["eindhoven"]["northeast_lat"] = 51.495469;
$settings["places"]["eindhoven"]["northeast_lng"] = 5.416563;
$settings["places"]["eindhoven"]["southwest_lat"] = 51.405234;
$settings["places"]["eindhoven"]["southwest_lng"] = 5.527776;	 

//den bosch totaal:
//51.738724, 5.247601  51.675956, 5.355132
$settings["places"]["den bosch"]["northeast_lat"] = 51.738724;
$settings["places"]["den bosch"]["northeast_lng"] = 5.247601;
$settings["places"]["den bosch"]["southwest_lat"] = 51.675956;
$settings["places"]["den bosch"]["southwest_lng"] = 5.355132;	 

//tilburg totaal:
//51.607295, 4.966317  51.529792, 5.128773
$settings["places"]["tilburg"]["northeast_lat"] = 51.607295;
$settings["places"]["tilburg"]["northeast_lng"] = 4.966317;
$settings["places"]["tilburg"]["southwest_lat"] = 51.529792;
$settings["places"]["tilburg"]["southwest_lng"] = 5.128773;

//breda totaal:
//51.629237, 4.698765  51.557764, 4.846733
$settings["places"]["breda"]["northeast_lat"] = 51.629237;
$settings["places"]["breda"]["northeast_lng"] = 4.698765;
$settings["places"]["breda"]["southwest_lat"] = 51.557764;
$settings["places"]["breda"]["southwest_lng"] = 4.846733;

//het keyword bepaalt wat voorzoekresultaten er komen, een andere optie is om met type te werken, maar keyword is beter
$settings["keyword"] = urlencode('atelier');

//use this for your own reference! Nothing is done with this variable except that it is saved into the database
$settings["place"] = "Eindhoven";

//EINDHOVEN:			
//crawlBetweenTwoCoordinates(51.497642, 5.359402, 51.396241, 5.544558, 1000);

//to keep up how many google api calls are being done.
$settings["clicks"] = 1;

//this is set by the user!
$settings["run"] = true; 
$settings["user_input"]["places"] = ["helmond", "den bosch", "tilburg", "breda", "eindhoven"];
//exposition? exhibition? studio?
$settings["user_input"]["keywords"] = ["museum", "theater", "toneel", "film", "filmhuis", "bioscoop", 'atelier', "galerie", "theatre", "performing arts", "movie", "exposition", "cinema", "gallery"];
$settings["user_input"]["test"] = "true";

function crawlWithUserInput($settings, $crawlThesePlaces, $keywords) {
	
	//loop over places
	foreach ($settings["places"] as $key => $value) {
		
		if (!in_array($key, $crawlThesePlaces)) {
			// do nothing
		} else {
			$settings["northeast_lat"] = $value["northeast_lat"];
			$settings["northeast_lng"] = $value["northeast_lng"];
			$settings["southwest_lat"] = $value["southwest_lat"];
			$settings["southwest_lng"] = $value["southwest_lng"];
			$settings["place"] = $key;
			echo "<br>$key:<br>";
			print_r($value);

			//loop over keywords
			foreach ($keywords as $keyword) {
				$settings["keyword"] = urlencode($keyword);
				$settings = crawlBetweenTwoCoordinates($settings);
			}
		}
	}
}

function calculateSquareSideWithRadius($radius, $overlap) {
	//to calculate one side of a square from a radius (straal), sqrt (( $radius * 2) / 2 ) * 2
	//if you would use this, you would have no overlap, so we do * overlap to fix this.
	$lengthOfOneSideOfASqare = sqrt (( $radius ** 2 ) / 2 ) * 2 * $overlap;
	return $lengthOfOneSideOfASqare;
}

function crawlBetweenTwoCoordinates($settings) {
	$northeast_lat = $settings["northeast_lat"];
	$northeast_lng = $settings["northeast_lng"];
	$southwest_lat = $settings["southwest_lat"];
	$southwest_lng = $settings["southwest_lng"];
	$keyword = $settings["keyword"];
	$place = $settings["place"];
	$radius = $settings["radius"];
	$overlap = $settings["overlap"];

	echo "<BR>STARTING LOCATIONS:";
	echo "<BR>place: ".$place;
	echo "<BR>keyword: ".$keyword;
	echo "<BR>NE lat: ".$northeast_lat;
	echo "<BR>NE lng: ".$northeast_lng;
	echo "<BR>SW lat: ".$southwest_lat;
	echo "<BR>SW lng: ".$southwest_lng;
	echo "<BR>radius: ".$radius;
	echo "<BR>overlap: ".$overlap;
	echo "<br>";

	$move_to_next_location_in_meters = calculateSquareSideWithRadius($radius, $overlap);
	
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
		$settings = takeSnapShot($current_lat, $current_lng, $settings, $grid_lat, $grid_lng);
		++$grid_lng;
		//go from left to right
		while ($current_lng < $southwest_lng) {
			
			$current_lng += $move_to_next_lng;
			$settings = takeSnapShot($current_lat, $current_lng, $settings, $grid_lat, $grid_lng);
			++$grid_lng;
		}

		$current_lat -= $move_to_next_lat;
		$current_lng = $northeast_lng;
		$grid_lng = 1;
		++$grid_lat;
	}

	//one extra in lat:
	$settings = takeSnapShot($current_lat, $current_lng, $settings, $grid_lat, $grid_lng);
	++$grid_lng;
	while ($current_lng < $southwest_lng) {
		
		$current_lng += $move_to_next_lng;
		$settings = takeSnapShot($current_lat, $current_lng, $settings, $grid_lat, $grid_lng);
		++$grid_lng;
	}

	return $settings;
}

function takeSnapShot($current_lat, $current_lng, $settings, $grid_lat, $grid_lng) {
	echo "<br>\r\n$grid_lat$grid_lng";
	echo "<br>*** CLICK ".$settings["clicks"]." ! *** ".$current_lat . " - " . $current_lng;
	echo "<Br>";

	$GoogleMapsRequest = new GMA;
	$GoogleMapsRequest->url_required_parts["location_x"] = $current_lat;
	$GoogleMapsRequest->url_required_parts["location_y"] = $current_lng;
	$GoogleMapsRequest->url_required_parts["radius"] = $settings["radius"];

	$GoogleMapsRequest->url_optional_parts["keyword"] = $settings["keyword"];
	
	//this works only if we have google credits to spend:
	$settings["clicks"] ++ ;
	if ($settings["run"]) {
		//echo "run";
		$output = $GoogleMapsRequest->do_one_request();
		useGoogleOutput($output, $current_lat, $current_lng, $settings);
	}
	sleep(0.1);

	return $settings;
}

function useGoogleOutput($output, $current_lat, $current_lng, $settings) {
	$results = $output->results;
	
	foreach ($results as $key => $result) {
		//echo "<pre>";
		//print_r($result);
		//echo "</pre>";
	

		//@@@@@@@@@@@@@@if ($key == 19) {$zoom_in = TRUE }; //functie bouwen voor zoom_in $zoom_link = 

		$record = mapGoogleResultToDatabase($result, $current_lat, $current_lng, $settings);
		//status en key opslaan (om later te controleren of er meer dan 20 resultaten zijn)
		
		$record->search_status = $output->status;
		$record->search_number = $key;
		
		$record->types = flattenArray($record->types);
	
		//echo "<h1>OUTPUT</h2>";
		//echo $result->search_status;
		//echo "<br>";
		//echo $result->search_number;
		//echo "<br>";
		//echo "<pre>";
		//print_r($record->toArray());
		//echo "</pre>";
	
		$record->save();
	}

	//$allNames = [];
	//foreach ($results as $key => $result) {
	//	$allNames[$key] = $record->name;
	//}
//
	//echo "all names:";
	//echo "<pre>";
	//print_r($allNames);
	//echo "</pre>"; 
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

function mapGoogleResultToDatabase($result, $current_lat, $current_lng, $settings) {
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

	$record->search_lat = $current_lat;
	$record->search_lng = $current_lng;
	$record->search_radius = $settings["radius"];
	$record->search_keyword = $settings["keyword"];
	$record->search_place = $settings["place"];
	$record->search_overlap = $settings["overlap"];

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

	   $table->float('search_lat', 20, 15)->nullable();
	   $table->float('search_lng', 20, 15)->nullable();
	   $table->string('search_radius')->nullable();
	   $table->string('search_keyword')->nullable();
	   $table->string('search_overlap')->nullable();
	   $table->string('search_place')->nullable();

	   $table->string('search_status')->nullable();
	   $table->string('search_number')->nullable(); 
	});
}

function createSearchResults() {
	Capsule::schema()->create('search_results', function ($table) {
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

	   $table->float('search_lat', 20, 15)->nullable();
	   $table->float('search_lng', 20, 15)->nullable();
	   $table->string('search_radius')->nullable();
	   $table->string('search_keyword')->nullable();
	   $table->string('search_overlap')->nullable();
	   $table->string('search_place')->nullable();

	   $table->string('search_status')->nullable();
	   $table->string('search_number')->nullable();
	});
}

?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.6/css/bootstrap.min.css" integrity="sha384-rwoIResjU2yc3z8GV/NPeZWAv56rSmLldC3R/AZzGRnGxQQKnKkoFVhFQhNUwEyJ" crossorigin="anonymous">
  <style type="text/css">

body, html {
    height: 100%;
}

.form-group {
	opacity: 0.92;
    filter: alpha(opacity=92); /* For IE8 and earlier  */
}

.bg { 
    /* The image used */
    background-image: url("https://previews.123rf.com/images/ofchina/ofchina1201/ofchina120100299/11966810-Monkey-fun-cartoon-animal-jungle-banana-food-Stock-Vector.jpg");*/

    /* Full height */
    height: 50%; 

    /* Center and scale the image nicely */
    background-position: center;
    background-repeat: no-repeat;
    background-size: cover;
    background-opacity: 0.2;

}
pre {
	background-color: #FFFFFF;
	opacity: 0.92;
    filter: alpha(opacity=92); /* For IE8 and earlier  */
}
.explanation {
	background-color: #FFFFFF;
	opacity: 0.92;
}

.bginnerwrapper {
	background-color: #FFFFFF;
	opacity: 0.92;
}

.bgwrapper { 
    /* The image used */
    background-image: url("https://images.fineartamerica.com/images-medium-large-5/tree-of-dreams-paulo-zerbato.jpg");*/

    /* Full height */
    height: 100%; 

    /* Center and scale the image nicely */
    background-position: center;
    background-repeat: no-repeat;
    background-size: cover;
}
  </style>

  </head>
  <body>
  	<br>
  	<div class="container bg">
  <!-- Content here -->
<?php 
if (empty($_GET["places"])) 
{ 
?>
<br><br><br><br><h1>Choose yer configuration</h1>
<hr>
<form action="index.php" method="GET">
	<?php 
	foreach ($settings["user_input"] as $key => $user_input) { 
		if (gettype($user_input) == 'array') {
		?>
		<div class="form-group">
			<label for="exampleFormControlSelect2"><?php echo $key; ?></label>
			<select multiple="multiple" style='height: 100%;' size=<?php echo count($user_input); ?> class="form-control" id="		exampleFormControlSelect2" name="<?php echo $key; ?>[]">
			<?php foreach ($user_input as $key2 => $value) { 
				echo "<option>".$value."</option>";
			} ?>
			</select>
		</div>  
		<?php 
		} //end if
		else { ?>
			<div class="form-group">
			<label for="textexample"><?php echo $key; ?></label>
			<input type="text" class="form-control" id="textexample" value="<?php echo $user_input; ?>" name="<?php echo $key; ?>">
			</div>
		<?php } //end else
	} //end foreach
	?>
	<button type="submit" class="btn btn-primary">Start crawling</button>
</form><br>
<div class="explanation"><br>
<h2>Yer want sum explaining?</h2>
<p>This form is used to select settings with which to "scrape" the google maps web services. We`ve set up coordinates around the city`s shown here, and made certain keywords availble to choose from. (Made for project: 2017.031 Pyrrhula Research Consultants - Big data B5) Per city, the script will generate a square, and make a grid within the square with a radius of <?php echo $settings["radius"]; ?> per square. We want a small overlap between those squares to make sure we get everything. The overlap starts at <?php echo $settings["overlap"]; ?> % of the previous square.</p><p>IMPORTANT!</p><p>Only a certain amount of API calls can be made per day. At the moment 1000, later on more. Because of this you can test run the script. With a test run, there will be made NO API CALLS. You can use this to see how many calls willl be made with the selected options. (scoll all the way to the bottom and check the last "CLICK") When you`re amount of clicks is close to the available daily amount, set "test" to "false" and press submit. Now the API calls will be made and saved to the database. The key that is used is linked to the gmail account of dialogicdevelopers@gmail.com. You can check the API / developers console to confirm the amount of API calls being used. For more information contact Wouter. --> koppers@dialogic.nl
<br><br></p></div>


</div>
<?php 
} else {  
	echo "<br><br><br><br><h1>yer chosen settings</h1><hr>";
	echo "<div class='bgwrapper'>";
	echo "<div class='bginnerwrapper'>";
	echo "<pre class='bg2'>";
	print_r($_GET);
	echo "</pre>";
	echo "</div>";
	echo "</div>";
	$crawlThesePlaces = $_GET["places"];
	$keywords = $_GET["keywords"];
	if ($_GET["test"] == "false") {
		$settings["run"] = true;
	} else {
		$settings["run"] = false;
	}
	crawlWithUserInput($settings, $crawlThesePlaces, $keywords);
	//unset($_POST);   
}
?>

<script src="https://code.jquery.com/jquery-3.1.1.slim.min.js" integrity="sha384-A7FZj7v+d/sdmMqp/nOQwliLvUsJfDHW+k9Omg/a/EheAdgtzNs3hpfag6Ed950n" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/tether/1.4.0/js/tether.min.js" integrity="sha384-DztdAPBWPRXSA/3eYEEUWrWCy7G5KFbe8fFjk5JAIxUYHKkDx6Qin1DkWx51bBrb" crossorigin="anonymous"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.6/js/bootstrap.min.js" integrity="sha384-vBWWzlZJ8ea9aCX4pEW3rVHjgjt7zpkNpZk+02D9phzyeVkE+jo0ieGizqPLForn" crossorigin="anonymous"></script>
</body>
</html>