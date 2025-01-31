import '../styles/app.css';

    document.addEventListener("DOMContentLoaded", function () {
    let startDateInput = document.getElementById("session_startDate");
    let endDateInput = document.getElementById("session_endDate");

    startDateInput.addEventListener("change", function () {
    let startDateTime = new Date(startDateInput.value);

    if (!isNaN(startDateTime.getTime())) {
    let minDateTime = startDateTime.toISOString().slice(0, 16);
    endDateInput.min = minDateTime;

    let endDateTime = new Date(endDateInput.value);
    if (endDateTime < startDateTime) {
    endDateInput.value = minDateTime;
}
}
});
});
