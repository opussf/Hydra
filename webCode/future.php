<?php
#
# https://google-developers.appspot.com/chart/interactive/docs/gallery/candlestickchart
#

error_reporting(1);

$fIn = "future.json";

$futureData = json_decode( file_get_contents( $fIn ) );


$graphSizes = array( "small" => array( "x" => 1400, "y" => 200 ),
		"normal" => array( "x" => 1400, "y" => 350 ),
		"micro" => array( "x" => 305, "y" => 176 ),
);
$size = "normal";

$divStyle = sprintf("width: %spx; height: %spx", $graphSizes[$size]["x"], $graphSizes[$size]["y"]);

# Massage the json data a bit first
# I want a structure that is [day, count]

$data = array();
foreach ( $futureData as $show => $showdata ) {
	#print( $show . "<br/>" );
	foreach( $showdata as $shows ) {
		#print(sprintf("%s %s %s<br/>", $shows[1], date("Y-m-d\tH:i:s", $shows[1]), $shows[0]));
		list($year, $month, $day) = split('-', date("Y-m-j", $shows[1]));
		$month -= 1;
		$jsonDate = sprintf("(%s, %s, %s)", $year, $month, $day);
		$key = date("Y-m-d", $shows[1]);
	
		#print($key . "<br/>");
		if (array_key_exists($key, $data)) {
			$data[$key][1] += 1;
			$data[$key][2] .= "<br/>" . $shows[0];
		} else {
			#var_dump( array( 1, $shows[0] ) );
			$data[$key] = array( 0 => $jsonDate, 1 => 1, 2 => $shows[0] );
		}
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
				$struct[0], $struct[1]
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
	foreach( $data as $date => $struct ) {
		print(sprintf("<tr><td>%s</td><td>%s</td></tr>\n",
				$date, $struct[2]));
	}
		
?>
	</table>
	</body>
</html>

