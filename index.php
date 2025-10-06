<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>The Music</title>
    <link rel="stylesheet" href="output.css" />
  </head>
  <body>
    
    <form
      class="flex flex-row gap-8 h-20 items-center justify-center mt-30 mx-20 border-2 rounded-3xl"
      action="./"
      method="POST"
    >
      <input
        class="border-2 border-amber-200 text-center p-2 rounded-2xl"
        type="text"
        name="title"
        placeholder="Song Title Here"
        required
      />
      <input
        class="border-2 border-amber-200 text-center p-2 rounded-2xl"
        type="text"
        name="artist"
        placeholder="Artist"
        required
      />
      <input
        class="border-2 border-amber-200 text-center p-2 rounded-2xl"
        type="text"
        name="duration"
        placeholder="Duration (MM:SS)"
        required
      />
      <button
        class="bg-lime-300 p-2 border-black border-b-2 rounded-2xl"
        type="submit"
      >
        Add Song
      </button>
    </form>
    <div
      class="flex flex-row gap-8 h-20 items-center justify-center mx-20 border-2 rounded-3xl mt-8"
    ></div>
    <?php
include 'connect.php';
$title = isset($_POST['title']) ? $_POST['title'] : '';
$artist = isset($_POST['artist']) ? $_POST['artist'] : '';
$duration = isset($_POST['duration']) ? $_POST['duration'] : '';


$sql = "INSERT INTO songs (title, artist, duration) VALUES ('$title', '$artist', '$duration')";

// if (mysqli_query($conn, $sql)) {
//     echo "✅ Song added successfully!";
// } else {
//     echo "❌ Error: " . mysqli_error($conn);
// }

mysqli_close($conn);
?>
  </body>
</html>
