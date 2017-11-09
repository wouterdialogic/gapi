<?php 

use Illuminate\Database\Eloquent\Model as Eloquent;

Class Google_Place extends Eloquent {
	
	public function save() {
		echo "save me";
	}

	protected $fillable = 'title';

}