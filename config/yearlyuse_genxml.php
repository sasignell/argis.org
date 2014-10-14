<?php
require("dbinfo.php");

// Get parameters from URL
$regid = $_GET["regid"];
$year = $_GET["year"];

// Start XML file, create parent node
$dom = new DOMDocument("1.0");
$node = $dom->createElement("registers");
$parnode = $dom->appendChild($node);

// Opens a connection to a mySQL server
$connection=pg_connect ("dbname=$database user=$user password=$password host=$host port=$port");
if (!$connection) {
  die("Not connected : " . pg_error());
}

// Build SQL SELECT Statement.
 //$query = "SELECT * from dec.registerdata a INNER JOIN argis.month_lookup b ON a.mo=b.moid where asset_uid = '$regid' and yr = '$yr'";
 $query = "SELECT yr, SUM(people) FROM dec.trailregisterdata WHERE asset_uid = '$regid' GROUP BY yr ORDER BY yr ASC";
 $result = pg_exec($connection, $query);
 if (!$result) {printf ("ERROR"); exit;}

header("Content-type: text/xml");

// Iterate through the rows, adding XML nodes for each
while ($row = @pg_fetch_assoc($result))
{

  $node = $dom->createElement("register");
  $newnode = $parnode->appendChild($node);
  $newnode->setAttribute("year", $row['yr']);
  $newnode->setAttribute("totals", $row['sum']);
}

echo $dom->saveXML();
?>
