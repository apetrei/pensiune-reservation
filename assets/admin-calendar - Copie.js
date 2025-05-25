document.addEventListener('DOMContentLoaded', function () {
    const calendarEl = document.getElementById('pensiune-admin-calendar');
    let occupiedDates = [];

    // Formatează un obiect Date în string "YYYY-MM-DD"
    function formatDateToYYYYMMDD(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    // Verifică dacă o dată este ocupată
    function isOccupied(dateStr) {
        return occupiedDates.includes(dateStr);
    }

    // Încarcă zilele ocupate de pe server
    function loadOccupiedDates() {
        return fetch(pensiuneAdminAjax.ajax_url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'pensiune_get_occupied_dates',
                nonce: pensiuneAdminAjax.nonce
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                occupiedDates = data.data;
            } else {
                occupiedDates = [];
                console.warn('Eroare la încărcarea zilelor ocupate:', data.data);
            }
        });
    }

    // Adaugă un interval de zile ocupate
    function addRangeOccupied(startStr, endStr) {
        return fetch(pensiuneAdminAjax.ajax_url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'pensiune_add_range',
                nonce: pensiuneAdminAjax.nonce,
                start_date: startStr,
                end_date: endStr
            })
        })
        .then(res => res.json())
        .then(data => {
            if (!data.success) throw new Error(data.data || 'Eroare la adăugare');
        });
    }

    // Șterge o zi ocupată
    function removeOccupiedDate(dateStr) {
        return fetch(pensiuneAdminAjax.ajax_url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'pensiune_remove_date',
                nonce: pensiuneAdminAjax.nonce,
                date: dateStr
            })
        })
        .then(res => res.json())
        .then(data => {
            if (!data.success) throw new Error(data.data || 'Eroare la ștergere');
        });
    }

    // Inițializează calendarul după încărcarea zilelor ocupate
    loadOccupiedDates().then(() => {
        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            locale: 'ro',
            selectable: true,
            height: 'auto',
            buttonText: { today: 'Astăzi' },

            // Marchează zilele ocupate ca evenimente roșii
            events(fetchInfo, successCallback) {
                const events = occupiedDates.map(dateStr => ({
                    title: 'Ocupat',
                    start: dateStr,
                    allDay: true,
                    color: 'red'
                }));
                successCallback(events);
            },

            // Selectarea unui interval de zile ocupate
            select(info) {
                const startStr = formatDateToYYYYMMDD(info.start);
                // FullCalendar select include și ziua următoare, scădem 1 zi
                const endDate = new Date(info.end);
                endDate.setDate(endDate.getDate() - 1);
                const endStr = formatDateToYYYYMMDD(endDate);

                if (startStr === endStr) return;

                if (confirm(`Marchezi zilele de la ${startStr} la ${endStr} ca ocupate?`)) {
                    addRangeOccupied(startStr, endStr)
                        .then(() => loadOccupiedDates())
                        .then(() => {
                            calendar.refetchEvents();
                            alert(`Zilele de la ${startStr} la ${endStr} au fost marcate ca ocupate.`);
                        })
                        .catch(err => alert('Eroare la adăugare: ' + err.message));
                }
            },

            // Click pe o zi: adaugă sau șterge zi ocupată
            dateClick(info) {
                const dateStr = formatDateToYYYYMMDD(info.date);

                if (isOccupied(dateStr)) {
                    if (confirm(`Ziua ${dateStr} este ocupată. Ștergi această zi?`)) {
                        removeOccupiedDate(dateStr)
                            .then(() => loadOccupiedDates())
                            .then(() => {
                                calendar.refetchEvents();
                                alert(`Ziua ${dateStr} a fost ștearsă.`);
                            })
                            .catch(err => alert('Eroare la ștergere: ' + err.message));
                    }
                } else {
                    if (confirm(`Marchezi ziua ${dateStr} ca ocupată?`)) {
                        addRangeOccupied(dateStr, dateStr)
                            .then(() => loadOccupiedDates())
                            .then(() => {
                                calendar.refetchEvents();
                                alert(`Ziua ${dateStr} a fost marcată ca ocupată.`);
                            })
                            .catch(err => alert('Eroare la adăugare: ' + err.message));
                    }
                }
            }
        });

        calendar.render();

        // Export CSV
        document.getElementById('export-csv').addEventListener('click', async function(e) {
            e.preventDefault();

            try {
                const response = await fetch(pensiuneAdminAjax.ajax_url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'pensiune_export_csv',
                        nonce: pensiuneAdminAjax.nonce
                    })
                });

                const contentType = response.headers.get('Content-Type') || '';

                if (contentType.includes('application/json')) {
                    const data = await response.json();
                    if (!data.success) {
                        alert(data.data || 'Nu există date de exportat.');
                        return;
                    }
                } else if (contentType.includes('text/csv')) {
                    const blob = await response.blob();
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = 'rezervari_pensiune.csv';
                    document.body.appendChild(a);
                    a.click();
                    a.remove();
                    window.URL.revokeObjectURL(url);
                } else {
                    alert('Răspuns necunoscut de la server.');
                }
            } catch (error) {
                alert('Eroare la export.');
                console.error(error);
            }
        });
    });
});
