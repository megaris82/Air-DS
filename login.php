<?php
//σύνδεση στην βάση
$host = 'localhost';
$db = 'air_ds';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Αδυναμία σύνδεσης με την βάση δεδομένων: " . $conn->connect_error);
}

$reg_error = '';//error για την εγγραφή
$login_error = '';//error για την σύνδεση

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    //εγγραφή
    if (isset($_POST['register'])) {
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $username = $_POST['username'];
        $password = $_POST['password'];
        $email = $_POST['email'];

        if (!ctype_alpha($first_name) || !ctype_alpha($last_name)) { //έλεγχος ότι περιέχονται μόνο γράμματα
            $reg_error = "Το όνομα και το επώνυμο πρέπει να περιέχουν μόνο χαρακτήρες";
        } elseif (strlen($password) < 4 || strlen($password) > 10 || !preg_match('/\d/', $password)) {//έλεγχος για μέγεθος και αριθμό (digit)
            $reg_error = "Ο κωδικός πρέπει να έχει 4-10 χαρακτήρες και να περιέχει τουλάχιστον έναν αριθμό";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strpos($email, '@') === false) {//έλεγχος για έγκυρο email (@ και domain)
            $reg_error = "Μη έγκυρο email";
        } else {//αν όλα πάνε καλά ως εδώ κοιτάμε αν υπάρχει ήδη το username ή το email
            $stmt = $conn->prepare("SELECT * FROM users WHERE username=? OR email=?");
            $stmt->bind_param("ss", $username, $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {//εμφάνιση σφάλματος αν υπάρχει
                $reg_error = "Το username ή το email υπάρχει ήδη";
            } else {//αλλιώς εγγραφή στην βάση
                $hashed_pass = password_hash($password, PASSWORD_DEFAULT);//χρήση της password_hash της php για κρυπτογράφηση των κωδικών
                $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, username, password, email) VALUES (?, ?, ?, ?, ?)");//εισαγωγή νέου χρήστη στην βάση
                $stmt->bind_param("sssss", $first_name, $last_name, $username, $hashed_pass, $email);
                $stmt->execute();
                $reg_success = true;// εμφάνιση alert για την επιτυχή εγγραφή
            }
        }
    }

    //είσοδος
    if (isset($_POST['login'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];

        $stmt = $conn->prepare("SELECT * FROM users WHERE username=?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        //χρήση password_verify για αποκρυπτογράφηση του password και ταυτόχρονο έλεγχο
        if ($user && password_verify($password, $user['password'])) {//αν υπάρχει ο χρήστης και ο κωδικός είναι σωστός τότε είσοδος
            setcookie("isLoggedIn", $user['user_id'] , time() + 3600, "/"); //cookie εισόδου για μια ώρα, κρατάει userid για το interface book_flight
            //γίνεται manipulate στο navbar.php για να εμφανίζεται και να υλοποιείται το logout
            header("Location: home.php");//ανακατεύθυνση στο home.php αν συνδεθεί επιτυχώς με σκοπό να κάνει κράτηση (αν θέλει ανακατευθύνεται μόνος του στο mytrips μέσω navbar)
            exit();
        } else {
            $login_error = "Λάθος username ή κωδικός.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Login / Register</title>
    <link rel="stylesheet" href="login.css">
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div id="popup-message" class="popup"></div><!--popup για την επιτυχία της εγγραφής-->
    <div class="form-wrapper">
        <div id="loginForm">
            <h2>Σύνδεση</h2>
            <form method="POST">
                <label>Όνομα χρήστη:</label>
                <input type="text" name="username" required>
                <label>Κωδικός:</label>
                <input type="password" name="password" required>
                <input type="submit" name="login" value="Σύνδεση">
                <div class="error"><?= $login_error ?></div>
            </form>
            <button id="showRegister" type="button">Δεν έχετε λογαριασμό; Εγγραφή</button><!--για την αλλαγή προς την εγγραφή-->
        </div>

        <div id="registerForm" style="display: none;"><!--αρχικά δεν εμφανίζεται, κάτω υπάρχει script που αλλάζει το display από none σε block-->
            <h2>Εγγραφή</h2><!--εγγραφή, όλα απαιτούμενα-->
            <form method="POST">
                <label>Όνομα:</label>
                <input type="text" name="first_name" required>
                <label>Επώνυμο:</label>
                <input type="text" name="last_name" required>
                <label>Όνομα χρήστη:</label>
                <input type="text" name="username" required>
                <label>Κωδικός:</label>
                <input type="password" name="password" required>
                <label>E-mail:</label>
                <input type="email" name="email" required>
                <input type="submit" name="register" value="Εγγραφή">
                <div class="error"><?= $reg_error ?></div>       
            </form>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script>//σκριπτάκι για την αλλαγή φόρμας
        const loginForm = document.getElementById("loginForm");
        const registerForm = document.getElementById("registerForm");
        const showRegister = document.getElementById("showRegister");

        showRegister.addEventListener("click", () => {//event για την αλλαγή από login σε register
            loginForm.style.display = "none";
            registerForm.style.display = "block";
        });


        <?php if (!empty($reg_error) && empty($reg_success)): ?>//επανεμφανίζει το register form αν υπάρξει σφάλμα
            loginForm.style.display = "none";
            registerForm.style.display = "block";
        <?php endif; ?>

        <?php if (!empty($reg_success)): ?>//alert για επιτυχή εγγραφή με popup που φεύγει μετά απο 2secs
            document.addEventListener("DOMContentLoaded", () => {
                const popup = document.getElementById("popup-message");
                popup.textContent = "Εγγραφήκατε επιτυχώς!";
                popup.classList.add("show");
                setTimeout(() => popup.classList.remove("show"), 2000);
            });
        <?php endif; ?>

    </script>
</body>
</html>
