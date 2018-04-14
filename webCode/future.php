<?php
#
# https://google-developers.appspot.com/chart/interactive/docs/gallery/candlestickchart
#

error_reporting(1);

$fIn = "future.json";

$futureData = json_decode( file_get_contents( $fIn ) );


$graphSizes = array( "small" => array( "x" => 1400, "y" => 200 ),
		"normal" => array( "x" => 1400, "y" => 320 ),
		"micro" => array( "x" => 305, "y" => 176 ),
);
$size = "normal";

$divStyle = sprintf("width: %spx; height: %spx", $graphSizes[$size]["x"], $graphSizes[$size]["y"]);

# Massage the json data a bit first
# I want a structure that is [day, count]

$data = array();
foreach ( $futureData as $channel => $showdata ) {
	#print( $show . "<br/>" );
	foreach( $showdata as $shows ) {
		#print(sprintf("%s %s %s<br/>", $shows[1], date("Y-m-d\tH:i:s", $shows[1]), $shows[0]));
		list($year, $month, $day) = split('-', date("Y-m-j", $shows[1]));
		$month -= 1;
		$jsonDate = sprintf("(%s, %s, %s)", $year, $month, $day);
		$key = date("Y-m-d", $shows[1]);
		$dateStr = date("D, Y-m-d", $shows[1]);
		$hourStr = date("H", $shows[1]);
	
		#print($key . "<br/>");
		if (array_key_exists($key, $data)) {
			$data[$key]["count"] += 1;
			$data[$key]["showList"] .= "<br/>" . $shows[0];
		} else {
			#var_dump( array( 1, $shows[0] ) );
			$data[$key] = array( 
			"jsonDate" => $jsonDate, 
			"count" => 1, 
			"showList" => $shows[0], 
			"displayDate" => $dateStr,
			"hourList" => array(),
			);
		}
		$data[$key]["hourList"][$hourStr][$channel][] = $shows[0];
	}
}

ksort( $data );
#var_dump($data);

?>
<html>
	<head>
	<title>Shows in the Future</title>
		<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
		<script type="text/javascript">
	google.charts.load("current", {packages:["calendar"]});
	google.charts.setOnLoadCallback(drawChart);

function drawChart() {
	var dataTable = new google.visualization.DataTable();
	dataTable.addColumn({ type: 'date', id: 'Date' });
	dataTable.addColumn({ type: 'number', id: 'Number to post' });
	dataTable.addRows([

<?php
	$googleData = array();
	foreach( $data as $date => $struct ) {
		array_push($googleData, sprintf("[new Date%s, %s]",
				$struct["jsonDate"], $struct["count"]
		));
	}
	print_r(implode(",\n", $googleData)."\n");
?>
		]);

		var chart = new google.visualization.Calendar(document.getElementById('chart_div'));

		var options = {
			title: "The Future",
			height: <?=$graphSizes[$size]["y"]?>,
		};

		chart.draw(dataTable, options);
	}
		</script>
	</head>
	<body>
		<div id="chart_div" style="<?=$divStyle ?>"></div> 
	<table border=1>
<?php
# get the first data
	foreach( $data as $date => $struct ) {
		$tsStart = strtoTime( $date );
	
		$dowPrev = date( "w", $tsDisplay );
		break;
	}
# get then end date
	end( $data );
	$tsEnd = strtoTime( key($data) ) + 86400;
	reset( $data );
# work through the days
	$itemCount = 0;

	for( $ts = $tsStart; $ts <= $tsEnd; $ts += 86400 ) {
		$dateKey = date("Y-m-d", $ts );
		if( $itemCount % 7 == 0 ) {
			print("<tr>");
		}

		print("<td style='vertical-align:top'>");

		$struct = $data[$dateKey];
		if( ! is_null( $struct ) ) {
			print( "\n<table border=1>");
			print( sprintf( "<tr><td colspan=3>%s</td></tr>", $struct["displayDate"] ) );
			ksort( $struct["hourList"] );
			foreach( $struct["hourList"] as $hour => $channels ) {
				$showCount = 0;
				$numShows = count( $channels );
				ksort( $channels );
				print("<tr>");
				print( sprintf( "<td rowspan=%s>%s</td>", $numShows, $hour ) );
				
				foreach( $channels as $channel => $showInfo ) {
					if( $showCount > 0 ) {
						print( "<tr>" );
					}
					print( sprintf( "<td>%s</td><td>%s</td></tr>",
						$channel,
						wordwrap( $showInfo[0], 8, "<br/>", true ) ) );
					$showCount ++;
				}
				
				print("</tr>");
		
			}

			#var_dump($struct);
			print("</table>");
		}

		print("</td>");
		$itemCount++;
		if ($itemCount % 7 == 0) {
			print("</tr>\n");
		}
		#if (array_key_exists($key, $data)) {
		
	}

#original
/*
	foreach( $data as $date => $struct ) {
		print(sprintf("<tr><td>%s</td><td>%s</td></tr>\n",
				$struct["displayDate"], $struct["showList"]));
	}
*/
?>
	</table>
	</body>
</html>

