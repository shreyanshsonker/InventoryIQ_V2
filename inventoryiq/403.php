<?php
/**
 * InventoryIQ v2.0 — 403 Forbidden Error Page
 */
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>403 Forbidden — InventoryIQ</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest"></script>
  <link rel="stylesheet" href="/inventoryiq/css/style.css">
</head>
<body class="aurora-bg">

<div class="error-page">
  <div class="error-code text-gradient">403</div>
  <h2>Access Denied</h2>
  <p style="color:var(--text-muted);max-width:400px;">You don't have permission to access this page. Contact your administrator if you believe this is an error.</p>
  <a href="/inventoryiq/dashboard/index.php" class="btn btn-primary">
    <i data-lucide="home" style="width:18px;height:18px;"></i>
    Go to Dashboard
  </a>
</div>

<script>if(typeof lucide!=='undefined')lucide.createIcons();</script>
</body>
</html>
