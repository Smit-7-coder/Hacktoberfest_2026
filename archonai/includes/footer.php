<?php
declare(strict_types=1);

/**
 * ArchonAI - Global Footer Component
 */
?>
<footer class="archon-footer">
    <div class="container px-lg-5 px-3">
        <div class="row g-4 align-items-center">
            <div class="col-md-6 text-center text-md-start">
                <div class="d-flex align-items-center justify-content-center justify-content-md-start gap-2 mb-2">
                    <span class="fw-bold text-light">ArchonAI</span>
                    <span class="badge-hacktober">Hacktoberfest 2026</span>
                </div>
                <p class="text-muted small mb-0">Intelligence for Software Architecture. Grounded repository exploration and dependency visualization.</p>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <div class="d-flex justify-content-center justify-content-md-end gap-3 mb-2">
                    <a href="https://github.com" target="_blank" rel="noopener" class="text-secondary hover-light"><i class="bi bi-github fs-5"></i></a>
                    <a href="<?= app_url('README.md') ?>" class="text-secondary small">Docs</a>
                    <a href="<?= app_url('CONTRIBUTING.md') ?>" class="text-secondary small">Contributing</a>
                    <a href="<?= app_url('test_db.php') ?>" class="text-secondary small">Status</a>
                </div>
                <div class="text-muted small">&copy; <?= date('Y') ?> ArchonAI Project. Open-source under MIT License.</div>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap 5 Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

<!-- Cytoscape.js for dependency graph visualization -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/cytoscape/3.28.1/cytoscape.min.js"></script>

<!-- ArchonAI App Script -->
<script src="<?= app_url('assets/js/app.js') ?>"></script>
</body>
</html>
