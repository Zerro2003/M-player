<?php
include 'connect.php';
$title = $_POST['title'];
$artist = $_POST['artist'];
$duration = $_POST['duration'];

$sql = "INSERT INTO songs (title, artist, duration) VALUES ('$title', '$artist', '$duration')";

if (mysqli_query($conn, $sql)) {
    echo "✅ Song added successfully!";
} else {
    echo "❌ Error: " . mysqli_error($conn);
}

mysqli_close($conn);
?>