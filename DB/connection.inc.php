<?php
$db = mysqli_connect('I', 'wont', 'share', 'this ;)');
if (mysqli_connect_errno($db))
{
    echo "<html><head>Datenbankfehler</head><body>";
    echo "Verbindung zur DB kann nicht hergestellt werden!";
    echo "</body></html>";
    exit;
}
?>
