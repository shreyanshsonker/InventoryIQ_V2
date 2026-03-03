<?php
/**
 * InventoryIQ v2.0 — 500 Server Error Page
 */
http_response_code(500);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>500 Error — InventoryIQ</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest"></script>
  <link rel="stylesheet" href="/inventoryiq/css/style.css">
</head>
<body class="aurora-bg">

<div class="error-page">
  <div class="error-code text-gradient">500</div>
  <h2>Something Went Wrong</h2>
  <p style="color:var(--text-muted);max-width:400px;">An internal server error occurred. Please try again later or contact support.</p>
  <a href="/inventoryiq/login.php" class="btn btn-primary">
    <i data-lucide="arrow-left" style="width:18px;height:18px;"></i>
    Back to Login
  </a>
</div>

<script>if(typeof lucide!=='undefined')lucide.createIcons();</script>
</body>
</html>
