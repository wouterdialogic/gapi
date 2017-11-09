<?php

namespace Models;

use Illuminate\Database\Eloquent\Model as Model;

Class Order extends Model {



	public $connection = 'default';
	
	public static $name = "wouter";

	public $timestamps = false;

	public $fillable = ["title"];
}