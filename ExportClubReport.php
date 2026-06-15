<?php
require_once 'db.php';

header("Content-Type: text/csv");
header("Content-Disposition: attachment; filename=ClubReport.csv");

$output = fopen("php://output", "w");

fputcsv($output,
[
    'Club ID',
    'Club Name',
    'Advisor',
    'Status'
]);

$result = mysqli_query($conn,
    "SELECT ClubID, ClubName, ClubAdvisor, ClubStatus
     FROM club
     ORDER BY ClubName");

while($row = mysqli_fetch_assoc($result))
{
    fputcsv($output, $row);
}

fclose($output);
exit();
?>