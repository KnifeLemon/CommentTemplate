<div class="card">
    <div class="card-header">
        <h5>User Information</h5>
    </div>
    <div class="card-body">
        <?php if (isset($isLoggedIn) && $isLoggedIn): ?>
        <p><strong>Name:</strong> {$user.name|escape}</p>
        <p><strong>Email:</strong> {$user.email|escape}</p>
        <p><strong>Status:</strong> <span class="badge bg-success">Online</span></p>
        <?php else: ?>
        <p>Please log in to see your information.</p>
        <a href="#" class="btn btn-primary">Login</a>
        <?php endif; ?>
    </div>
</div>

<div class="card mt-3">
    <div class="card-header">
        <h5>Quick Links</h5>
    </div>
    <div class="card-body">
        <ul class="list-unstyled">
            <li><a href="#" class="text-decoration-none">Documentation</a></li>
            <li><a href="#" class="text-decoration-none">Examples</a></li>
            <li><a href="#" class="text-decoration-none">GitHub Repository</a></li>
            <li><a href="#" class="text-decoration-none">Support</a></li>
        </ul>
    </div>
</div>