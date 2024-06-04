<?php
// Подключение к базе данных
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "company";

$conn = new mysqli($servername, $username, $password, $dbname);

// Проверка соединения
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_GET['id'])) {
    echo "Неверный запрос.";
    exit;
}

$id = $_GET['id'];

$result = $conn->query("SELECT * FROM employees WHERE id = $id");

if ($result->num_rows == 0) {
    echo "Сотрудник не найден.";
    exit;
}

$employee = $result->fetch_assoc();

// Получение мест работы сотрудника
$jobs_result = $conn->query("SELECT job_title FROM jobs WHERE employee_id = $id");
$jobs = $jobs_result->fetch_all(MYSQLI_ASSOC);

// Редактирование существующего сотрудника
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit'])) {
    $fio = $_POST['fio'];
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $new_jobs = $_POST['jobs'];

    $stmt = $conn->prepare("UPDATE employees SET fio = ?, dob = ?, gender = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("sssi", $fio, $dob, $gender, $id);
        $stmt->execute();
        $stmt->close();

        // Удаление старых мест работы
        $conn->query("DELETE FROM jobs WHERE employee_id = $id");

        // Добавление новых мест работы
        $stmt = $conn->prepare("INSERT INTO jobs (employee_id, job_title) VALUES (?, ?)");
        foreach ($new_jobs as $job) {
            if (!empty($job)) {
                if ($stmt) {
                    $stmt->bind_param("is", $id, $job);
                    $stmt->execute();
                }
            }
        }
        $stmt->close();

        // Перенаправление обратно на главную страницу
        header("Location: index.php");
        exit;
    } else {
        echo "Error preparing statement: " . $conn->error;
    }
}

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактировать сотрудника</title>
    <style>
        .form-container {
            margin: 20px 0;
        }
        .form-container form {
            display: flex;
            flex-direction: column;
            width: 300px;
        }
        .form-container form input, .form-container form select, .form-container form textarea {
            margin-bottom: 10px;
            padding: 8px;
        }
    </style>
</head>
<body>

<h2>Редактировать сотрудника</h2>

<div class="form-container">
    <form method="POST">
        <input type="hidden" name="edit" value="1">
        <input type="hidden" name="id" value="<?php echo $employee['id']; ?>">
        <input type="text" name="fio" placeholder="ФИО" value="<?php echo htmlspecialchars($employee['fio']); ?>" required>
        <input type="date" name="dob" value="<?php echo htmlspecialchars($employee['dob']); ?>" required>
        <select name="gender" required>
            <option value="Мужской" <?php echo $employee['gender'] == 'Мужской' ? 'selected' : ''; ?>>Мужской</option>
            <option value="Женский" <?php echo $employee['gender'] == 'Женский' ? 'selected' : ''; ?>>Женский</option>
        </select>
        <?php foreach ($jobs as $job): ?>
            <textarea name="jobs[]" required><?php echo htmlspecialchars($job['job_title']); ?></textarea>
        <?php endforeach; ?>
        <textarea name="jobs[]" placeholder="Новое место работы"></textarea>
        <textarea name="jobs[]" placeholder="Новое место работы"></textarea>
        <button type="submit">Сохранить</button>
    </form>
</div>

</body>
</html>

<?php $conn->close(); ?>
