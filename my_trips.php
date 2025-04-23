<?php
    #ανάκτηση user_id μέσω του cookie και σύνδεση στην βάση
    $user_id = $_COOKIE['isLoggedIn'] ?? null;

    #έλεγχος αν είναι logged in και ανάκτηση user_id μέσω του cookie (ίδιο με home.php) μόνο που εδώ κάνει redirect αυτόματα
    $isLoggedIn = isset($_COOKIE['isLoggedIn']) && !empty($_COOKIE['isLoggedIn']);
    if (!$isLoggedIn) {
        header('Location: login.php');
        exit();
    }

    $host = 'localhost';
    $db = 'air_ds';
    $user = 'root';
    $pass = '';
    $conn = new mysqli($host, $user, $pass, $db);

    if ($conn->connect_error) {
        die("Αδυναμία σύνδεσης με την βάση δεδομένων: " . $conn->connect_error);
    }

    //αν ήρθε φόρμα με post τότε προχωράμε στην ενημέρωση της κράτησης
    //χρησιμοποιείται το if επειδή μπορεί να μπει στο my_trips και απευθείας μέσω navbar χωρίς συμπλήρωση φόρμας
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        //ανάκτηση των στοιχείων που στέλνει η home.php φόρμα με post	
        $selectedSeats = $_POST['selected_seats'];
        $totalCost = $_POST['total_cost'];
        $firstNames = $_POST['first_name'];
        $lastNames = $_POST['last_name'];
        $seatsCost = $_POST['seat_cost'];

        //δημιουργία php και έπειτα json array με ολόκληρα τα ονόματα των επιβατών
        $passengerFullNames = [];
        for ($i = 0; $i < count($firstNames); $i++) {
            $fullName = trim($firstNames[$i] . ' ' . $lastNames[$i]);
            $passengerFullNames[] = $fullName;
        }
        $passengerNamesJson = json_encode($passengerFullNames, JSON_UNESCAPED_UNICODE);

        //ανάκτηση της τελευταίας κράτησης του χρήστη 
        $stmt = $conn->prepare("SELECT reservation_id FROM reservation_user WHERE user_id = ? ORDER BY reservation_id DESC LIMIT 1");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($reservation_id);
        $stmt->fetch();
        $stmt->close();
    
        // Ενημέρωση κράτησης με τις θέσεις και το συνολικό ποσό
        $stmt = $conn->prepare("UPDATE reservations SET reserved_seats_json = ?, total_amount = ?, seat_cost = ?, passenger_names = ?, reservation_status = 'confirmed' WHERE reservation_id = ?");
        $stmt->bind_param("sddsi", $selectedSeats, $totalCost, $seatsCost, $passengerNamesJson, $reservation_id);
        $stmt->execute();
        

        $stmt->close();
    }
    
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My trips</title>
    <link rel="stylesheet" href="my_trips.css">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container">
        <h1>Λεπτομέρειες Κράτησης</h1>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>