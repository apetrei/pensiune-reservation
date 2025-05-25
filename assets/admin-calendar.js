document.addEventListener('DOMContentLoaded', function () {
    const calendarEl = document.getElementById('pensiune-admin-calendar');

    let occupiedDates = [];

    function formatDateToYYYYMMDD(date) {
        const year = date.getFullYear();
        const month = (date.getMonth() + 1).toString().padStart(2, '0');
        const day = date.getDate().toString().padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    function isOccupied(dateStr) {
        return occupiedDates.includes(dateStr);
    }

    function loadOccupiedDates() {
        return fetch(pensiuneAdminAjax.ajax_url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'pensiune_get_occupied_dates',
                nonce: pensiuneAdminAjax.nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                occupiedDates = data.data;
            } else {
                occupiedDates = [];
            }
        });
    }

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
        .then(response => response.json())
        .then(data => {
            if (!data.success) throw new Error(data.data || 'Eroare la adăugare');
        });
    }

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
        .then(response => response.json())
        .then(data => {
            if (!data.success) throw new Error(data.data || 'Eroare la ștergere');
        });
    }

    loadOccupiedDates().then(() => {
        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            locale: 'ro',
            selectable: true,
            height: 'auto',
            buttonText: {
                today: 'Astăzi'
            },

            events: function(fetchInfo, successCallback) {
                const events = occupiedDates.map(dateStr => ({
                    title: 'Ocupat',
                    start: dateStr,
                    allDay: true,
                    color: 'red'
                }));
                successCallback(events);
            },

            select: function(info) {
                const startStr = formatDateToYYYYMMDD(info.start);
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

            dateClick: function(info) {
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

        document.getElementById('export-csv').addEventListener('click', function(e) {
            e.preventDefault();

            fetch(pensiuneAdminAjax.ajax_url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'pensiune_export_csv',
                    nonce: pensiuneAdminAjax.nonce
                })
            })
            .then(response => {
                const contentType = response.headers.get('Content-Type') || '';
                if (contentType.includes('application/json')) {
                    return response.json().then(data => {
                        if (!data.success) {
                            alert(data.data || 'Nu există date de exportat.');
                        }
                    });
                } else if (contentType.includes('text/csv')) {
                    return response.blob().then(blob => {
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = 'rezervari_pensiune.csv';
                        document.body.appendChild(a);
                        a.click();
                        a.remove();
                        window.URL.revokeObjectURL(url);
                    });
                } else {
                    alert('Răspuns necunoscut de la server.');
                }
            })
            .catch(() => {
                alert('Eroare la export.');
            });
        });
    });
});
