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
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['selected_seats'])){//ελέγχω ότι έχουν επιλεχθεί θέσεις για να μην υπάρχει conflict με την φόρμα πιο κάτω
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

    // Ανάκτηση όλων των κρατήσεων για τον χρήστη, join στον airport για να πάρουμε τα ονόματα των αεροδρομίων (ο reservations κρατάει ids)
    $stmt = $conn->prepare("SELECT res.reservation_id, res.total_amount, res.seat_cost, res.reserved_seats_json, res.passenger_names, res.reservation_status, res.departure_airport_id, 
    res.arrival_airport_id, res.reservation_date, a1.airport_name AS departure_airport, a2.airport_name AS arrival_airport 
    FROM reservations AS res 
    JOIN airports AS a1 ON res.departure_airport_id = a1.airport_id
    JOIN airports AS a2 ON res.arrival_airport_id = a2.airport_id
    WHERE res.reservation_id IN (SELECT reservation_id FROM reservation_user WHERE user_id = ?)");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    //αν κάναμε submit για την ακύρωση τότε
    //status=cancelled και ελευθερώνουμε τις θέσεις κάνοντας το json άδειο
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['cancel_reservation_id'])) {
        $cancelId = intval($_POST['cancel_reservation_id']);
        $stmt = $conn->prepare("UPDATE reservations SET reservation_status = 'cancelled', reserved_seats_json = '[]' WHERE reservation_id = ?");
        $stmt->bind_param("i", $cancelId);
        $stmt->execute();
        $stmt->close();
        header('Location: my_trips.php');
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

        <?php if ($result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Αεροδρόμιο Αναχώρησης</th>
                        <th>Αεροδρόμιο Άφιξης</th>
                        <th>Ημερομηνία Πτήσης</th>
                        <th>Θέσεις</th>
                        <th>Ονόματα Επιβατών</th>
                        <th>Κόστος Θέσεων</th>
                        <th>Συνολικό Ποσό</th>
                        <th>Κατάσταση Κράτησης</th>
                        <th>Επιλογές</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <?php 
                            // Ανάκτηση και μετατροπή των JSON δεδομένων
                            $reservedSeats = json_decode($row['reserved_seats_json'], true); 
                            $passengerNames = json_decode($row['passenger_names'], true);
                            
                            //υπολογισμός ημερών μέχρι την πτήση
                            //αν η πτήση είναι στο παρελθόν (invert===0) τότε εμφανίζεται κανονικά αλλά το κουμπί δεν είναι clickable
                            $flightDate = new DateTime($row['reservation_date']);
                            $today = new DateTime();
                            $daysToFlight = $today->diff($flightDate);
                            //όλη η λογική για το αν μπορεί να ακυρωθεί η πτήση
                            $canCancel = $daysToFlight->invert === 0 && $daysToFlight->days >= 30 && $row['reservation_status'] !== 'cancelled';
                        ?>
                    
                        
                        <tr style="background-color: <?php echo $row['reservation_status'] === 'cancelled' ? 'gray' : 'white'; ?>">
                            <td><?php echo htmlspecialchars($row['departure_airport']); ?></td>
                            <td><?php echo htmlspecialchars($row['arrival_airport']); ?></td>
                            <td><?php echo htmlspecialchars($row['reservation_date']); ?></td>
                            <td><?php echo implode("<br><br>", $reservedSeats); ?></td>
                            <td><?php echo implode("<br><br>", $passengerNames); ?></td>
                            <td><?php echo htmlspecialchars($row['seat_cost']); ?> €</td>
                            <td><?php echo htmlspecialchars($row['total_amount']); ?> €</td>
                            <td><?php echo htmlspecialchars($row['reservation_status']); ?></td>
                            <td>
                                <form method="post" class="cancel">
                                    <input type="hidden" name="cancel_reservation_id" value="<?php echo $row['reservation_id']; ?>">
                                    <button type="submit" <?php echo !$canCancel ? 'disabled class="disabled"' : ''; ?>>Ακύρωση κράτησης</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <h2>Δεν έχετε κάνει καμία κράτηση ακόμα.</h2>
        <?php endif; ?>
    </div>

<?php include 'footer.php'; ?>

</body>
</html>