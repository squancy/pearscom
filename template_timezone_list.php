<?php
    $tz_string .= "";
    $timezone_identifiers = DateTimeZone::listIdentifiers();
    for ($i=0; $i < 423; $i++) {
        $curtz = new DateTimeZone($timezone_identifiers[$i]);
        $datetz = new DateTime("now",$curtz);
        $offset = $curtz->getOffset($datetz);
        $offset = $offset/3600;
        $offset = "GMT (".$offset.":00)";
        $tz_string .= "<option value=".$timezone_identifiers[$i].">".$offset." ".$timezone_identifiers[$i]."</option>";
    }
    echo $tz_string;
?>