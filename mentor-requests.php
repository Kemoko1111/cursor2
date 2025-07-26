    <script>
        async function respondRequest(requestId, action) {
            if (!confirm('Are you sure you want to ' + action + ' this request?')) return;
            try {
                const response = await fetch('/api/mentor/respond-request.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ request_id: requestId, action: action })
                });
                const result = await response.json();
                if (result.success) {
                    MenteegoApp.showAlert(result.message, 'success');
                    setTimeout(() => { window.location.reload(); }, 1500);
                } else {
                    MenteegoApp.showAlert(result.message || 'Failed to process request', 'danger');
                }
            } catch (error) {
                console.error('Error:', error);
                MenteegoApp.showAlert('Network error: ' + error.message, 'danger');
            }
        }
    </script>