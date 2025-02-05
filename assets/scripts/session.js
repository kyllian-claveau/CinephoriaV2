document.addEventListener("DOMContentLoaded", function () {
    // Gestion des dates
    let startDateInput = document.getElementById("session_startDate");
    let endDateInput = document.getElementById("session_endDate");

    if (startDateInput && endDateInput) {
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
    }

    // Gestion du changement de film
    const filmSelect = document.getElementById('session_film');
    if (filmSelect) {
        filmSelect.addEventListener('change', function () {
            const filmId = this.value;
            const cinemasContainer = document.getElementById('cinemas-container');

            // Détecter si nous sommes sur la page admin ou employee
            const isAdmin = window.location.pathname.includes('/admin/');
            const baseUrl = isAdmin ? '/admin/api' : '/employee/api';

            if (filmId) {
                fetch(`${baseUrl}/films/${filmId}/cinemas`)
                    .then(response => response.json())
                    .then(data => {
                        cinemasContainer.innerHTML = '';

                        if (data.length > 0) {
                            cinemasContainer.style.display = 'block';

                            data.forEach(cinema => {
                                const div = document.createElement('div');
                                div.classList.add('flex', 'items-center');

                                const checkbox = document.createElement('input');
                                checkbox.type = 'checkbox';
                                checkbox.name = 'session[cinemas][]';
                                checkbox.value = cinema.id;
                                checkbox.id = `cinema-${cinema.id}`;

                                const label = document.createElement('label');
                                label.setAttribute('for', `cinema-${cinema.id}`);
                                label.textContent = cinema.name;
                                label.classList.add('ml-2');

                                div.appendChild(checkbox);
                                div.appendChild(label);

                                cinemasContainer.appendChild(div);
                            });
                        } else {
                            cinemasContainer.style.display = 'none';
                        }
                    })
                    .catch(error => console.error('Erreur:', error));
            } else {
                cinemasContainer.style.display = 'none';
            }
        });
    }
});