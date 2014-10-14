<?php
require("dbinfo.php");

// Get parameters from URL
$regid = $_GET["regid"];

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
 $query = "SELECT a.asset_uid, b.mo, b.month, AVG(a.people)::integer FROM dec.trailregisterdata a INNER JOIN argis.month_lookup b ON a.mo=b.mo WHERE asset_uid = $regid GROUP BY b.mo, a.asset_uid, b.month ORDER BY b.mo ASC ";
 $result = pg_exec($connection, $query);
 if (!$result) {printf ("ERROR"); exit;}

header("Content-type: text/xml");

// Iterate through the rows, adding XML nodes for each
while ($row = @pg_fetch_assoc($result))
{
  $node = $dom->createElement("register");
  $newnode = $parnode->appendChild($node);
  $newnode->setAttribute("registerid", $row['registerid']);
  $newnode->setAttribute("month", $row['month']);
  $newnode->setAttribute("avgpeople", $row['avg']);
}

echo $dom->saveXML();
?>