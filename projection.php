<?php

$measure = "newCasesByPublishDate";
$opts = array( 
	'filters'=>'areaType=nation',
	'structure'=>'{"date":"date","areaName":"areaName","'.$measure.'":"'.$measure.'"}',
	'format'=>'json' );
$url = "https://api.coronavirus.data.gov.uk/v1/data?".http_build_query($opts) ;

#$json = file_get_contents( $url );
#print $url;
$data = json_decode( gzdecode( file_get_contents( $url )), true );
$dates = array();
foreach( $data["data"] as $record ) {
	$dates[$record["date"]]["date"]=$record["date"];
	@$dates[$record["date"]]["stat"]+=$record[$measure];
	$dates[$record["date"]]["nation"][$record["areaName"]]=$record[$measure];
}
krsort( $dates );

# at time_t
foreach( $dates as &$date ) {
	$date["time_t"] = strtotime( $date["date"] );
}

$DAY = 24*60*60;
# add rolling average
foreach( $dates as &$date ) {
	$days = 0;
	$total = 0;
	for( $i=-6; $i<=0; $i++ ) {
		$date_i = date( "Y-m-d", $date["time_t"] + $i*$DAY );
		if( array_key_exists($date_i,$dates) ) {
			$days++;
			$total += $dates[$date_i]["stat"];
		}
	}
	$date["7day"]=$total/$days;
	$date["7day_days"]=$days;
}

foreach( $dates as &$date ) {
	$date_i = date( "Y-m-d", $date["time_t"] -7*$DAY );
	if( !array_key_exists( $date_i, $dates ) ) { continue; }
	if( !array_key_exists( "7day", $dates[$date_i] ) ) { continue; }
	if( $dates[$date_i]["7day"] == 0 ) { continue; }
	$date["7daychange"] = $date["7day"]/$dates[$date_i]["7day"];
}




$max = array(
	"stat"=>0,
	"7day"=>0,
	"7daychange"=>0,
);
$min = array (
	"7daychange"=>1,
);
$MAXGROW = 5;
foreach( $dates as &$date ) {
	if( $date["7day"] > $max["7day"] ) {
		$max["7day"] = $date["7day"];
	}
	if( $date["7daychange"] > $max["7daychange"] && $date["7daychange"] < $MAXGROW ) {
		$max["7daychange"] = $date["7daychange"];
		
	}
	if( $date["7daychange"] < $min["7daychange"] && $date["7daychange"]>0 ) {
		$min["7daychange"] = $date["7daychange"];
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
.data {
	text-align: right;
	padding: 2px 1em;
}
.lockdown {
	background-color: #ffffcc;
}
.weekend {
	background-color: #f0f0f0;
}
.weekend.lockdown {
	background-color: #f0f0cc;
}
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
	padding:0px 1em;
	white-space: nowrap;
}
th {
	border-right: solid 1px white;
	padding: 2px 1em;
	white-space: nowrap;
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
	
</style>
<div class="container" >
<h1>COVID Stats and projection</h1>
<p>This page uses data from the UK government API. Specifically it's using "newCasesByPublishDate" for the UK. Download: <a href="<?php print $url;?>">Raw JSON data</a>.</p>
<p>Daily average for a date is the average of that date and the 6 days proceeding it.</p>
<p>Daily change is the change between a date's (7 day averaged) cases and the value for 7 days before that, so seeing the increase or decrease in the number of average cases-per-day this week to average cases-per-day last week.</p>
<p>The next bit shows the time it would take for the number of cases to <span class='bad'>double</span> based on this change (or <span class='good'>halve</span> if it's negative).</p>
<p>The last column shows a projection if cases kept increasing from that date, at that rate for 4 weeks. This is the "what will happen if nothing changes", or just plain how worrying the situation is.</p>
<p>Yellow tint shows days of national lockdowns or widespread Tier 4.</p>
<p>My <a href='https://github.com/cgutteridge/ukcovidcities'>code is on github</a> if you want to check my working or play with a copy.</p>
<table>
<tr class="" style='background-color: #000;color: #fff;'>
<th class='first'>Date</th>
<!--<th class=' data'>New Cases</th>-->
<th class=' '>7 Day<br />Average</th>
<th class=' '></th>
<th class=' '>Daily<br />Change</th>
<th class=' '></th>
<th class=' '>Time for cases to<br> <span class='bad'>double</span> (or <span class='good'>halve</span>)</th>
<th class=' last' colspan='2'>28 day projection<br />for thousand cases/day</th>
</tr>
<?php
foreach( $dates as $iso=>$date ) {
	$class = "";
	//if ( $date["date"] < "2020-07-01" ) { continue; }
	if( !array_key_exists( "7daychange", $date ) ) { continue; }
	$daychange = pow($date["7daychange"],1/7);
	if( $iso >= "2020-03-23"  && $iso <= "2020-05-13" ) { $class = "lockdown lockdown1"; }
	if( $iso >= "2020-11-05"  && $iso <= "2020-12-02" ) { $class = "lockdown lockdown2"; }
	if( $iso >= "2020-12-26"  && $iso <= "2030-11-02" ) { $class = "lockdown tier4"; }
	if( date("l", $date["time_t"])=="Saturday" || date("l", $date["time_t"])=="Sunday" ) {
		$class.=" weekend";
	}
	print "<tr class='$class' >";
	print "<td class='first'>";
	print date("M jS", $date["time_t"]);
	print "</td>";
	//print sprintf( "<td class=' data'>%d</td>\n", $date["stat"] );
	print sprintf( "<td class=' data'>%d</td>\n", $date["7day"] );
	print sprintf( "<td class=''><div class='bar' style='width: %dpx;'>&nbsp;</div></td>", 100*$date["7day"]/$max["7day"] );
	#print sprintf( "<td class=' data'>x%0.4f</td>\n", $daychange );

	print sprintf( "<td class=' data'>%d%%</td>\n", ($daychange*100)-100 );

	print sprintf( "<td class=''>" );
	$gwidth = 200;
	$range = 0;
	$w1 = 0;
	$range += ($max["7daychange"]-1);
	if( $min["7daychange"]>0 ) {
		$range += (1/$min["7daychange"]-1);
		$w1 = (1/$min["7daychange"]-1)/$range*$gwidth;
	}
	
	if( $date["7daychange"] < 1 ) {
		$v = floor((1/$date["7daychange"]-1)/$range*$gwidth);
		print sprintf( "<div class='  nobar' style='width: %dpx;'>&nbsp;</div>", $w1-$v );
		print sprintf( "<div class='goodbar' style='width: %dpx;'>&nbsp;</div>", $v );
		print sprintf( "<div class=' midbar' style='width: 1px;'>&nbsp;</div>" );
	} else {
		$v = floor(($date["7daychange"]-1)/$range*$gwidth);
		if( $date["7daychange"]-1>$MAXGROW ) {
			print "very high";
		} else {
			print sprintf( "<div class='  nobar' style='width: %dpx;'>&nbsp;</div>", $w1 );
			print sprintf( "<div class=' midbar' style='width: 1px;'>&nbsp;</div>" );
			print sprintf( "<div class=' badbar' style='width: %dpx;'>&nbsp;</div>", $v );
		}
	}
	print sprintf( "</td>" );

	print sprintf( "<td class='data'>" );
	if( $daychange < 1 ) {
		# time to halve 
		# c^x = 0.5
		# log(c^x)=log(0.5)
		# x log(c)=log(0.5)
		# x= log(0.5)/log(c)
		$days = log(0.5)/log($daychange);
		if( $days>=100 ) {
			print sprintf( "<div style='color: #333 !important'>stable</div>\n" );
		} else {
			print sprintf( "<div style='color: #3c3 !important'>%d days</div>\n", $days );
		}
	}
	if( $daychange > 1 ) {
		$days = log(2)/log($daychange);
		if( $days<2 ) {
			print sprintf( "<div style='color: #f33 !important'>%d hours</div>\n", $days*24 );
		} elseif( $days>=100 ) {
			print sprintf( "<div style='color: #333 !important'>stable</div>\n" );
		} else {
			print sprintf( "<div style='color: #f33 !important'>%d days</div>\n", $days );
		}
	}
	print sprintf( "</td>" );
		
	$proj = floor(($date["stat"]*pow($daychange,7*4))/1000);
	print sprintf( "<td class=' data'>" );
	print sprintf( "%d K", $proj );
	print "</td>\n";
	print "<td>\n";
	$v = $proj/2;
	if( $v>200 ) {
		print "very high";
	} else {
		print sprintf( "<div class='bar' style='width: %dpx;'>&nbsp;</div>", $v );
	}
	print "</td>\n";
	print "</tr>\n";
}
?>
</table>
    </div>
  </body>
</html>
