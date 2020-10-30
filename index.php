<?php

$opts = array( 
	'filters'=>'areaType=nation',
	'structure'=>'{"date":"date","areaName":"areaName","cumDeaths28DaysByDeathDate":"cumDeaths28DaysByDeathDate"}',
	'format'=>'json' );
$deathsURL = "https://api.coronavirus.data.gov.uk/v1/data?".http_build_query($opts) ;
$json = file_get_contents( $deathsURL );
$data = json_decode( gzdecode( file_get_contents( $deathsURL )), true );
$dates = array();
foreach( $data["data"] as $record ) {
	$dates[$record["date"]]["date"]=$record["date"];
	$dates[$record["date"]]["deaths"]+=$record["cumDeaths28DaysByDeathDate"];
	$dates[$record["date"]]["nation"][$record["areaName"]]=$record["cumDeaths28DaysByDeathDate"];
}
krsort( $dates );


$city_data = json_decode( file_get_contents( "cities.json" ), true );
$cities = array();
foreach( $city_data["results"]["bindings"] as $record ) {
	$city = array();
	foreach( $record as $key=>$value ) {
		$city[$key] = $value["value"];
	}
	$cities [$city["place"]]= $city;
}

foreach( $dates as &$date ) {

	# parse the date
	$date["time_t"] = strtotime( $date["date"] );
	
	# add the best city
	$best = null;
	foreach( $cities as $city ) {
		if( $city["pop"] > $date["deaths"] ) { break; }
		$best = $city;
	}
	$date["city"] = $best;
}

?>
<!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/css/bootstrap.min.css" integrity="sha384-TX8t27EcRE3e/ihU7zmQxVncDAy5uIKz4rEkgIXeMed4M0jlfIDPvg6uqKI2xXr2" crossorigin="anonymous">

    <title>UK COVID Death in perspective <?php print $last["date"] ?></title>
  </head>
  <body>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ho+j7jyWK8fNQe+A12Hb8AhRq26LrZ/JpcUGGOn+Y7RsweNrtN/tE3MoK7ZeZDyx" crossorigin="anonymous"></script>

<div class="container">
    <h1>UK COVID Death in perspective <?php print $last["date"] ?></h1>

<p>This page shows the death total in the UK compared to the sizes of UK cities (according to Wikipedia)</p>
<div class="container">
<?php
$city = null;
$city_uri = null;
foreach( $dates as $date ) {
	if( $city == null && @$date["city"] ) {
		$city_uri = $date["city"]["place"];
		$city = $date["city"];
	}
	if( $city_uri != $date["city"]["place"] ) {
		print "<div class='row my-3'>";
		print "<div class='card'>";

		print "<div class='card-body'>";
		print "<img src='".$city["image"]."' class='rounded float-right ml-3' style='width:30%' alt='' >";
		print "<h3 class='card-title'>";
		print $city["label"];
		print "</h3>";
		print "<p>Population ";
		print $city["pop"];
		print "</p>";
		print "<p>";
		print $city["abstract"];
		print "</p>";
		print "</div>";#card-body
#    [place] => http://dbpedia.org/resource/Salisbury
#    [pop] => 40302
#    [image] => http://commons.wikimedia.org/wiki/Special:FilePath/Salisbury_Cathedral_from_Old_George_Mall.jpg
#    [abstract] => 
#    [label] => Salisbury
#    [lat] => 51.074
#    [long] => -1.7936
		print "</div>"; #card
		print "</div>"; #row
		$city_uri = $date["city"]["place"];
		$city = $date["city"];
	}


	print "<div class='row'>";
	print "<div class='col-sm'>".date("F jS", $date["time_t"])."</div>";
	print "<div class='col-sm'>".$date["deaths"]."</div>";
	#print "<div class='col-sm'>".$date["city"]["label"]." (population ".$date["city"]["pop"].")</div>";
	print "</div>";
}
?>
</div>
  </body>
</html>
