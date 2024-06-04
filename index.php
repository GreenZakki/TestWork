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

// Добавление нового сотрудника
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add'])) {
    $fio = $_POST['fio'];
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $jobs = $_POST['jobs'];

    $stmt = $conn->prepare("INSERT INTO employees (fio, dob, gender) VALUES (?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("sss", $fio, $dob, $gender);
        $stmt->execute();
        $employee_id = $stmt->insert_id;
        $stmt->close();

        // Добавление мест работы
        $stmt = $conn->prepare("INSERT INTO jobs (employee_id, job_title) VALUES (?, ?)");
        foreach ($jobs as $job) {
            if ($stmt) {
                $stmt->bind_param("is", $employee_id, $job);
                $stmt->execute();
            }
        }
        $stmt->close();

        // Перенаправление для предотвращения повторной отправки формы
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        echo "Error preparing statement: " . $conn->error;
    }
}

// Удаление сотрудника
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM employees WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        // Перенаправление для предотвращения повторного удаления
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        echo "Error preparing statement: " . $conn->error;
    }
}

// Получение данных сотрудников
$result = $conn->query("
    SELECT e.id, e.fio, e.dob, e.gender, GROUP_CONCAT(j.job_title SEPARATOR ', ') as jobs
    FROM employees e
    LEFT JOIN jobs j ON e.id = j.employee_id
    GROUP BY e.id
");
$employees = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Таблица данных сотрудника</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
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

<h2>Таблица данных сотрудника</h2>

<table>
    <tr>
        <th>ФИО</th>
        <th>Дата рождения</th>
        <th>Пол</th>
        <th>Места работы</th>
        <th>Действия</th>
    </tr>
    <?php foreach ($employees as $employee) : ?>
        <tr>
            <td><?php echo htmlspecialchars($employee['fio']); ?></td>
            <td><?php echo htmlspecialchars($employee['dob']); ?></td>
            <td><?php echo htmlspecialchars($employee['gender']); ?></td>
            <td><?php echo htmlspecialchars($employee['jobs']); ?></td>
            <td>
                <a href="edit.php?id=<?php echo $employee['id']; ?>">Редактировать</a>
                <a href="?delete=<?php echo $employee['id']; ?>">Удалить</a>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

<div class="form-container">
    <h3>Добавить сотрудника</h3>
    <form method="POST">
        <input type="hidden" name="add" value="1">
        <input type="text" name="fio" placeholder="ФИО" required>
        <input type="date" name="dob" required>
        <select name="gender" required>
            <option value="">Выберите пол</option>
            <option value="Мужской">Мужской</option>
            <option value="Женский">Женский</option>
        </select>
        <textarea name="jobs[]" placeholder="Место работы 1" required></textarea>
        <textarea name="jobs[]" placeholder="Место работы 2"></textarea>
        <textarea name="jobs[]" placeholder="Место работы 3"></textarea>
        <button type="submit">Добавить</button>
    </form>
</div>

</body>
</html>

<?php $conn->close(); ?>
