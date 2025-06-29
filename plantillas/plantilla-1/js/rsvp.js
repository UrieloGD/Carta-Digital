function openRSVPModal() {
    document.getElementById('rsvpModal').style.display = 'flex';
}

function closeRSVPModal() {
    document.getElementById('rsvpModal').style.display = 'none';
}

function initRSVP() {
    // Manejar envío de RSVP
    document.getElementById('rsvpForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const data = Object.fromEntries(formData);
        
        fetch('./api/rsvp.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                closeRSVPModal();
                document.getElementById('successMessage').style.display = 'flex';
                setTimeout(() => {
                    document.getElementById('successMessage').style.display = 'none';
                }, 3000);
            } else {
                alert('Error al enviar la confirmación. Por favor intenta de nuevo.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al enviar la confirmación. Por favor intenta de nuevo.');
        });
    });
}