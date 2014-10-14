<?php  
		$user="postgres";
		$password="gisdb";
		$database="ADKGIS";
		$host="localhost";

		$connection=pg_connect ("dbname=$database user=$user password=$password host=$host port=$port");
		if (!$connection) {
		  die("Not connected : " . pg_error());
		}

		$query = "SELECT * FROM metadata ORDER BY layer_order DESC";

		$result = pg_exec($connection, $query);
		
		if (!$result) {printf ("ERROR"); exit;}
		
		$myFile = "config.js";
		$fh = fopen($myFile, 'w') or die("can't open file");

		$config = array();
		$config[] = "var app = new GeoExplorer({
				wms: '/geoserver/wms',
				map: {
						projection: 'EPSG:900913',
						maxResolution: 156543.0339,
						maxExtent: [
                        -20037508.34, -20037508.34,
                        20037508.34, 20037508.34
						],
						center: [0, 0],
						zoom: 1,
						layers: [";
		
		$features = array();
		while ($row = @pg_fetch_assoc($result)){
		$features[] = "{
				name: '" . $row['geoserver_workspace'] . ':' . $row['geoserver_layer'] . "',
				title: '" . $row["title_of_dataset"] . "',
				format: 'image/png',
				group: '" . $row['TOCgroup'] . "',
				visibility: " . $row['startup_visibility'] . ",
				metadata: '" . $row["online_linkage"] . "',
				description: " . json_encode($row["abstract"]) . ",
				styles: '" . $row["styles"] . "',
				slds: [" . $row['themes'] . "],
				cql_filter: " . $row['cql_filter'] . ",
				queries: [" . $row['queries'] . "]
				}";
		}
		$config[] = implode(",", $features);
		
		$config[] = "]
						},
				wfs: '/geoserver/wfs'
				});";
		$configOutput = implode('', $config);
		$search = array("visibility: f", "visibility: t");
		$replace   = array("visibility: false", "visibility: true");
		$entry = str_replace($search,$replace,$configOutput);
		fwrite($fh, $entry);
		fclose($fh);
		echo "ARGIS Map Configuration Sucessfully Updated!"; 
?>