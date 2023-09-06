<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="assets/images/logo_32.png" type="image/png">
    <title><?= APP_NAME ?></title>
    <!-- google fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Titillium+Web:wght@300;600&display=swap" rel="stylesheet">
    <!-- bootstrap -->
    <link rel="stylesheet" href="assets/bootstrap/bootstrap.min.css">
    <!-- fontawesome -->
    <link rel="stylesheet" href="assets/fontawesome/all.min.css">
    <!-- custom css -->
    <link rel="stylesheet" href="assets/app.css">

    <?php if (isset($flatpickr)) : ?>
        <!-- Flatpickr -->
        <link rel="stylesheet" href="assets/flatpickr/flatpickr.min.css">
        <script src="assets/flatpickr/flatpickr.js"></script>
    <?php endif; ?>

    <!-- Jquery -->
    <script src="assets/jquery/jquery-3.7.0.min.js"></script>

    <!-- Datatables -->
    <script src="assets/datatables/datatables.min.js"></script>
    <link rel="stylesheet" href="assets/datatables/datatables.min.css">
    
    <!-- Chart JS -->
    <?php if (isset($chartJs)): ?>
        <script src="assets/chartjs/chart.js"></script>
        <!-- <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> -->
    <?php endif; ?>
</head>

<body>