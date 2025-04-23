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

//συνάρτηση για τον υπολογισμό της απόστασης, την πήρα από(https://www.geeksforgeeks.org/haversine-formula-to-find-distance-between-two-points-on-a-sphere/)
function calculateDistance(lat1, lon1, lat2, lon2) {
    //Διαφορά γεωγραφικού πλάτους και μήκους σε ακτίνια
    let dLat = (lat2 - lat1) * Math.PI / 180.0;
    let dLon = (lon2 - lon1) * Math.PI / 180.0;

    //μετατροπή των γεωγραφικών πλατών σε ακτίνια
    lat1 = lat1 * Math.PI / 180.0;
    lat2 = lat2 * Math.PI / 180.0;

    //εφαρμογή τύπου
    let a = Math.pow(Math.sin(dLat / 2), 2) +
            Math.pow(Math.sin(dLon / 2), 2) *
            Math.cos(lat1) * Math.cos(lat2);

    let R = 6371; //η ακτίνα όπως μας δώθηκε
    let c = 2 * Math.asin(Math.sqrt(a));

    return R * c; //επιστροφή της απόστασης σε χιλιόμετρα
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

    //υπολογισμός κόστους από τους φόρους των αεροδρομίων και για την πτήση
    const totalTax = flightData.departureTax + flightData.arrivalTax;
    const flightDistance = calculateDistance(flightData.departureLat, flightData.departureLon, flightData.arrivalLat, flightData.arrivalLon);
    const flightCost = flightDistance / 10;

    let html = "<ul style='list-style: none; padding: 0'>";//αρχικοποίηση μεταβλητής που αποθηκεύει html για την εμφάνιση των στοιχείων σε λίστα χωρίς discs
    let totalFinalCost = 0;//για το γενικό κόστος
    let totalSeatCost = 0;//για το κόστος των θέσεων

    //υπολογισμός κόστους θέσης για τον καθένα ξεχωριστά (η εκφώνηση αναφέρει κάτι ελαφρώς διαφορετικό αλλά αυτό έβγαζε περισσότερο νόημα)
    for (let i = 0; i < seatLabels.length; i++) {
        const seat = seatLabels[i];
        const row = parseInt(seat.match(/\d+/)[0]);

        let seatCost = 0;
        //έλεγχος για το αν η θέση έχει κάποιο κόστος
        if ([1, 11, 12].includes(row)) {
            seatCost = 20;
            totalSeatCost += seatCost;
        } else if (row >= 2 && row <= 10) {
            seatCost = 10;
            totalSeatCost += seatCost;
        }

        //υπολογισμός κόστους εισητηρίου για κάθε επιβάτη και πρόσθεση στο τελικό κόστος
        const ticketCost = totalTax + flightCost + seatCost;
        totalFinalCost += ticketCost;

        //προσθήκη των δεδομένων του χρήστη στην μεταβλητή html
        html += `<li><strong>Επιβάτης ${i + 1}:</strong> ${firstNames[i].value} ${lastNames[i].value}
        <br>Θέση: ${seat} <br> Κόστος Θέσης: ${seatCost.toFixed(2)}€ <br> Κόστος Εισιτηρίου: ${ticketCost.toFixed(2)}€<br><br></li>`;
    }

    html += "</ul>";

    //εμφάνιση των παραπάνω όπως θέλουμε και ενεργοποίηση του κουμπιού
    document.getElementById('summary-passengers').innerHTML = html;//χρήση της μεταβλητής html 
    document.getElementById('booking-summary').style.display = 'block';
    document.getElementById('total-cost').textContent = `${totalFinalCost.toFixed(2)}€`;
    document.getElementById('total-cost-input').value = totalFinalCost.toFixed(2);//πέρασμα του value στο κρυφό πεδίο της φόρμας για πέρασμα στην my_trips
    document.getElementById('seat-cost-input').value = totalSeatCost.toFixed(2);//όμοια για τις θέσεις
    document.getElementById('submitButton').disabled = false;//ενεργοποίηση του κουμπιού
}
