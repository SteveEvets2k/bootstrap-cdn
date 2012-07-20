<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>Reports</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="Karlo Espiritu">


    <!-- Le styles -->
    <link href="http://current.bootstrapcdn.com/bootstrap-v204/css/bootstrap-combined.min.css" rel="stylesheet">
    <style>
      body {
        padding-top: 60px; /* 60px to make the container go all the way to the bottom of the topbar */
      }
    </style>

    <!-- Le HTML5 shim, for IE6-8 support of HTML5 elements -->
    <!--[if lt IE 9]>
      <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
    <![endif]-->

    <!-- Le fav and touch icons -->
    <link rel="shortcut icon" href="bootstrap/ico/favicon.ico">
    <link rel="apple-touch-icon-precomposed" sizes="144x144" href="bootstrap/ico/apple-touch-icon-144-precomposed.png">
    <link rel="apple-touch-icon-precomposed" sizes="114x114" href="bootstrap/ico/apple-touch-icon-114-precomposed.png">
    <link rel="apple-touch-icon-precomposed" sizes="72x72" href="bootstrap/ico/apple-touch-icon-72-precomposed.png">
    <link rel="apple-touch-icon-precomposed" href="bootstrap/ico/apple-touch-icon-57-precomposed.png">
  <script>
  $(document).ready(function() {
    $("#progressbar").progressbar({ value: 37 });
  });
  </script>
  </head>

  <body>

    <div class="navbar navbar-fixed-top">
      <div class="navbar-inner">
        <div class="container">
          <a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </a>
          <a class="brand" href="#">Reports</a>
          <div class="nav-collapse">
            <ul class="nav">
              <li><a href="index.php">Popular Files</a></li>
              <li class="active"><a href="#">Nodes Breakdown</a></li>
              <li><a href="errors.php?date=<?=gmdate("Y-m-d");?>">Hourly Errors</a></li>
            </ul>
          </div><!--/.nav-collapse -->
        </div>
      </div>
    </div>
    
    <div class="container">

  
    <h2>Location Overview</h2>
    <p>Total requests and bandwidth by map location </p>
	<div class="row">
		<div class="span6">
			<h3>United States</h3>
			<div id="chart_div1" style="width: 480px; height: 300px;"></div>
		</div>
	    <div class="span6">
			<h3>Europe</h3>
			<div id="chart_div2" style="width: 480px; height: 300px;"></div>
		</div>	
	</div>
    <p>
    <h2>Nodes Breakdown Overview</h2>
    <p>Total requests and bandwidth by location </p>
	<table class="table table-striped">
        <thead>
          <tr>
            <th>Location</th>
            <th>Hits (Requests)</th>
            <th> </th>
            <th>Bandwidth</th>
			<th> </th>
          </tr>
        </thead>
        <tbody>

<?php
//error_reporting(E_ALL);
require_once('config.php');
require_once("OAuth.php");

/*
 * NetDNA API OAuth Code - PHP
 * Version 1.0a
 * Succeeding code is based on on:
 * https://raw.github.com/gist/2791330/64b7007ab9d4d4cbb77efd107bb45e16fc6c8cdf/OAuth.php
 */

// create an OAuth consumer with your key and secret
$consumer = new OAuthConsumer($key, $secret, NULL);

// method type: GET, POST, etc
$method_type   = "GET";

//url to send request to (everything after alias/ in endpoint)
$selected_call = "reports/nodes.json/stats";

// the endpoint for your request
$endpoint = "https://rws.netdna.com/$alias/$selected_call"; //this endpoint will pull the account information for the provided alias

//parse endpoint before creating OAuth request
$parsed = parse_url($endpoint);
if(array_key_exists("parsed", $parsed))
{
    parse_str($parsed['query'], $params);
}


//generate a request from your consumer
$req_req = OAuthRequest::from_consumer_and_token($consumer, NULL, $method_type, $endpoint, $params);

//sign your OAuth request using hmac_sha1
$sig_method = new OAuthSignatureMethod_HMAC_SHA1();
$req_req->sign_request($sig_method, $consumer, NULL);

// create curl resource 
$ch = curl_init(); 
// set url 
curl_setopt($ch, CURLOPT_URL, $req_req); 
//return the transfer as a string
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER , FALSE);

// set curl custom request type if not standard
if ($method_type != "GET" && $method_type != "POST") {
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method_type);
}

// not sure what this is doing
if ($method_type == "POST" || $method_type == "PUT" || $method_type == "DELETE") {
    $query_str = OAuthUtil::build_http_query($params);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:', 'Content-Length: ' . strlen($query_str)));
    curl_setopt($ch, CURLOPT_POSTFIELDS,  $query_str);
}

//tell curl to grab headers
//curl_setopt($ch, CURLOPT_HEADER, true);

// $output contains the output string 
$json_output = curl_exec($ch);

// $headers contains the output headers
//$headers = curl_getinfo($ch);

// close curl resource to free up system resources 
curl_close($ch);

//convert json response into multi-demensional array
$json_o = json_decode($json_output);

// dump the result
//  var_dump($json_output);

//define array for geochart
$eu_cities = array('Amsterdam','London');

//define array for US and Europe cities
$us_data = array();
$eu_data = array();


if(array_key_exists("code",$json_o))
{
    //if successful response, grab data into elements array
    if($json_o->code == 200 || $json_o->code == 201)
    {
        $zones = $json_o->data;
		//echo $output->data->total;
		
		$array_bytestransferred = array();
		$array_requests = array();

		$highest_hit = $json_o->data->stats[0]->hit;
		$highest_size = ($json_o->data->stats[0]->size); ///1073741824;
		$highest_size_rounded = round($highest_size,2);

		foreach ( $json_o->data->stats as $f)
		{
			array_push($array_bytestransferred,$f->size);
			array_push($array_requests,$f->hit);
		}	
		//print_r($array_bytestransferred);
		$max_filetransfer = max($array_bytestransferred);
		$total_filetransfer = array_sum($array_bytestransferred);
		$total_filetransfer = round(($total_filetransfer/1073741824),2);
		$total_requests = number_format(array_sum($array_requests));
		//echo "maxfile_transferred_value: $max_filetransfer<p>";

		foreach ( $json_o->data->stats as $d)
		{
			
		//	$hit_rate = ((($d->hit)/$highest_hit) * 100);
		    $hit_formatted = number_format($d->hit);
			$hit_percentage = round(((($d->hit)/$highest_hit) * 100),2);
			$bandwidth_size = round(($d->size/1073741824),2);
			$bandwidth_percentage = round(((($d->size)/$max_filetransfer) * 100),2);	
			
			//check if data is a europe city
			if (in_array($d->pop_description, $eu_cities)) {
				array_push($eu_data,array($d->pop_description,$d->hit,$bandwidth_size,));
			} else {	
			//else add to US data array											
				array_push($us_data,array($d->pop_description,$d->hit,$bandwidth_size,));
			}
			
			echo "<tr>
            <td>
				$d->pop_description
			</td>";
            echo "<td>";
			echo "<div class=\"progress\">";
	  		echo  "<div class=\"bar\" style=\"width: $hit_percentage%;\"></div>";
			echo  "</div>";			
			echo  "</td>";
            echo "<td>$hit_formatted Requests </td>";
            echo "<td>";
			echo "<div class=\"progress\">";
	  		echo  "<div class=\"bar\" style=\"width: $bandwidth_percentage%;\"></div>";
			echo  "</div>";	
			echo "</td>";
			echo "<td>$bandwidth_size GB</td>";
			
            echo "</tr>";
			echo "<tr>";
			
		   // echo "uri: $d->uri | hit: $d->hit Requests  | percentage: $hit_percentage | | size: $d->size | fsize_percentage: $bandwidth_percentage | bandwidth: $bandwidth_size GB<p>";
		}
			echo "<td><strong>Total</strong></td><td></td><td><strong>$total_requests Requests</strong></td><td></td><td><strong>$total_filetransfer GB</strong></td>";
    }
	
    // else, spit out the error received
    else
    {
        echo "Error: " . $json_o->code . ":";
        $elements = $json_o->error;
        foreach($elements as $key => $value)
        {
            echo "$key = $value";
        }
    }
}
else
{
    echo "No return code given";
}

// print_r($us_data);
// print_r($eu_data);
?>




        </tbody>
      </table>
    <hr> 
	<footer class="footer">
	   <p class="pull-left">&copy; NetDNA 2012</p>
	   <p class="pull-right"><a href="#">Back to top</a></p>
	</footer>
    </div> <!-- /container -->

    <!-- Le javascript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
    <script src="http://current.bootstrapcdn.com/bootstrap-v204/js/bootstrap.min.js"></script>
    	<script src="js/highcharts.js"></script>
	<script src="js/highcharts-defaults.js"></script>
	<script src="js/highcharts-cachehits.js"></script>
       <script type='text/javascript' src='https://www.google.com/jsapi'></script>
    <script type='text/javascript'>
	//US data	
     google.load('visualization', '1', {'packages': ['geochart']});
     google.setOnLoadCallback(drawMarkersMap);

      function drawMarkersMap() {
      var data = google.visualization.arrayToDataTable([
		  ['City',   'Requests', 'Bandwidth(GB)'],
		<?
			foreach ( $us_data as $d)	{
				echo "['$d[0]',$d[1],$d[2]"."],\n";
			}	
		?>

      ]);

      var options = {
	    showZoomOut: true,
		colorAxis: {colors:['red','#004411']},
        region: 'US',
        displayMode: 'markers',
		enableRegionInteractivity: true,
		resolution:'metros',
		legend: {textStyle: {color: 'blue', fontSize: 16, numberFormat:'.##'}},
		magnifyingGlass:{enable: true, zoomFactor: 7.5},
		markerOpacity:0.65,
		sizeAxis:{minValue: 1,  maxSize: 20},
		tooltip:  {textStyle: {color: '#AA0000'}, showColorCode: true},
        colorAxis: {colors: ['orange', 'red']}

      };

      var chart = new google.visualization.GeoChart(document.getElementById('chart_div1'));
      chart.draw(data, options);
    };
    </script>

    <script type='text/javascript'>
    //Europe
     google.load('visualization', '1', {'packages': ['geochart']});
     google.setOnLoadCallback(drawMarkersMap);

      function drawMarkersMap() {
      var data = google.visualization.arrayToDataTable([
        ['City',   'Requests', 'Bandwidth(GB)'],
		<?
			foreach ( $eu_data as $d)	{
				echo "['$d[0]',$d[1],$d[2]"."],\n";
			}	
		?>

      ]);

      var options = {
	    showZoomOut: true,
        region: 155,
        displayMode: 'markers',
		enableRegionInteractivity: true,
		resolution:'countries',
		legend: {textStyle: {color: 'blue', fontSize: 16}},
		magnifyingGlass:{enable: true, zoomFactor: 7.5},
		markerOpacity:0.65,
		sizeAxis:{minValue: 1,  maxSize: 20},
		tooltip:  {textStyle: {color: '#AA0000'}, showColorCode: true},
        colorAxis: {colors: ['orange', 'red']}
      };

      var chart = new google.visualization.GeoChart(document.getElementById('chart_div2'));
      chart.draw(data, options);
    };
    </script>
  </body>
</html>
