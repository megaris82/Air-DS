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
    
    //insert εγγραφής στην reservations
    // Εισαγωγή νέας κράτησης στη βάση δεδομένων με κενό JSON για τις θέσεις
    $insertReservationQuery = "INSERT INTO reservations (departure_airport_id, arrival_airport_id,reservation_date,reservation_status,reserved_seats_json,total_amount)
    VALUES (?, ?, ?, 'pending', ?, 0)";//pending (αλλάζει σε cancelled/valid), totalcost 0 (θα υπολογιστεί αργότερα)
    $stmt = $conn->prepare($insertReservationQuery);
    $empty_reserved_seats_json = json_encode([]);//άδειο json για τις θέσεις, update μετά
    $stmt->bind_param("iiss", $departure_airport_id, $arrival_airport_id, $flight_date, $empty_reserved_seats_json);
    $stmt->execute();

    $reservation_id = $stmt->insert_id;//αποθήκευση του reservation_id της τελευταίας εγγραφής
    $stmt->close();

    //αντιστοίχηση της κράτησης με τον χρήστη
    $insertUserReservationQuery = "INSERT INTO reservation_user (reservation_id, user_id)VALUES (?, ?)";
    $stmt = $conn->prepare($insertUserReservationQuery);
    $stmt->bind_param("ii", $reservation_id, $user_id);
    $stmt->execute();
    $stmt->close();

    //ανάκτηση στοιχείων που δεν περνάνε με την φόρμα

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

    $conn->close();
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
                        required pattern=\"[A-Za-zΑ-Ωα-ω]{3,20}\">";

            echo "<label>Επώνυμο:</label>";
            echo "<input type='text' name='last_name[]' 
                        value=\"" . ($i == 0 ? htmlspecialchars($lastName) : '') . "\" 
                        " . ($i == 0 ? 'readonly' : '') . " 
                        required pattern=\"[A-Za-zΑ-Ωα-ω]{3,20}\"
                        oninput='checkLastNameInput($i, $passengerCount)'>";
            echo "</div>";
        }
        ?>


            <!-- Seat Map and Selection -->
            <div id="seat-map" class="seat-map" style="display: none;">
                <h2>Επιλογή Θέσεων</h2>
                <div class="seat-legend">
                    <div class="legend-item"><div class="seat-available"></div> <span>Διαθέσιμη</span></div>
                    <div class="legend-item"><div class="seat-selected"></div> <span>Επιλεγμένη</span></div>
                    <div class="legend-item"><div class="up-front"></div> <span>Μπροστά Θέση</span></div>
                    <div class="legend-item"><div class="exit-row-indicator"></div> <span>Έξοδος κινδύνου</span></div>
                    <div class="legend-item"><div class="extra-legroom"></div> <span>Επιπλέον Χώρος Ποδιών</span></div>
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
                    
                        // Assign class based on row number
                        if ($row == 11) {
                            $rowClass = ' exit-row extra-legroom';
                        } else if ($row == 1 || $row == 12) {
                            $rowClass = ' extra-legroom';
                        } else if ($row >= 2 && $row <= 10) {
                            $rowClass = ' up-front';
                        }
                    
                        echo "<div class='seat-row$rowClass'>";
                        echo "<div class='row-number'>$row</div>";
                    
                        foreach ($columns as $col) {
                            $seatId = $row . $col;
                            $seatClass = 'seat-available';
                            echo "<div class='seat $seatClass' id='seat-$seatId' onclick='toggleSeat(this)'>$col</div>";
                            if ($col === 'C') echo "<div class='aisle'></div>";
                        }
                    
                        echo "</div>";
                    }
                    ?>
                </div>
            </div>
            <div id="booking-summary" style="display: none;">
                <h3>Επισκόπηση Κράτησης</h3>
                <div id="summary-passengers"></div>
                <p><strong>Από:</strong> <?= htmlspecialchars($dep_name); ?></p>
                <p><strong>Προς:</strong> <?= htmlspecialchars($arr_name); ?></p>
                <p><strong>Ημερομηνία Πτήσης:</strong> <?= htmlspecialchars($flight_date); ?></p>
                <p><strong>Συνολικό Κόστος:</strong> — €</p>
            </div>
            

            <input type="hidden" name="selected_seats" id="selected-seats-input" value="">
            <button type="submit">Κάντε Κράτηση</button>
        </form>
    </div>

    <?php include 'footer.php'; ?>

    <script>
document.addEventListener("DOMContentLoaded", function() {
    const passengerCount = <?= ($passengerCount); ?>;

    if (passengerCount === 1) {
        checkLastNameInput(0, passengerCount);
    }
});

function checkLastNameInput(i, passengerCount) {
    const lastNameInput = document.getElementsByName('last_name[]')[i];
    const seatMap = document.getElementById('seat-map');

    const isValid = lastNameInput.value.length >= 3 &&
                    lastNameInput.value.length <= 20 &&
                    /^[A-Za-zΑ-Ωα-ω]+$/.test(lastNameInput.value);

    if (i === passengerCount - 1) {
        seatMap.style.display = isValid ? 'block' : 'none';
    }
}

function toggleSeat(seatDiv) {
    if (!seatDiv.classList.contains('seat-available')) return;

    seatDiv.classList.toggle('seat-selected');

    const maxSeats = <?= ($passengerCount); ?>;
    const selectedSeats = document.querySelectorAll('.seat.seat-selected');
    const seatMap = document.getElementById('seat-map');
    const passengerInputs = document.querySelectorAll('.passenger');


    updateSelectedSeatsInput();

    if (selectedSeats.length === maxSeats) {
        seatMap.style.display = 'none';
        passengerInputs.forEach(div => div.style.display = 'none');

        // Show summary
        const summaryDiv = document.getElementById('booking-summary');
        const summaryPassengers = document.getElementById('summary-passengers');
        const firstNames = document.getElementsByName('first_name[]');
        const lastNames = document.getElementsByName('last_name[]');

        const seatLabels = Array.from(selectedSeats).map(seat => {
            const row = seat.closest('.seat-row').querySelector('.row-number').textContent;
            const col = seat.textContent.trim();
            return row + col;
        });

        let html = "<ul style='list-style-type: none; padding-left: 0;'>";
        for (let i = 0; i < maxSeats; i++) {
            html += `<li><strong>Επιβάτης ${i + 1}:</strong> ${firstNames[i].value} ${lastNames[i].value} <br> Θέση ${seatLabels[i]}</li>`;
        }
        html += "</ul>";

        summaryPassengers.innerHTML = html;
        summaryDiv.style.display = 'block';
    }
}

function updateSelectedSeatsInput() {
    const selectedSeats = Array.from(document.querySelectorAll('.seat.seat-selected'))
        .map(seat => {
            const rowDiv = seat.closest('.seat-row');
            const rowNumber = rowDiv.querySelector('.row-number').textContent;
            const seatLetter = seat.textContent.trim();
            return rowNumber + seatLetter;
        });

    document.getElementById('selected-seats-input').value = JSON.stringify(selectedSeats);
}
</script>



</body>
</html>
