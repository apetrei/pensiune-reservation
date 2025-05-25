document.addEventListener('DOMContentLoaded', function () {
    const calendarEl = document.getElementById('pensiune-public-calendar');
    if (!calendarEl) return;

    fetch(pensiunePublicAjax.ajax_url, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            action: 'pensiune_get_occupied_dates',
            nonce: pensiunePublicAjax.nonce
        })
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            calendarEl.innerHTML = '<p>Nu s-au putut încărca datele.</p>';
            return;
        }

        const occupiedDates = data.data;

        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            locale: 'ro',
            firstDay: 1,
            nowIndicator: true,
            selectable: false,
            height: 'auto',
            headerToolbar: {
                left: 'title',
                center: '',
                right: 'today prev,next'
            },
            buttonText: {
                today: 'Astăzi',
                month: 'Lună'
            },
            events: function(fetchInfo, successCallback, failureCallback) {
                const events = occupiedDates.map(dateStr => ({
                    title: 'Ocupat',
                    start: dateStr,
                    allDay: true,
                    color: 'red'
                }));
                successCallback(events);
            },
            dateClick: function() {
                // click dezactivat
            }
        });

        calendar.render();
    });
});
