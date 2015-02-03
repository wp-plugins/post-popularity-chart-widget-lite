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
		foreach ($result as $key => $row) {
			static $i = 0;
			$value = $date[$i]['CAST(date AS DATE)'];
			++$i;
			$a = preg_replace("|(\d{4})\-(\d{2})-(\d{2})|", "$1", $value);
			$b = preg_replace("|(\d{4})\-(\d{2})-(\d{2})|", "$2", $value);
			$b = (int)$b - 1;
			$c = preg_replace("|(\d{4})\-(\d{2})-(\d{2})|", "$3", $value);
			$value = $row['COUNT(post_id)'];
			echo "[new Date(".(int)$a.", ".$b.", ".(int)$c."), ".(int)$value."],";
		}
?>
      ]);

      var options = {
        hAxis: {
          title: 'Time',
          textPosition: 'none'
        },
        vAxis: {
          title: 'Visits'
        },
        legend: {
        	position: 'none'
        },
        chartArea: {
			left: '5%',
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