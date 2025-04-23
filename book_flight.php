<?php
    #ανάκτηση user_id μέσω του cookie και σύνδεση στην βάση
    $user_id = $_COOKIE['isLoggedIn'] ?? null;

    $host = 'localhost';
    $db = 'air_ds';
    $user = 'root';
    $pass = '';
    $conn = new mysqli($host, $user, $pass, $db);

    if ($conn->connect_error) {
        die("Αδυναμία σύνδεσης με την βάση δεδομένων: " . $conn->connect_error);
    }

    //ανάκτηση των στοιχείων που στέλνει η home.php φόρμα με post
    $passengerCount = $_POST['passenger_count'] ?? '';
    $departure_airport_id = $_POST['departure_airport'] ?? '';
    $arrival_airport_id = $_POST['arrival_airport'] ?? '';
    $flight_date = $_POST['flight_date'] ?? '';

    //ανάκτηση των στοιχείων του user με id=user_id
    $sql = "SELECT first_name, last_name FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($firstName, $lastName);
    $stmt->fetch();
    $stmt->close();

    //ανάκτηση των δεδομένων των αεροδρομίων από την βάση
    $stmt = $conn->prepare("SELECT airport_name, latitude, longitude, airport_tax FROM airports WHERE airport_id = ?");
    $stmt->bind_param("i", $departure_airport_id);
    $stmt->execute();
    $stmt->bind_result($dep_name, $dep_lat, $dep_lon, $dep_tax);
    $stmt->fetch();
    $stmt->close();

    $stmt = $conn->prepare("SELECT airport_name, latitude, longitude, airport_tax FROM airports WHERE airport_id = ?");
    $stmt->bind_param("i", $arrival_airport_id);
    $stmt->execute();
    $stmt->bind_result($arr_name, $arr_lat, $arr_lon, $arr_tax);
    $stmt->fetch();
    $stmt->close();



    //για να γίνει σωστά το insert αλλά με άδειες τιμές αρχικοποιώ τα εξής
    $empty_reserved_seats_json = null;
    $passenger_names_json = null;

    //insert εγγραφής στην reservations
    //εισαγωγή νέας κράτησης στη βάση δεδομένων με κενό JSON για τις θέσεις και τους επιβάτες και κατάσταση 'pending'
    $insertReservationQuery = "INSERT INTO reservations (departure_airport_id, arrival_airport_id, reservation_date, reservation_status, reserved_seats_json,
    total_amount, departure_tax, arrival_tax, seat_cost, passenger_names) VALUES (?, ?, ?, 'pending', ?, 0, ?, ?, 0, ?)";
    $stmt = $conn->prepare($insertReservationQuery);
    $stmt->bind_param("iissiis",$departure_airport_id, $arrival_airport_id, $flight_date,
    $empty_reserved_seats_json, $dep_tax, $arr_tax, $passenger_names_json);
    $stmt->execute();
    $reservation_id = $stmt->insert_id;
    $stmt->close();

    //αντιστοίχηση της κράτησης με τον χρήστη
    $insertUserReservationQuery = "INSERT INTO reservation_user (reservation_id, user_id)VALUES (?, ?)";
    $stmt = $conn->prepare($insertUserReservationQuery);
    $stmt->bind_param("ii", $reservation_id, $user_id);
    $stmt->execute();
    $stmt->close();

    $conn->close();
    /*αν ο χρήστης δεν ολοκληρώσει την κράτηση με την χρήση 
    του κουμπιού τότε όταν επιστρέψει στο home.php τότε η κράτηση του θα γίνει delete*/
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Flight</title>
    <link rel="stylesheet" href="book_flight.css">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container">
        <h1>Κράτηση Πτήσης</h1>
        <form action="my_trips.php" method="POST">
            <?php
            for ($i = 0; $i < $passengerCount; $i++) {
                echo "<div class='passenger'>";
                echo "<label>Όνομα:</label>";
                echo "<input type='text' name='first_name[]' 
                            value=\"" . ($i == 0 ? htmlspecialchars($firstName) : '') . "\" 
                            " . ($i == 0 ? 'readonly' : '') . " 
                            required pattern=\"[A-Za-zΑ-Ωα-ω]{3,20}\">";//έλεγχος για το αν το όνομα είναι έγκυρο, το πρώτο read only

                echo "<label>Επώνυμο:</label>";
                echo "<input type='text' name='last_name[]' 
                            value=\"" . ($i == 0 ? htmlspecialchars($lastName) : '') . "\" 
                            " . ($i == 0 ? 'readonly' : '') . " 
                            required pattern=\"[A-Za-zΑ-Ωα-ω]{3,20}\"
                            oninput='showSeatMap($i, $passengerCount)'>";//όμοια + καλεί την js για να εμφανίσει την επιλογη θέσεων	
                echo "</div>";
            }
            ?>

            <div id="seat-map" class="seat-map" style="display: none;"><!--επιλογή θέσεων, με legend-->
                <h2>Επιλογή Θέσεων</h2>
                <div class="seat-legend">
                    <div class="legend-item"><div class="seat-available"></div><span>Διαθέσιμη</span></div>
                    <div class="legend-item"><div class="seat-selected"></div><span>Επιλεγμένη</span></div>
                    <div class="legend-item"><div class="up-front"></div><span>Μπροστά Θέση</span></div>
                    <div class="legend-item"><div class="exit-row-indicator"></div><span>Έξοδος κινδύνου</span></div>
                    <div class="legend-item"><div class="extra-legroom"></div><span>Επιπλέον Χώρος Ποδιών</span></div>
                </div>

                <div class="airplane-container">
                    <?php
                    $rows = range(1, 31);
                    $columns = ['A', 'B', 'C', 'D', 'E', 'F'];
                    $exitRows = [11];
                    $extraLegroomRows = [1, 11, 12];
                    $upFrontRows = range(2, 10);

                    foreach ($rows as $row) {
                        $rowClass = '';

                        //έλεγχος για το αν η σειρά αυτή έχει ιδιαίτερο χαρακτηριστικό
                        if ($row == 11) {
                            $rowClass = ' exit-row extra-legroom';
                        } else if ($row == 1 || $row == 12) {
                            $rowClass = ' extra-legroom';
                        } else if ($row >= 2 && $row <= 10) {
                            $rowClass = ' up-front';
                        }

                        echo "<div class='seat-row$rowClass'>";//δημιουργία σειρών
                        echo "<div class='row-number'>$row</div>";//εμφάνιση αριθμού σειράς
                        
                        foreach ($columns as $col) {//δημιουργία στήλων 
                            $seatId = $row . $col;
                            $seatClass = 'seat-available';
                            echo "<div class='seat $seatClass' id='seat-$seatId' onclick='toggleSeat(this)'>$col</div>";//επιλογή θέσης υλοποίηση στο book_flight.js
                            if ($col === 'C') echo "<div class='aisle'></div>";//δημιουργία του διαδρόμου
                        }

                        echo "</div>";
                    }
                    ?>
                </div>
            </div>
            
            <!--επισκόπηση κράτησης αφού συμπληρωθεί το επίθετο του τελευταίου passenger-->
            <div id="booking-summary" style="display: none;">
                <h3>Επισκόπηση Κράτησης</h3>
                <div id="summary-passengers"></div>
                <p><strong>Από:</strong> <?= htmlspecialchars($dep_name); ?></p>
                <p><strong>Προς:</strong> <?= htmlspecialchars($arr_name); ?></p>
                <p><strong>Ημερομηνία Πτήσης:</strong> <?= htmlspecialchars($flight_date); ?></p>
                <p><strong>Συνολικό Κόστος:</strong> <span id="total-cost"></span></p>
            </div>
                
            <input type="hidden" name="reservation_id" value="<?= $reservation_id ?>"><!--πεδίο για το πέρασμα του reservation_id-->
            <input type="hidden" name="selected_seats" id="selected-seats-input" value=""><!--πεδίο για το πέρασμα των θέσεων με την φόρμα-->
            <input type="hidden" name="total_cost" id="total-cost-input" value=""><!--πεδίο για το πέρασμα του κόστους-->
            <input type="hidden" name="seat_cost" id="seat-cost-input"  value=""><!-- για το κόστος των θέσεων-->
            <button type="submit" class="submitButton" id="submitButton" disabled>Κάντε Κράτηση</button><!--το submittion button που αρχικά είναι disabled (μέχρι να κληθεί η showBookingSummary)-->
        </form>
    </div>

    <?php include 'footer.php'; ?>
    
    <script>//πέρασμα των δεδομένων από την βάση στην js
        const flightData = {
            passengerCount: <?= $passengerCount ?>,
            departureTax: <?= $dep_tax ?>,
            arrivalTax: <?= $arr_tax ?>,
            departureLat: <?= $dep_lat ?>,
            departureLon: <?= $dep_lon ?>,
            arrivalLat: <?= $arr_lat ?>,
            arrivalLon: <?= $arr_lon ?>
        };
    </script>
    <script src="book_flight.js"></script><!--σύνδεση με την υπόλοιπη js-->
</body>
</html>
