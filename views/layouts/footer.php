            </div>
        </section>
    </div>

</div>

<!-- jQuery -->
<script src="<?= BOWER_URL ?>jquery/dist/jquery.min.js"></script>

<!-- Bootstrap 4 -->
<script src="<?= BOWER_URL ?>bootstrap4/dist/js/bootstrap.bundle.min.js"></script>

<!-- SlimScroll (sidebar) -->
<script src="<?= BOWER_URL ?>jquery-slimscroll/jquery.slimscroll.min.js"></script>

<!-- AdminLTE -->
<script src="<?= ADMINLTE_URL ?>js/adminlte.min.js"></script>

<!-- JS propio -->
<script>
    var APP_URL = <?= json_encode(BASE_URL) ?>;
</script>
<script src="<?= ASSETS_URL ?>js/app.js?v=<?= APP_VERSION ?>"></script>

</body>
</html>
