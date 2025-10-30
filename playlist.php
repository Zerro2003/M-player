<?php
session_start();

require 'connect.php'; // Your database connection file

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php'); // Redirect to login if not logged in
    exit;
}

// Initialize variables
$flashMessage = $_SESSION['flash_message'] ?? '';
unset($_SESSION['flash_message']); // Clear the message after displaying it
$errors = [];
$editSong = null;

// Handle POST requests (add, update, delete actions)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ACTION: Add a new song
    if ($action === 'add') {
        $title = trim($_POST['title'] ?? '');
        $artist = trim($_POST['artist'] ?? '');
        $duration = trim($_POST['duration'] ?? 'N/A'); // Default to 'N/A' if not provided
        $file = $_FILES['audio_file'] ?? null;
        $filePath = '';

        if ($title === '' || $artist === '') {
            $errors[] = 'Title and Artist fields are required.';
        } elseif (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'A valid audio file is required.';
        } else {
            $uploadDir = 'uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $fileName = uniqid() . '-' . basename($file['name']);
            $targetPath = $uploadDir . $fileName;

            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                $filePath = $targetPath;
                $stmt = mysqli_prepare($conn, 'INSERT INTO songs (title, artist, duration, file_path) VALUES (?, ?, ?, ?)');
                mysqli_stmt_bind_param($stmt, 'ssss', $title, $artist, $duration, $filePath);
                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['flash_message'] = 'Song added to the playlist.';
                } else {
                    $errors[] = 'Could not add the song right now.';
                    unlink($targetPath); // Delete uploaded file if DB insert fails
                }
                mysqli_stmt_close($stmt);
            } else {
                $errors[] = 'Failed to upload the audio file.';
            }
            
            if (empty($errors)) {
                header('Location: playlist.php');
                exit;
            }
        }
    }

    // ACTION: Update an existing song
    if ($action === 'update') {
        $songId = (int) ($_POST['song_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $artist = trim($_POST['artist'] ?? '');
        $duration = trim($_POST['duration'] ?? '');

        if ($songId <= 0) {
            $errors[] = 'Invalid song selected for update.';
        } elseif ($title === '' || $artist === '' || $duration === '') {
            $errors[] = 'All fields are required to update a song.';
        } else {
            $stmt = mysqli_prepare($conn, 'UPDATE songs SET title = ?, artist = ?, duration = ? WHERE id = ?');
            mysqli_stmt_bind_param($stmt, 'sssi', $title, $artist, $duration, $songId);
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['flash_message'] = 'Song details updated successfully.';
            } else {
                $errors[] = 'Could not update the song right now.';
            }
            mysqli_stmt_close($stmt);
            header('Location: playlist.php');
            exit;
        }
    }

    // ACTION: Delete a single song
    if ($action === 'delete') {
        $songId = (int) ($_POST['song_id'] ?? 0);
        if ($songId > 0) {
            // First, get the file path to delete the file
            $stmt = mysqli_prepare($conn, 'SELECT file_path FROM songs WHERE id = ?');
            mysqli_stmt_bind_param($stmt, 'i', $songId);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $song = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);

            if ($song && !empty($song['file_path']) && file_exists($song['file_path'])) {
                unlink($song['file_path']);
            }

            // Then, delete the record from the database
            $stmt = mysqli_prepare($conn, 'DELETE FROM songs WHERE id = ?');
            mysqli_stmt_bind_param($stmt, 'i', $songId);
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['flash_message'] = 'Song removed from the playlist.';
            } else {
                $errors[] = 'Could not delete the song right now.';
            }
            mysqli_stmt_close($stmt);
            header('Location: playlist.php');
            exit;
        }
    }
    
    // ACTION: Delete all songs
    if ($action === 'delete_all') {
        // First, get all file paths to delete the files
        $result = mysqli_query($conn, 'SELECT file_path FROM songs');
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                if (!empty($row['file_path']) && file_exists($row['file_path'])) {
                    unlink($row['file_path']);
                }
            }
            mysqli_free_result($result);
        }

        // Then, truncate the table
        if (mysqli_query($conn, 'TRUNCATE TABLE songs')) {
            $_SESSION['flash_message'] = 'All songs have been removed from the playlist.';
        } else {
            $errors[] = 'Could not delete all songs right now.';
        }
        header('Location: playlist.php');
        exit;
    }
}

// Handle GET request for editing a song
if (isset($_GET['edit_id'])) {
    $editId = (int) $_GET['edit_id'];
    if ($editId > 0) {
        $stmt = mysqli_prepare($conn, 'SELECT id, title, artist, duration, file_path FROM songs WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $editId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $editSong = mysqli_fetch_assoc($result); // This will be null if song not found
        mysqli_stmt_close($stmt);

        if (!$editSong) {
            $_SESSION['flash_message'] = 'The selected song could not be found.';
            header('Location: playlist.php');
            exit;
        }
    }
}

// Fetch all songs to display in the list
$songs = [];
$searchTerm = trim($_GET['search'] ?? '');

$query = 'SELECT id, title, artist, duration, file_path FROM songs';
if ($searchTerm !== '') {
    $query .= ' WHERE title LIKE ? OR artist LIKE ?';
}
$query .= ' ORDER BY id DESC';

$stmt = mysqli_prepare($conn, $query);

if ($searchTerm !== '') {
    $searchParam = "%{$searchTerm}%";
    mysqli_stmt_bind_param($stmt, 'ss', $searchParam, $searchParam);
}

mysqli_stmt_execute($stmt);
$songsResult = mysqli_stmt_get_result($stmt);

if ($songsResult) {
    while ($row = mysqli_fetch_assoc($songsResult)) {
        $songs[] = $row;
    }
    mysqli_free_result($songsResult);
}
mysqli_stmt_close($stmt);

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>My Playlist</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .modal { transition: opacity 0.25s ease; }
    </style>
</head>
<body class="bg-slate-900 text-slate-200 font-sans">

    <div class="container mx-auto p-4 sm:p-6 lg:p-8">
        
        <header class="flex justify-between items-center mb-8">
            <h1 class="text-3xl sm:text-4xl font-bold text-white tracking-tight">My Playlist</h1>
            <div class="flex items-center gap-4">
                <form method="GET" action="playlist.php" class="relative">
                    <input type="search" name="search" placeholder="Search by title or artist..." class="w-full bg-slate-700 border border-slate-600 rounded-lg p-3 text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-lime-500" value="<?php echo htmlspecialchars($searchTerm); ?>">
                    <?php if ($searchTerm): ?>
                        <a href="playlist.php" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white">&times;</a>
                    <?php endif; ?>
                </form>
                <form method="POST" action="logout.php">
                    <button type="submit" class="px-4 py-2 text-sm font-semibold bg-amber-600 hover:bg-amber-700 rounded-lg transition-colors">Logout</button>
                </form>
            </div>
        </header>

        <?php if ($flashMessage): ?>
            <div class="mb-6 rounded-lg bg-emerald-500/20 border border-emerald-500 p-4 text-emerald-200">
                <?php echo htmlspecialchars($flashMessage); ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($errors)): ?>
            <div class="mb-6 rounded-lg bg-rose-500/20 border border-rose-500 p-4 text-rose-200">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="bg-slate-800/50 rounded-2xl p-6 mb-8 border border-slate-700">
            <h2 class="text-xl font-semibold mb-4 text-white">Add a New Song</h2>
            <form id="add-song-form" action="playlist.php" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <input type="hidden" name="action" value="add" />
                <input type="hidden" name="duration" id="duration" />
                <div class="md:col-span-2">
                    <label for="title" class="block text-sm font-medium text-slate-400 mb-1">Title</label>
                    <input class="w-full bg-slate-700 border border-slate-600 rounded-lg p-3 text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-lime-500" type="text" id="title" name="title" placeholder="Song Title" required />
                </div>
                <div>
                    <label for="artist" class="block text-sm font-medium text-slate-400 mb-1">Artist</label>
                    <input class="w-full bg-slate-700 border border-slate-600 rounded-lg p-3 text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-lime-500" type="text" id="artist" name="artist" placeholder="Artist Name" required />
                </div>
                <div>
                    <label for="audio-file" class="block text-sm font-medium text-slate-400 mb-1">Audio File</label>
                    <input class="w-full text-sm text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-lime-500 file:text-lime-950 hover:file:bg-lime-600" type="file" id="audio-file" name="audio_file" accept="audio/*" required />
                </div>
                <button id="add-song-button" class="md:col-start-4 w-full bg-lime-500 hover:bg-lime-600 text-slate-900 font-bold p-3 rounded-lg transition-colors" type="submit">Add Song</button>
            </form>
            <div id="duration-display" class="text-slate-400 mt-2"></div>
        </div>

        <div class="space-y-3">
            <?php if (empty($songs)): ?>
                <div class="text-center py-16 px-6 bg-slate-800/50 rounded-2xl border border-slate-700">
                    <h2 class="text-2xl font-bold text-white">Your Playlist is Empty</h2>
                    <p class="text-slate-400 mt-2">Add your first song using the form above.</p>
                </div>
            <?php else: ?>
                <?php foreach ($songs as $song): ?>
                    <div class="group bg-slate-800 rounded-xl p-4 flex items-center gap-4 transition-all duration-300 hover:bg-slate-700/80 hover:shadow-lg cursor-pointer" onclick="playSong('<?php echo htmlspecialchars(json_encode($song), ENT_QUOTES, 'UTF-8'); ?>')">
                        <div class="flex-shrink-0">
                            <button class="w-12 h-12 flex items-center justify-center bg-lime-500/20 rounded-full text-lime-400 group-hover:bg-lime-500 group-hover:text-slate-900 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3l14 9-14 9V3z" /></svg>
                            </button>
                        </div>

                        <div class="flex-1 min-w-0">
                            <h3 class="text-lg font-bold text-white truncate"><?php echo htmlspecialchars($song['title']); ?></h3>
                            <p class="text-sm text-slate-400 truncate"><?php echo htmlspecialchars($song['artist']); ?></p>
                        </div>
                        <p class="hidden sm:block text-base font-mono text-slate-400"><?php echo htmlspecialchars($song['duration']); ?></p>
                        
                        <div class="flex items-center gap-2">
                            <a href="playlist.php?edit_id=<?php echo (int) $song['id']; ?>" class="px-4 py-2 text-sm font-semibold bg-sky-600 hover:bg-sky-700 rounded-lg transition-colors z-10 relative">Edit</a>
                            <form method="POST" action="playlist.php" onsubmit="return confirm('Delete this song?');" class="z-10 relative">
                                <input type="hidden" name="action" value="delete" />
                                <input type="hidden" name="song_id" value="<?php echo (int) $song['id']; ?>" />
                                <button type="submit" class="px-4 py-2 text-sm font-semibold bg-rose-600 hover:bg-rose-700 rounded-lg transition-colors">Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if (!empty($songs)): ?>
            <div class="mt-12 pt-8 border-t border-rose-500/30 text-center">
                 <form method="POST" action="playlist.php" onsubmit="return confirm('Delete ALL songs? This cannot be undone.');">
                    <input type="hidden" name="action" value="delete_all" />
                    <button type="submit" class="bg-rose-800/80 hover:bg-rose-700 text-rose-200 font-bold py-3 px-6 rounded-lg transition-colors">Delete All Songs</button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <div id="player-container" class="fixed bottom-0 left-0 right-0 bg-slate-800/80 backdrop-blur-lg border-t border-slate-700 p-4 text-white shadow-lg" style="display: none;">
        <div class="container mx-auto flex items-center gap-4">
            <div class="flex-1 min-w-0">
                <p class="font-bold truncate" id="player-title">Song Title</p>
                <p class="text-sm text-slate-400 truncate" id="player-artist">Artist Name</p>
            </div>
            <audio id="audio-player" controls class="w-full max-w-md"></audio>
        </div>
    </div>

    <?php if ($editSong): ?>
    <div id="editModal" class="modal fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-slate-800 w-full max-w-lg rounded-2xl border border-slate-700 shadow-2xl">
            <div class="p-6 border-b border-slate-700 flex justify-between items-center">
                <h2 class="text-2xl font-bold text-white">Edit Song</h2>
                <a href="playlist.php" class="text-slate-400 hover:text-white">&times;</a>
            </div>
            <form method="POST" action="playlist.php" class="p-6">
                <input type="hidden" name="action" value="update" />
                <input type="hidden" name="song_id" value="<?php echo (int) $editSong['id']; ?>" />
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-400 mb-1">Title</label>
                        <input class="w-full bg-slate-700 border border-slate-600 rounded-lg p-3 text-white placeholder-slate-400" type="text" name="title" value="<?php echo htmlspecialchars($editSong['title']); ?>" required />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-400 mb-1">Artist</label>
                        <input class="w-full bg-slate-700 border border-slate-600 rounded-lg p-3 text-white placeholder-slate-400" type="text" name="artist" value="<?php echo htmlspecialchars($editSong['artist']); ?>" required />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-400 mb-1">Duration</label>
                        <input class="w-full bg-slate-700 border border-slate-600 rounded-lg p-3 text-white placeholder-slate-400" type="text" name="duration" value="<?php echo htmlspecialchars($editSong['duration']); ?>" required />
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <a href="playlist.php" class="px-5 py-2 text-sm font-semibold bg-slate-600 hover:bg-slate-500 rounded-lg transition-colors">Cancel</a>
                    <button type="submit" class="px-6 py-2 text-sm font-semibold bg-sky-600 hover:bg-sky-700 text-white rounded-lg transition-colors">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://unpkg.com/music-metadata-browser/dist/music-metadata-browser.min.js"></script>
    <script>
        function toggleMenu(songId) {
            const menu = document.getElementById('menu-' + songId);
            if (menu) {
                menu.classList.toggle('hidden');
            }
        }

        window.onclick = function(event) {
            if (!event.target.matches('.relative button, .relative svg, .relative path')) {
                document.querySelectorAll('[id^="menu-"]').forEach(dropdown => {
                    if (dropdown && !dropdown.classList.contains('hidden')) {
                        dropdown.classList.add('hidden');
                    }
                });
            }
        }

        const audioFileInput = document.getElementById('audio-file');
        const titleInput = document.getElementById('title');
        const artistInput = document.getElementById('artist');
        const durationInput = document.getElementById('duration');
        const durationDisplay = document.getElementById('duration-display');
        const addSongForm = document.getElementById('add-song-form');
        const addSongButton = document.getElementById('add-song-button');

        if (audioFileInput) {
            audioFileInput.addEventListener('change', async (event) => {
                const file = event.target.files[0];
                if (file) {
                    addSongButton.disabled = true;
                    addSongButton.textContent = 'Reading File...';
                    durationDisplay.textContent = 'Reading metadata...';

                    // --- Get duration using native Audio element ---
                    const audio = new Audio();
                    audio.src = URL.createObjectURL(file);
                    audio.onloadedmetadata = () => {
                        URL.revokeObjectURL(audio.src); // Clean up object URL
                        if (isFinite(audio.duration)) {
                            const duration = audio.duration;
                            const minutes = Math.floor(duration / 60);
                            const seconds = Math.floor(duration % 60).toString().padStart(2, '0');
                            const durationString = `${minutes}:${seconds}`;
                            durationInput.value = durationString;
                            durationDisplay.textContent = `Duration: ${durationString}`;
                        } else {
                            durationInput.value = 'N/A';
                            durationDisplay.textContent = 'Duration not found.';
                        }
                    };
                    audio.onerror = () => {
                        URL.revokeObjectURL(audio.src);
                        durationInput.value = 'N/A';
                        durationDisplay.textContent = 'Could not read audio file.';
                    };

                    // --- Get title/artist using music-metadata-browser ---
                    try {
                        const metadata = await musicMetadataBrowser.parseBlob(file);
                        const common = metadata.common;
                        if (common.title) titleInput.value = common.title;
                        if (common.artist) artistInput.value = common.artist;
                    } catch (error) {
                        console.error('Could not read title/artist metadata:', error);
                    } finally {
                        addSongButton.disabled = false;
                        addSongButton.textContent = 'Add Song';
                    }
                }
            });
        }

        const playerContainer = document.getElementById('player-container');
        const audioPlayer = document.getElementById('audio-player');
        const playerTitle = document.getElementById('player-title');
        const playerArtist = document.getElementById('player-artist');

        function playSong(songData) {
            const song = JSON.parse(songData);
            playerContainer.style.display = 'block';
            playerTitle.textContent = song.title;
            playerArtist.textContent = song.artist;
            audioPlayer.src = song.file_path;
            audioPlayer.play();
        }
    </script>
</body>
</html>