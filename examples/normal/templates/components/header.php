<header class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="#">
            <!--@base64(images/logo.png)-->
            CommentTemplate
        </a>
        
        <?php if (isset($isLoggedIn) && $isLoggedIn): ?>
        <div class="navbar-nav ms-auto">
            <span class="navbar-text">
                Welcome, {$user.name|escape|default=Guest}!
            </span>
        </div>
        <?php endif; ?>
    </div>
</header>