<?php 

namespace Classes;

class GoogleMapsApi {
	
	public $url_required_parts = [
		'google_api_places' => 'https://maps.googleapis.com/maps/api/place/nearbysearch/',
		'format' => 'json',
		'key' => 'AIzaSyB4LeZepSN7SlYT3ayLJjFJ7cp06eXImWE',
		'location_x' => '51.4736171',
		'location_y' => '5.6180959',
		'radius' => 5000,
	];	

	public $url_optional_parts = [
		//'type' => 'theater',
		'keyword' => '',
		'language' => 'nl',
		'region' => 'nl',
	];

//	json?
//key=AIzaSyB4LeZepSN7SlYT3ayLJjFJ7cp06eXImWE
//location=51.4736171,5.6180959
//latitude,longitude
//
//radius=1000
//
//optional:
//type=bar
//https://google-developers.appspot.com/places/web-service/supported_types
//
////rankby=distance werkt alleen indien keyword opgegeven.
//keyword=
//language=nl
//region=nl
//
//https://maps.googleapis.com/maps/api/place/nearbysearch/json?key=AIzaSyB4LeZepSN7SlYT3ayLJjFJ7cp06eXImWE&location=51.4736171,5.61809//59&radius=1000&type=bar&rankby=distance&language=nl&region=nl

	public function create_url() {
		$url = '';
		foreach ($this->url_required_parts as $key => $url_required_part) {
			if (empty($url_required_part)) {
				echo "<br>No value given for ".$key." Please adjust.";
				return;
			} 
		}

		$url = $this->url_required_parts['google_api_places'].
			$this->url_required_parts['format'].
			"?key=".		$this->url_required_parts['key'].
			"&location=".	$this->url_required_parts['location_x'].
			",".			$this->url_required_parts['location_y'].
			"&radius=".		$this->url_required_parts['radius'];

		foreach ($this->url_optional_parts as $key => $url_optional_part) {
			if (!empty($url_optional_part)) {
				$url .= "&$key=$url_optional_part";
			}
		} 

		echo "<br>URL: ".$url;

		return $url;
	}

	public function send_url() {

	}

	public function process_completed_requests() {

	}

	public function do_one_request() {
		$link = $this->create_url();

		$output = $this->send_request_to_google($link);
	
		return $output;
	}

	public function send_request_to_google($url) {
		$arrContextOptions=array(
		    //"ssl"=>array(
		    //    "verify_peer"=>false,
		    //    "verify_peer_name"=>false,
		    //),
		);  
		//echo "<br><xmp>good: https://maps.googleapis.com/maps/api/place/nearbysearch/json?key=AIzaSyB4LeZepSN7SlYT3ayLJjFJ7cp06eXImWE&location=51.4736171,5.6180959&radius=5000&type=bar&language=nl&region=nl</xmp>";
		//echo "<br><br><xmp>new: ".$url."</xmp>";

		//$response = file_get_contents($url, false, stream_context_create($arrContextOptions));
		$response = file_get_contents($url);

		$json_response = json_decode($response);

		$not_json_response = json_decode(json_encode($object), true);


		//foreach ($json_response->results as $key => $result) {
		//	echo $result->name;
		//} 
//
		return $json_response;
		// echo $response;
	}

}
//$GMA = new GoogleMapsApi;
//
//$output = $GMA->do_one_request();
//
//$results = $output->results;
//
//$firstResult = $results[0];
//
//echo "<pre>";
//print_r($firstResult);
//echo "</pre>";
