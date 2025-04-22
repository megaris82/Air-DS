document.addEventListener("DOMContentLoaded", function () {
    const passengerCount = flightData.passengerCount;
    if (passengerCount === 1) {//εμφάνιση του seatmap αν έχουμε 1 μόνο passenger (τον user)
        showSeatMap(0, passengerCount);
    }
});

//επαλήθευση του lastname και εμφάνιση seatmap όταν συμπληρωθεί το lastname του τελευταίου επιβάτη 
function showSeatMap(i, passengerCount) {
    const lastNameInput = document.getElementsByName('last_name[]')[i];
    const seatMap = document.getElementById('seat-map');

    const isValid = lastNameInput.value.length >= 3 &&
        lastNameInput.value.length <= 20 &&
        /^[A-Za-zΑ-Ωα-ω]+$/.test(lastNameInput.value);//validation όπως και στο book_flight.php

    //
    if (i === passengerCount - 1) {
        seatMap.style.display = isValid ? 'block' : 'none';//αν είναι έγκυρο το lastname του τελευταίου επιβάτη τότε εμφανίζεται το seatmap
    }
}

//υλοποίηση επιλογής θέσης
function toggleSeat(seatDiv) {
    //αν η θέση δεν είναι διαθέσιμη τότε δεν κάνουμε τίποτα
    if (!seatDiv.classList.contains('seat-available')) return;

    //αλιώς αλλάζουμε την κλάση της σε διαθέσιμη
    seatDiv.classList.toggle('seat-selected');

    const maxSeats = flightData.passengerCount;//πόσες θέσεις χρειαζόμαστε
    const selectedSeats = document.querySelectorAll('.seat.seat-selected');//ποιες θέσεις έχουμε επιλέξει
    const seatMap = document.getElementById('seat-map');
    const passengerInputs = document.querySelectorAll('.passenger');

    //κλήση συνάρτησης για το πέρασμα τον επιλεγμένων θέσεων στην φόρμα
    updateSelectedSeatsInput();

    //αν έχουν επιλεχθεί όσες θέσεις χρειάζεται τότε δείξε booking summary και κρύψε seatMap
    if (selectedSeats.length === maxSeats) {
        seatMap.style.display = 'none';
        passengerInputs.forEach(div => div.style.display = 'none');
        showBookingSummary();
    }
}

//συνάρτηση για ενημέρωση του κρυφού πεδίου της φόρμας για το πέρασμα των θέσεων
function updateSelectedSeatsInput() {
    const selectedSeats = Array.from(document.querySelectorAll('.seat.seat-selected'))
        .map(seat => {
            const rowDiv = seat.closest('.seat-row');//επιλογή της σειράς
            const rowNumber = rowDiv.querySelector('.row-number').textContent;//παίρνουμε το νούμερο ως text
            const seatLetter = seat.textContent;//παίρνουμε το γράμμα από την θέση
            return rowNumber + seatLetter; //concarteration για αποθήκευση ως string π.χ. 1A
        });

    //αποθήκευση των επιλεγμένων θέσεων στο κρυφό πεδίο ως json array
    document.getElementById('selected-seats-input').value = JSON.stringify(selectedSeats);
}

//συνάρτηση για τον υπολογισμό της απόστασης
function calculateDistance(lat1, lon1, lat2, lon2) {
    const toRad = angle => angle * Math.PI / 180;
    const R = 6371;
    const dLat = toRad(lat2 - lat1);
    const dLon = toRad(lon2 - lon1);
    const a = Math.sin(dLat / 2) ** 2 +
        Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) *
        Math.sin(dLon / 2) ** 2;
    const c = 2 * Math.asin(Math.sqrt(a));
    return R * c;
}

//συνάρτηση για την προβολή των στοιχείων της κράτησης και τον υπολογισμό του κόστους
function showBookingSummary() {
    const firstNames = document.getElementsByName('first_name[]');
    const lastNames = document.getElementsByName('last_name[]');
    const selectedSeats = document.querySelectorAll('.seat.seat-selected');
    const seatLabels = Array.from(selectedSeats).map(seat => {
        const rowDiv = seat.closest('.seat-row');
        const row = rowDiv.querySelector('.row-number').textContent;
        const letter = seat.textContent.trim();
        return row + letter;
    });

    // Calculate tax and flight cost
    const totalTax = flightData.departureTax + flightData.arrivalTax;
    const flightDistance = calculateDistance(flightData.departureLat, flightData.departureLon, flightData.arrivalLat, flightData.arrivalLon);
    const flightCost = flightDistance / 10;

    let html = "<ul style='list-style: none; padding: 0'>";
    let totalFinalCost = 0;

    // Loop through the selected seats to calculate ticket costs
    for (let i = 0; i < seatLabels.length; i++) {
        const seat = seatLabels[i];
        const row = parseInt(seat.match(/\d+/)[0]);

        let seatCost = 0;
        // Determine seat cost based on row number
        if ([1, 11, 12].includes(row)) {
            seatCost = 20;
        } else if (row >= 2 && row <= 10) {
            seatCost = 10;
        }

        // Calculate the total ticket cost per passenger
        const ticketCost = totalTax + flightCost + seatCost;
        totalFinalCost += ticketCost;

        // Add passenger details to the HTML summary
        html += `<li><strong>Επιβάτης ${i + 1}:</strong> ${firstNames[i].value} ${lastNames[i].value}
        <br>Θέση: ${seat} <br> Κόστος Θέσης: ${seatCost.toFixed(2)}€ <br> Κόστος Εισιτηρίου: ${ticketCost.toFixed(2)}€<br><br></li>`;
    }

    html += "</ul>";

    // Display the summary in the booking summary section
    document.getElementById('summary-passengers').innerHTML = html;
    document.getElementById('booking-summary').style.display = 'block';
    document.getElementById('total-cost').textContent = `${totalFinalCost.toFixed(2)}€`;
    document.getElementById('submitButton').disabled = false;
}
