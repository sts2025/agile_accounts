<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Broadcast Message</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1>Send a New Broadcast Message</h1>
            <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">Back to Dashboard</a>
        </div>

        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('admin.broadcasts.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label for="title" class="form-label">Title / Subject</label>
                        <input type="text" class="form-control" name="title" id="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="body" class="form-label">Message Body</label>
                        <textarea class="form-control" name="body" id="body" rows="8" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Send Message to All Managers</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>