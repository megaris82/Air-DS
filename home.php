<?php
#έλεγχος αν είναι logged in και ανάκτηση user_id μέσω του cookie (για χρήση στο μετέπειτα interface)
$isLoggedIn = isset($_COOKIE['isLoggedIn']) && !empty($_COOKIE['isLoggedIn']);
$user_id = $_COOKIE['isLoggedIn'] ?? null;

$host = 'localhost';
$db = 'air_ds';
$user = 'root';
$pass = '';
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Αδυναμία σύνδεσης με την βάση δεδομένων: " . $conn->connect_error);
}

#ανάκτηση των αεροδρομίων και αποθήκευση σε πίνακα
$airports_query = "SELECT airport_id, airport_name, airport_code FROM airports";
$result = $conn->query($airports_query);
$airports = [];
while ($row = $result->fetch_assoc()) {
    $airports[] = $row;
}

#διαγραφή κρατήσεων που είναι ακόμα σε κατάσταση 'pending'
if ($isLoggedIn && $user_id !== null) {#έλεγχος ότι είναι logged in και ότι υπάρχει user_id για να μην έχουμε θέμα με null 
    #πρώτα διαγραφή από τον reservation_user γιατί παίρνει foreign key από τον reservations
    $deleteReservationUserQuery = "DELETE FROM reservation_user WHERE reservation_id IN (
        SELECT reservation_id FROM reservations WHERE reservation_status = 'pending' AND reservation_id IN (
            SELECT reservation_id FROM reservation_user WHERE user_id = ?)
    )";
    $stmt = $conn->prepare($deleteReservationUserQuery);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();

    #έπειτα διαγραφή από τον reservations όπου η κατάσταση pending και το id δεν υπάρχει μέσα στον reservation_user
    $deletePendingReservationsQuery = "DELETE FROM reservations WHERE reservation_status = 'pending' AND
    reservation_id NOT IN (SELECT DISTINCT reservation_id FROM reservation_user)";
    $stmt = $conn->prepare($deletePendingReservationsQuery);
    $stmt->execute();
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Home Page</title>
    <link rel="stylesheet" href="home.css">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <!--φόρμα για την κράτηση-->
    <form action="book_flight.php" method="POST">
        <label for="departure_airport">Αεροδρόμιο Αναχώρησης:</label>
        <select name="departure_airport" id="departure_airport" required>
            <option value="" disabled selected>Επιλέξτε Αναχώρηση</option>
            <?php foreach ($airports as $airport): ?>
                <option value="<?= $airport['airport_id'] ?>"><?= $airport['airport_name'] ?> (<?= $airport['airport_code'] ?>)</option>
            <?php endforeach; ?>
        </select>
        
        <label for="arrival_airport">Αεροδρόμιο Άφιξης:</label>
        <select name="arrival_airport" id="arrival_airport" required>
            <option value="" disabled selected>Επιλέξτε Άφιξη</option>
            <?php foreach ($airports as $airport): ?>
                <option value="<?= $airport['airport_id'] ?>"><?= $airport['airport_name'] ?> (<?= $airport['airport_code'] ?>)</option>
            <?php endforeach; ?>
        </select>

        <label for="flight_date">Ημερομηνία Πτήσης:</label>
        <input type="date" name="flight_date" id="flight_date" required>

        <label for="passenger_count">Πλήθος Επιβατών:</label>
        <input type="number" name="passenger_count" id="passenger_count" required>

            <!--submit button που είναι απενεργοποιημένο αν δεν είναι logged in o user, 
            είναι ενεργοποιημένο αν δεν έχει συμπληρωθεί κάτι από τα παραπάνω αλλά δεν κάνει submit λόγω του required -->
        <button type="submit" id="submitBtn" <?= !$isLoggedIn ? 'disabled' : '' ?>>Κάντε Κράτηση</button>
    </form>

    <?php include 'footer.php'; ?>

    <script>//σκριπτάκι για να μην φαίνεται το αεροδρόμιο που έχει ήδη επιλεχθεί για αναχώρηση
        const departureSelect = document.getElementById('departure_airport');
        const arrivalSelect = document.getElementById('arrival_airport');

        function updateArrivalOptions() {
            const selectedDeparture = departureSelect.value;

            for (let option of arrivalSelect.options) {//επαναφορά όλων των επιλογών στην άφιξη
                option.hidden = false;
            }

            for (let option of arrivalSelect.options) {//κρύβει την επιλογή που έχει επιλεχθεί για αναχώρηση
                if (option.value === selectedDeparture) {
                    option.hidden = true;
                }
            }

            if (arrivalSelect.value === selectedDeparture) {//αν η άφιξη ίδια με την αναχώρηση τότε η άφιξη γίνεται άδεια
                arrivalSelect.value = "";
            }
        }
        //ενημέρωση της λίστας για αεροδρόμια άφιξης όταν αλλάζει το αεροδρόμιο αναχώρησης
        departureSelect.addEventListener('change', updateArrivalOptions);
    </script>
</body>
</html>
