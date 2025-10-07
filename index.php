
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>The Music</title>
    <link rel="stylesheet" href="output.css" />
  </head>
  <body>
    <div>
      <form
      class="flex flex-row gap-8 h-20 items-center justify-center mt-30 mx-20 border-2 rounded-3xl"
      action=""
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
    </div>
    
    <div class="flex flex-col bg-red-600 h-50 justify-between mt-10 mx-40 rounded-3xl overflow-auto">
      <div class="flex flex-row gap-52 text-white justify-between bg-amber-400 px-8 rounded-3xl">
        <h1 class="text-2xl font-bold">Song Title</h1> <h1 class="text-2xl font-bold">Artist</h1> <h1 class="text-2xl font-bold">Duration</h1>
      </div>
      
    
    </div>
    <?php
include 'connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = isset($_POST['title']) ? $_POST['title'] : '';
    $artist = isset($_POST['artist']) ? $_POST['artist'] : '';
    $duration = isset($_POST['duration']) ? $_POST['duration'] : '';

    $sql = "INSERT INTO songs (title, artist, duration) VALUES ('$title', '$artist', '$duration')";

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('inserted successfully')</script>";
    } else {
        echo "<script>alert('Error: " . mysqli_error($conn) . "')</script>";
    }
}

mysqli_close($conn);
?>
  </body>
  
</html>
