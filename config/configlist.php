<?php
		include("dbinfo.php");

		$connection=pg_connect ("dbname=$database user=$user password=$password host=$host port=$port");
		if (!$connection) {
		  die("Not connected : " . pg_error());
		}

		$query = "SELECT * FROM metadata ORDER BY layer_order DESC";

		$result = pg_exec($connection, $query);

		if (!$result) {printf ("ERROR"); exit;}

		$myFile = "configlist.js";
		$fh = fopen($myFile, 'w') or die("can't open file");

		$config = array();
		$config[] = "";

		$features = array();
		while ($row = @pg_fetch_assoc($result)){
		$features[] = "$row["geoserver_layer"]";
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