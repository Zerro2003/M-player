<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: playlist.php');
    exit;
}

require 'connect.php';

$flashMessage = $_SESSION['flash_message'] ?? '';
unset($_SESSION['flash_message']);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Email and password are required.';
    } else {
        $stmt = mysqli_prepare($conn, 'SELECT id, email, p_word FROM users WHERE email = ? LIMIT 1');
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 's', $email);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $userId, $userEmail, $storedPassword);

            if (mysqli_stmt_fetch($stmt)) {
                if ($storedPassword === $password) {
                    $_SESSION['user_id'] = (int) $userId;
                    $_SESSION['user_email'] = $userEmail;
                    mysqli_stmt_close($stmt);
                    mysqli_close($conn);
                    header('Location: playlist.php');
                    exit;
                }
            }

            mysqli_stmt_close($stmt);
            $error = 'Invalid email or password.';
        } else {
            $error = 'Unable to process your login right now.';
        }
    }
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Welcome Back</title>
    <link rel="stylesheet" href="output.css" />
    <link rel="stylesheet" href="login.css" />
  </head>
  <body class="login-body">
    <div class="login-card">
      <div class="login-card__header">
        <h1>Music Player Login</h1>
        <p>Sign in to manage your playlist.</p>
      </div>

      <?php if ($flashMessage !== ''): ?>
        <div class="alert alert--success">
          <?php echo htmlspecialchars($flashMessage); ?>
        </div>
      <?php endif; ?>

      <?php if ($error !== ''): ?>
        <div class="alert alert--error">
          <?php echo htmlspecialchars($error); ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="index.php" class="login-form">
        <label class="form-field">
          <span>Email</span>
          <input
            type="email"
            name="email"
            placeholder="you@example.com"
            required
            autocomplete="email"
          />
        </label>
        <label class="form-field">
          <span>Password</span>
          <input
            type="password"
            name="password"
            placeholder="Enter your password"
            required
            autocomplete="current-password"
          />
        </label>
        <button type="submit" class="login-button">
          Log In
        </button>
      </form>
    </div>
  </body>
</html>
