<?php ?>

<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false&libraries=geometry"></script>

<script>
//var p1 = new google.maps.LatLng(51.475837, 5.298619);
//var p2 = new google.maps.LatLng(51.475875, 5.313065);
//
//console.log(calcDistance(p1, p2));
//
var distance1kmInLat = 0.008983;
var distance1kmInLng = 0.014423;
//
//var p1 = new google.maps.LatLng(51.475837, 5.298619);
//var p2 = new google.maps.LatLng(51.466870, 5.298549);
//
//console.log(calcDistance(p1, p2));

var lat = 51.475837;
var lng = 5.298619;

//latcheck: 
//for (var i = 100; i >= 0; i--) {
//
//	var p1 = new google.maps.LatLng(lat, lng);
//	var newLng = 5.298619 + i * distance1kmInLng;
//	var p2 = new google.maps.LatLng(lat, newLng);
//
//	console.log(calcDistance(p1, p2));
//}

for (var i = 100; i >= 0; i--) {

	var p1 = new google.maps.LatLng(lat, lng);
	var newLat = 51.475837 + i * distance1kmInLat;
	var p2 = new google.maps.LatLng(newLat, lng);

	console.log(calcDistance(p1, p2));
}


//calculates distance between two points in km's
function calcDistance(p1, p2) {
  return (google.maps.geometry.spherical.computeDistanceBetween(p1, p2) / 1000).toFixed(2);
}

</script>