<?php
$dates = array();
$metrics = [ "maleCases","femaleCases"];
$headingsmap = [];
foreach($metrics as $metric ) {
	$areaType = "nation";
	$areaName = "England";
	$filters = "areaType=nation";
	if( @$areaName ) { $filters .= ';areaName='.$areaName; }
	$opts = array( 
		'filters'=>$filters,
		'structure'=>'{"date":"date","areaName":"areaName","'.$metric.'":"'.$metric.'"}',
		'format'=>'json' );
	$url = "https://api.coronavirus.data.gov.uk/v1/data?".http_build_query($opts) ;
	print "<!--\n$url\n-->\n";

	#$json = file_get_contents( $url );
	#print $url;
	$data = json_decode( gzdecode( file_get_contents( $url )), true );
	foreach( $data["data"] as $record ) {
		$dates[$record["date"]]["date"]=$record["date"];
		foreach( $record[$metric] as $srecord ) {
			$age = $srecord["age"];
			if( $age == "5_to_9" ) { $age = "05_to_09"; }
			if( $age == "0_to_4" ) { $age = "00_to_04"; }
			$heading = $record["areaName"]."_".$age."_".$metric;
			$dates[$record["date"]]["div"][$heading] = $srecord;
			$headingsmap[$heading] = 1;		
		}
	}
}
krsort( $dates );

$headings = array_keys( $headingsmap );
sort($headings );

# at time_t
foreach( $dates as &$date ) {
	$date["time_t"] = strtotime( $date["date"] );
}

$DAY = 24*60*60;
foreach( $dates as &$date ) {
	$week_ago = date( "Y-m-d", $date["time_t"] - 7*$DAY );
	if( !array_key_exists( $week_ago, $dates ) ) { continue;}
	if( !array_key_exists( "div", $date ) ) { continue; }
#print_r( $dates[$week_ago] );
	foreach( $date["div"] as $div=>&$srecord ) {
		if( !array_key_exists( 'div', $dates["$week_ago"] )) { continue; }
		if( !array_key_exists( $div, $dates["$week_ago"]['div'] )) { continue; }
		$srecord["rate_weekly_change"] = $srecord["rate"]-$dates[$week_ago]['div'][$div]["rate"];
		$srecord["value_weekly_change"] = $srecord["value"]- $dates[$week_ago]['div'][$div]["value"];
	}
}

foreach( $dates as &$date ) {
	$twoweek_ago = date( "Y-m-d", $date["time_t"] - 14*$DAY );
	if( !array_key_exists( $twoweek_ago, $dates ) ) { continue;}
	if( !array_key_exists( "div", $date ) ) { continue; }
#print_r( $dates[$twoweek_ago] );
	foreach( $date["div"] as $div=>&$srecord ) {
		if( !array_key_exists( 'div', $dates["$twoweek_ago"] )) { continue; }
		if( !array_key_exists( $div, $dates["$twoweek_ago"]['div'] )) { continue; }
		if( !array_key_exists( 'rate_weekly_change', $dates["$twoweek_ago"]['div'][$div] )) { continue; }
		$srecord["rate_weekly_change_change"] = $srecord["rate_weekly_change"]-$dates[$twoweek_ago]['div'][$div]["rate_weekly_change"];
		$srecord["value_weekly_change_change"] = $srecord["value_weekly_change"]- $dates[$twoweek_ago]['div'][$div]["value_weekly_change"];
	}
}

$min = [];
$max = [];
$stats = [ "rate_weekly_change", "value_weekly_change", "rate_weekly_change_change", "value_weekly_change_change"];
foreach( $dates as &$date ) { 
	if( !array_key_exists( "div", $date ) ) { continue; }
	foreach( $date["div"] as $div=>&$srecord ) {
		foreach( $stats as $stat ) {
			if( !array_key_exists( $stat, $srecord ) ) { continue; }
			if( !array_key_exists( $stat,$max)
			 || $srecord[$stat] > $max[$stat] ) { $max[$stat] = $srecord[$stat]; }
			if( !array_key_exists( $stat,$min)
			 || $srecord[$stat] < $min[$stat] ) { $min[$stat] = $srecord[$stat]; }
		}
	}
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

    <title>COVID stats</title>
  </head>
  <body>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ho+j7jyWK8fNQe+A12Hb8AhRq26LrZ/JpcUGGOn+Y7RsweNrtN/tE3MoK7ZeZDyx" crossorigin="anonymous"></script>
<style>
.good {
	color: #3f3;
}
.bad {
	color: #f33;
}
table {
    	border-spacing: 0px;
	border-collapse: collapse;
}
td {
	padding:0px 5px
	white-space: nowrap;
}
th {
	border-right: solid 1px white;
	padding: 15px 15px;
	white-space: nowrap;
	text-align: center;
}
th .text {
  writing-mode: vertical-rl;
  text-orientation: sideways-right;
}
.nobar, .goodbar, .midbar, .badbar, .bar {
	display: inline-block;
}
.nobar { 
}
.bar {
	background-color: #666;
}
.midbar { 
	width: 1px;
	background-color: #eee;
}
.badbar {
	background-color: #c33;
}
.goodbar {
	background-color: #3c3;
}
.event {
	border-top: solid 2px black;
	border-bottom: solid 2px black;
	text-align: center;
}	
</style>
<div class="" >
<table>
<tr class="" style='background-color: #000;color: #fff;'>
<th class='first'><div class='text'>Date</div></th>
<?php
foreach( $headings as $h ) { print "<th><div class='text'>$h</div></th>"; }
?>
</tr>
<?php
$stat = "rate_weekly_change_change";
foreach( $dates as $iso=>$date ) {
	$class = "";
	print "<tr>";
	print "<td>$iso</td>";
#print "<td><pre>";print_r( $date["div"] ); print "</pre></td>";
	foreach( $headings as $h ) { 
		$v = "";
		$col = 'ffffff';
		if( isset( $date["div"][$h][$stat] ) ) {
			$col = '000';
			$v = sprintf("%.1f",$date["div"][$h][$stat]);
			if( $v > 0 ) {
				$colr = log($v+1)/log($max[$stat]+1);
				$col = sprintf( "%02X%02X%02X", 255*$colr, 0, 0);
			}
			if( $v < 0 ) {
				$colr = log(-$v+1)/log(-$min[$stat]+1);
				$col = sprintf( "%02X%02X%02X", 0, 255*$colr, 0);
			}
		}
		print "<td style='background-color:#$col'>$v</td>"; 
	}
	print "</tr>";

}
?>
</table>
    </div>
  </body>
</html>
