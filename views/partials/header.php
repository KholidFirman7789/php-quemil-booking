<?php
/**
 * Partial: Header HTML
 * @var string $pageTitle
 * @var string $extraCss (opsional)
 */
?>
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle ?? 'Beranda') ?> | <?= APP_NAME ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= baseUrl('assets/css/style.css') ?>">
  <!-- AOS - Animate On Scroll -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css">
  <?php if (isset($extraCss)) echo $extraCss; ?>
  <!-- Prevent dark mode flash -->
  <script>
    (function(){
      var t = localStorage.getItem('qm_theme') || 'light';
      document.documentElement.setAttribute('data-theme', t);
    })();
  </script>
</head>
<body>
