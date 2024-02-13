<?php

// Подключение к базе данных
$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "task";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Получение списка аптек из базы данных
$sql = "SELECT pharmacy_id, pharmacy_code FROM pharmacies";
$result = $conn->query($sql);

// Проверка наличия аптек
if ($result->num_rows > 0) {
    // Аптеки найдены, используем данные для создания списка
    $pharmacies = $result->fetch_all(MYSQLI_ASSOC);
} else {
    // Аптеки не найдены
    $pharmacies = array();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Task</title>
    <!-- Add Bootstrap CDN links -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
            <div class="position-sticky">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">
                            List of Pharmacies
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="upload.php">
                            Upload ZIP file
                        </a>
                    </li>
                    <!-- Add more sidebar items as needed -->
                </ul>
            </div>
        </nav>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">List of Pharmacies</h1>
            </div>

            <!-- Right-side content, list of pharmacies -->
            <ul class="list-group">
                <?php foreach ($pharmacies as $pharmacy): ?>
                    <li class="list-group-item"><a href="detail.php?id=<?= $pharmacy['pharmacy_id'] ?>"> <?= $pharmacy['pharmacy_code'] ?></a></li>
                <?php endforeach; ?>
                <!-- Add more pharmacies as needed -->
            </ul>
        </main>
    </div>
</div>

</body>
</html>
