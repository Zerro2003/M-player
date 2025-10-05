# Playlist (XAMPP)

This small project includes a form (`index.html`) that submits a song to a MySQL database using PHP.

Setup steps (XAMPP / local):

1. Start Apache and MySQL from the XAMPP control panel.
2. Create the database and table:
   - Open phpMyAdmin (http://localhost/phpmyadmin) and run the SQL in `create_table.sql`, or run it from the MySQL console.
3. Ensure `config.php` credentials match your MySQL setup (default XAMPP: user `root` with empty password).
4. Place the project folder inside XAMPP's `htdocs` (you already have it there).
5. Open the form: http://localhost/playlist/index.html
6. Fill out the form and submit — the `Post.php` handler will insert the row and redirect back to the form.

Notes & security:
- This example uses PDO prepared statements to avoid SQL injection.
- For production, do not use the `root` MySQL user; create a dedicated DB user with limited privileges.
- Consider adding CSRF protection and better form validation for production.

