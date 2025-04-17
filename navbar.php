<?php
$isLoggedIn = isset($_SESSION['username']);

#υλοποίηση logout με απλή χρήση μεταβλητής
if (isset($_GET['logout']) && $_GET['logout'] === 'true') {
  $_SESSION = [];
}
?>

<!DOCTYPE HTML>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="navbar.css">
    <title>Air DS</title>
</head>
<body>
    <!--για το navbar θα χρησιμοποιησω κλαση logo, main και login-->
    <nav class="navbar">
        <div class="logo">
            <img src="logo.png" alt="Air DS Logo">
        </div>

        <!--το main menu ανακατευθύνει στο home και στο mytrips, ενώ το login menu στην φόρμα login ή εκτελεί το logout-->
        <div class="nav">
            <div class="main-menu" id="main-menu">
                <a href="home.php">Home</a>
                <a href="mytrips.php">My Trips</a>
            </div>
            <div class="login-menu" id="login-menu">
                <?php if ($isLoggedIn): ?>
                    <a href="login.php"?logout=true>Logout</a><!--ανακατευθύνει στο login form αλλά υλοποιεί και logout με χρήση της μεταβλητής-->
                <?php else: ?>
                    <a href="login.php">Login</a>
                <?php endif; ?>
            </div>
        </div>
        
        <!--3 div εσωτερικά για να εμφανίζονται 3 μπάρες-->
        <div class="hamburger" onclick="toggleMenu()">
            <div></div>
            <div></div>
            <div></div>
        </div>
    </nav>
    <?php include 'footer.php'; ?>
    
    <!--απλό σκριπτακι που υλοποιεί το hamburger menu μέσω αλλαγή css κλάσης-->
    <script>
        function toggleMenu() {
            document.querySelector('.nav').classList.toggle('active');
        }
    </script>
</body>
</html>