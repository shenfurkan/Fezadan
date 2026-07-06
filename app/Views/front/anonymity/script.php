<script nonce="<?= htmlspecialchars($nonce) ?>">
(function () {
    'use strict';

<?php require __DIR__ . '/scripts/context.php'; ?>
<?php require __DIR__ . '/scripts/helpers.php'; ?>
<?php require __DIR__ . '/scripts/map.php'; ?>
<?php require __DIR__ . '/scripts/modules/network.php'; ?>
<?php require __DIR__ . '/scripts/modules/fingerprint.php'; ?>
<?php require __DIR__ . '/scripts/modules/privacy.php'; ?>
<?php require __DIR__ . '/scripts/runner.php'; ?>

})();
</script>
