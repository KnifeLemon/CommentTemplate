<!--@layout(layout)-->

<div class="row">
    <div class="col-md-8">
        <div class="hero-section">
            <h1 class="display-4">{$title|escape}</h1>
            <p class="lead">{$description|escape}</p>
        </div>

        <div class="content-section">
            <h2>Features</h2>
            <ul class="list-group">
                <?php foreach ($items as $item): ?>
                <li class="list-group-item"><?= htmlspecialchars($item) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="mt-4">
            <h3>Content with Line Breaks</h3>
            <div class="card">
                <div class="card-body">
                    {$content|nl2br|escape}
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <!--@import(components/sidebar)-->
    </div>
</div>