<?php

// Function responsible for data gathering in SQL database
function add_hits($postID) {
	global $wpdb;
	$post_popularity_graph_table = $wpdb->prefix . 'post_popularity_graph';
	if (!preg_match('/bot|spider|crawler|slurp|curl|^$/i', $_SERVER['HTTP_USER_AGENT'])) { // If there is no hit_count with proper ID and visitor is not a bot proceed
		$result = $wpdb->query("INSERT INTO $post_popularity_graph_table (post_id, date) VALUES ($postID, NOW())"); // Adds to SQL table post ID, date and hit count
		$wpdb->query("DELETE FROM $post_popularity_graph_table WHERE date <= NOW() - INTERVAL 30 DAY"); // Removes database entry older than 30 days
	}
}

// Function responsible for displaying the chart  
function show_graph($postID, $numberofdays, $chartstyle, $haxistitle, $vaxistitle, $backgroundcolor, $chartcolor) {
	global $wpdb;
	$post_popularity_graph_table = $wpdb->prefix . 'post_popularity_graph';
	if ($wpdb->query("SELECT post_id FROM $post_popularity_graph_table WHERE post_id = $postID")) {
		$result = $wpdb->get_results("SELECT COUNT(post_id) FROM $post_popularity_graph_table WHERE post_id = $postID AND date >= DATE(DATE_SUB(NOW(), INTERVAL $numberofdays DAY)) GROUP BY CAST(date AS DATE)", ARRAY_A);
		$date = $wpdb->get_results("SELECT CAST(date AS DATE) FROM $post_popularity_graph_table WHERE post_id = $postID AND date >= DATE(DATE_SUB(NOW(), INTERVAL $numberofdays DAY)) GROUP BY CAST(date AS DATE)", ARRAY_A);
		
	$date1 = date("Y, m, d", strtotime("- $numberofdays day"));
	$date2 = date("Y, m, d");

// Function responsible for creation of date range
	function returnDates($fromdate, $todate) {
		$fromdate = DateTime::createFromFormat('Y, m, d', $fromdate);
		$todate = DateTime::createFromFormat('Y, m, d', $todate);
    		return new DatePeriod(
        	$fromdate,
        	new DateInterval('P1D'),
        	$todate->modify('+1 day')
    		);
	}
	
	$datePeriod = returnDates($date1, $date2);

// Gathering dates in arrays
	foreach($datePeriod as $dateLoop) {
		$dateLoop = $dateLoop->format('Y, m, d');
		$tablica[] = $dateLoop;
	}
	foreach($date as $dateMainLoop) {
		static $i = 0;
		$dateMainLoop = $date[$i]['CAST(date AS DATE)'];
		++$i;
		$dateMainLoop = DateTime::createFromFormat('Y-m-d', $dateMainLoop);
		$dateMainLoop = $dateMainLoop->format('Y, m, d');
		$tablica2[] = $dateMainLoop;
	}

?>
<!-- Google Charts script -->
    <script type="text/javascript" src="https://www.google.com/jsapi"></script>
    <script type="text/javascript">
                 google.load('visualization', '1', {packages: ['corechart']});
    google.setOnLoadCallback(drawChart);

    function drawChart() {

      var data = new google.visualization.DataTable();
      data.addColumn('date', 'Date');
      data.addColumn('number', 'Visits');

      data.addRows([
<?php
// Loop responsible for throwing of dates and data to javascript.
	for($i = 0; $i <= $numberofdays; ++$i){
		$value = $tablica[$i];
		$valueFormat = DateTime::createFromFormat('Y, m, d', $value);
		$year = $valueFormat->format('Y');
		$month = $valueFormat->format('m');
		$month = $month - 1;
		$day = $valueFormat->format('d');
		$valueSet = $year.", ".$month.", ".$day;
		if(in_array($value, $tablica2)){
			static $i2 = 0;
			$hitCount = $result[$i2]['COUNT(post_id)'];
			++$i2;
			echo "[new Date(".$valueSet."), ".$hitCount."],";
		}else{
			echo "[new Date(".$valueSet."), 0],";
		}
	}
?>
      ]);

      var options = {
        hAxis: {
          title: "<?php echo $haxistitle; ?>",
          textPosition: 'none'
        },
        vAxis: {
          title: "<?php echo $vaxistitle; ?>",
          format: '0',
          viewWindow: {
 	     	min: 0
    	  }
        },
        legend: {
        	position: 'none'
        },
        chartArea: {
			left: '15%',
			top: '3%',
			width: '90%',
			height: '90%'
		},
		curveType: 'function',
		width: '100%',
		height: '100%',
		backgroundColor: "<?php echo $backgroundcolor; ?>",
		colors: ["<?php echo $chartcolor; ?>"]
      };

      var chart = new google.visualization.<?php echo $chartstyle; ?>(
        document.getElementById('ex0'));

      chart.draw(data, options);

    }
    </script>

	<div id="ex0" style="width: 100%;"></div>

<?php
	}
}

?>