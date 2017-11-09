<?php

namespace Models;

use Illuminate\Database\Eloquent\Model as Model;

Class Place extends Model {

	public $connection = 'default';
	
	public static $name = "wouter";

	public $timestamps = false;

	public $fillable = [
		//"title", 
		"geometry_location_lat", 
		"geometry_location_lng", 
		"geometry_viewport_northeast_lat", 
		"geometry_viewport_northeast_lng", 
		"geometry_viewport_southwest_lat", 
		"geometry_viewport_southwest_lng", 
		"icon", 
		"google_id", 
		"name", 
		//"opening_hours", 
		//"opening_hours_weekday_text", 
		//"photos_photo_reference", 
		"place_id", 
		"rating", 
		"reference", 
		"scope", 
		"types", 
		"vicinity",
	];

	public $modelResponseMapping = [
		//"title" => "title",
		"geometry_location_lat" => "geometry->location->lat",
		"geometry_location_lng" => "geometry->location->lng",
		"geometry_viewport_northeast_lat" => "geometry->viewport->northeast->lat",
		"geometry_viewport_northeast_lng" => "geometry->viewport->northeast->lng",
		"geometry_viewport_southwest_lat" => "geometry->viewport->southwest->lat",
		"geometry_viewport_southwest_lng" => "geometry->viewport->southwest->lng",
		"icon" => "icon",
		"google_id" => "id",
		"name" => "name",
		"opening_hours_weekday_text" => "opening_hours->weekday_text",
		//"photos_photo_reference",>
		"place_id" => "place_id",
		"rating" => "rating",
		"reference" => "reference",
		"scope" => "scope",
		"types" => "types",
		"vicinity" => "vicinity",
	];

	public function modelResponseMapping($result) {
		$this->title = $result->title;
		$this->geometry_location_lat = $result->geometry->location->lat;
		$this->geometry_location_lng = $result->geometry->location->lng;
		$this->geometry_viewport_northeast_lat = $result->geometry->viewport->northeast->lat;
		$this->geometry_viewport_northeast_lng = $result->geometry->viewport->northeast->lng;
		$this->geometry_viewport_southwest_lat = $result->geometry->viewport->southwest->lat;
		$this->geometry_viewport_southwest_lng = $result->geometry->viewport->southwest->lng;
		$this->icon = $result->icon;
		$this->id = $result->id;
		$this->name = $result->name;
		//$this->opening_hours_weekday_text = $result->
		//$this->photos_photo_reference = $result->
		$this->place_id = $result->place_id;
		$this->rating = $result->rating;
		$this->reference = $result->reference;
		$this->scope = $result->scope;
		$this->types = $result->types;
		$this->vicinity = $result->vicinity;
	}

	
//$record->title = $result->title
//$record->geometry_location_lat = $result->geometry->location->lat;
//$record->geometry_location_lng = $result->geometry->location->lng;
//$record->geometry_viewport_northeast_lat = $result->geometry->viewport->northeast->lat;
//$record->geometry_viewport_northeast_lng = $result->geometry->viewport->northeast->lng;
//$record->geometry_viewport_southwest_lat = $result->geometry->viewport->southwest->lat;
//$record->geometry_viewport_southwest_lng = $result->geometry->viewport->southwest->lng;
//$record->icon = $result->icon;
//$record->id = $result->id;
//$record->name = $result->name;
////$record->opening_hours_weekday_text = $result->
////$record->photos_photo_reference = $result->
//$record->place_id = $result->place_id;
//$record->rating = $result->rating;
//$record->reference = $result->reference;
//$record->scope = $result->scope;
//$record->types = $result->types;
//$record->vicinity = $result->vicinity;


}