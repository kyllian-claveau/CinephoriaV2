import '../styles/app.css';

    document.addEventListener("DOMContentLoaded", function () {
    let startDateInput = document.getElementById("session_endDate");
    let endDateInput = document.getElementById("session_endDate");

    startDateInput.addEventListener("change", function () {
    endDateInput.min = startDateInput.value;
    if (endDateInput.value < startDateInput.value) {
    endDateInput.value = startDateInput.value;
}
});
});