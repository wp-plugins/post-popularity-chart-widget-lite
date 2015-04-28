<?php

//zbieranie danych
function add_hits($postID) {
	global $wpdb;
	$post_popularity_graph_table = $wpdb->prefix . 'post_popularity_graph';
	if (!preg_match('/bot|spider|crawler|slurp|curl|^$/i', $_SERVER['HTTP_USER_AGENT'])) { //jeśli nie istnieje rekord hit_count z podanym ID oraz ID nie jest równe 1 oraz odwiedzający nie jest botem
		$wpdb->query($wpdb->prepare("INSERT INTO $post_popularity_graph_table (post_id, date) VALUES (%d, NOW())", $postID)); //dodaje do tablicy id postu, date oraz hit
		$wpdb->query("DELETE FROM $post_popularity_graph_table WHERE date <= NOW() - INTERVAL 30 DAY"); //removes database entry older than 30 days
	}
} 

function show_graph($postID, $numberofdays) {
	global $wpdb;
	$post_popularity_graph_table = $wpdb->prefix . 'post_popularity_graph';
	if ($wpdb->query("SELECT post_id FROM $post_popularity_graph_table WHERE post_id = $postID")) {
		$result = $wpdb->get_results($wpdb->prepare("SELECT COUNT(post_id) FROM $post_popularity_graph_table WHERE post_id = %d AND date >= DATE(DATE_SUB(NOW(), INTERVAL %d DAY)) GROUP BY CAST(date AS DATE)", $postID, $numberofdays), ARRAY_A);
		$date = $wpdb->get_results($wpdb->prepare("SELECT CAST(date AS DATE) FROM $post_popularity_graph_table WHERE post_id = %d AND date >= DATE(DATE_SUB(NOW(), INTERVAL %d DAY)) GROUP BY CAST(date AS DATE)", $postID, $numberofdays), ARRAY_A);
		
//przedział dat - listowanie na podstawie $numberofdays
	$date1 = date("Y, m, d", strtotime("- $numberofdays day"));
	$date2 = date("Y, m, d");
	
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

//zbieranie dat w tablicach
	foreach($datePeriod as $dateLoop) {
		$dateLoop = $dateLoop->format('Y, m, d');
		$tablica[] = $dateLoop; //zapisuje wyniki w tablicy
	}
	foreach($date as $dateMainLoop) {
		static $i = 0;
		$dateMainLoop = $date[$i]['CAST(date AS DATE)'];
		++$i;
		$dateMainLoop = DateTime::createFromFormat('Y-m-d', $dateMainLoop);
		$dateMainLoop = $dateMainLoop->format('Y, m, d');
		$tablica2[] = $dateMainLoop; //zapisuje wyniki w tablicy
	}

?>
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
          title: 'Time',
          textPosition: 'none'
        },
        vAxis: {
          title: 'Visits',
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
		height: '100%'
      };

      var chart = new google.visualization.LineChart(
        document.getElementById('ex0'));

      chart.draw(data, options);

    }
    </script>

	<div id="ex0" style="width: 100%;"></div>

<?php
	}
}
?>