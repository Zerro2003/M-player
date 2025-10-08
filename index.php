
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>The Music</title>
    <link rel="stylesheet" href="output.css" />
  </head>
  <body>
    <div class="px-4">
      <form
      class="flex flex-col gap-4 md:flex-row md:gap-6 md:items-center justify-center mt-10 mx-auto max-w-5xl border-2 border-lime-500 rounded-3xl p-6 bg-white/5 backdrop-blur-sm"
      action=""
      method="POST"
    >
      <input
  class="border-2 border-amber-200 text-center p-3 rounded-2xl w-full md:flex-1 min-w-0"
        type="text"
        name="title"
        placeholder="Song Title Here"
        required
      />
      <input
  class="border-2 border-amber-200 text-center p-3 rounded-2xl w-full md:flex-1 min-w-0"
        type="text"
        name="artist"
        placeholder="Artist"
        required
      />
      <input
  class="border-2 border-amber-200 text-center p-3 rounded-2xl w-full md:w-36"
        type="text"
        name="duration"
        placeholder="Duration (MM:SS)"
        required
      />
      <button
  class="bg-lime-300 p-3 border-black border-b-2 rounded-2xl font-semibold w-full md:w-auto"
        type="submit"
      >
        Add Song
      </button>
    </form>
    </div>

  <div class="bg-gradient-to-tl from-[#15803d] via-[#115e59] to-[#164e63] flex flex-col mt-10 mx-4 sm:mx-8 lg:mx-auto max-w-5xl rounded-3xl overflow-hidden border-2 border-emerald-800 shadow-xl">

      <?php
include 'connect.php';
$sql = "SELECT title, artist, duration FROM songs";
$result = mysqli_query($conn, $sql);
if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo "
    <div class='flex flex-col sm:flex-row gap-3 sm:gap-6 mt-4 text-white justify-between bg-gradient-to-r from-[#2dd4bf] to-[#1f2937] px-6 py-4 rounded-3xl mx-4 shadow-lg sm:items-center'>
    <h1 class='text-lg sm:text-xl font-bold truncate sm:flex-1'>🎵  ".$row['title']."</h1> <h1 class='text-base sm:text-lg font-semibold sm:w-40 truncate'>".$row['artist']."</h1> <h1 class='text-base sm:text-lg font-semibold sm:w-24 text-right'>".$row['duration']."</h1>
      </div>";
    }
} else {
  echo "<h1 class='mt-10 text-xl sm:text-2xl font-bold text-center text-white px-6'>You have no saved music Yet!</h1> ";
}

mysqli_close($conn);
?>
    
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
