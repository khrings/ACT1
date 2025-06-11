<?php
include "connnector.php";

class Candidate
{
    public $candidate_id;
    public $first_name;
    public $last_name;
    public $date_of_birth;
    public $gender;
    public $email;
    public $registration_date;

    function __construct($id, $first, $last, $dob, $gender, $email, $reg_date)
    {
        $this->candidate_id = $id;
        $this->first_name = $first;
        $this->last_name = $last;
        $this->date_of_birth = $dob;
        $this->gender = $gender;
        $this->email = $email;
        $this->registration_date = $reg_date;
    }
}

class PhoneNumber
{
    public $phone_id;
    public $candidate_id;
    public $phone_number;

    function __construct($phone_id, $candidate_id, $phone_number)
    {
        $this->phone_id = $phone_id;
        $this->candidate_id = $candidate_id;
        $this->phone_number = $phone_number;
    }
}

$result = ""; // Initialize result variable
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = htmlspecialchars($_POST['first_name']);
    $last_name = htmlspecialchars($_POST['last_name']);
    $email = htmlspecialchars($_POST['email']);
    $phone_number = htmlspecialchars($_POST['phone_number']);
    $registration_date = date("Y-m-d H:i:s");

    // Process gender as a single input
    $gender = htmlspecialchars($_POST['gender']);

    // Process date_of_birth as an array
    $date_of_birth = isset($_POST['date_of_birth']) && is_array($_POST['date_of_birth']) 
        ? implode(", ", $_POST['date_of_birth']) 
        : '';
    
    try {
        // Insert candidate data
        $stmt = $conn->prepare("INSERT INTO candidates (first_name, last_name, date_of_birth, gender, email, registration_date) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$first_name, $last_name, $date_of_birth, $gender, $email, $registration_date]);
        $candidate_id = $conn->lastInsertId();

        // Insert phone number
        $stmt = $conn->prepare("INSERT INTO phone_numbers (candidate_id, phone_number) VALUES (?, ?)");
        $stmt->execute([$candidate_id, $phone_number]);

        // Success message using SweetAlert
        $result = "<script>
            Swal.fire({
                title: 'Success!',
                text: 'Candidate registered successfully. Candidate ID: $candidate_id',
                icon: 'success'
            });
        </script>";
    } catch (PDOException $e) {
        // Error message using SweetAlert
        $result = "<script>
            Swal.fire({
                title: 'Error!',
                text: 'Database Error: " . addslashes($e->getMessage()) . "',
                icon: 'error'
            });
        </script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidate Registration</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600;700&display=swap');

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #121212;
            color: rgb(230, 144, 15);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .container {
            background: #1e1e1e;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0px 0px 15px rgba(255, 255, 255, 0.1);
            width: 400px;
            text-align: center;
        }

        h2 {
            margin-bottom: 20px;
        }

        .form-group {
            display: flex;
            gap: 10px;
        }

        .form-group input {
            flex: 1;
        }

        input,
        select,
        button {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: none;
            border-radius: 5px;
            font-size: 16px;
        }

        input,
        select {
            background: #252525;
            color: white;
        }

        button {
            background: #ff9800;
            color: white;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }

        button:hover {
            background: #e68900;
        }

        .gender-group {
            display: flex;
            gap: 10px;
            margin: 10px 0;
        }

        .gender-group label {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .dob-input {
            display: flex;
            gap: 10px;
            margin-bottom: 5px;
        }

        .remove-btn {
            background-color: red;
            color: white;
            border: none;
            padding: 5px;
            cursor: pointer;
            border-radius: 5px;
        }

        .remove-btn:hover {
            background-color: darkred;
        }

        img {
            margin-left: 10px;
            z-index: -1;
            filter: drop-shadow(4px 4px 26px rgba(226, 155, 22, 0.92));

        }
    </style>
</head>

<body>
    <img src="R-removebg-preview.png" alt="logo">
    <div class="container">
        <?php echo $result; ?> <!-- Display SweetAlert message -->
        <form method="POST" autocomplete="off">
            <h2>Candidate Registration Form</h2>
            <label for="">Personal Details:</label><br>
            <div class="form-group">
                <input type="text" name="first_name" placeholder="First name" required>
                <input type="text" name="last_name" placeholder="Last name" required>
            </div>
            <div class="form-group">
                <input type="text" name="email" placeholder="Email" required>
                <input type="text" name="phone_number" placeholder="Phone Number" required>
            </div>

            <label for="">Date of Birth (Select Multiple):</label>
            <div id="dob-container">
                <div class="dob-input">
                    <input type="date" name="date_of_birth[]" required>
                    <button type="button" class="remove-btn" onclick="removeDateField(this)">Remove</button>
                </div>
            </div>
            <button type="button" onclick="addDateField()">+ Add More Dates</button>

            <label for="">Gender:</label>
            <div class="form-group">
                <input type="text" name="gender" placeholder="Enter Gender" required>
            </div>

            <input type="submit" value="Register">
            <button class="nav-button" onclick="window.location.href='displayNormalizationTB.php'">Normalization Display Table</button>
        </form>
    </div>
    <script>
        function addDateField() {
            let container = document.getElementById('dob-container');
            let div = document.createElement('div');
            div.classList.add('dob-input');
            div.innerHTML = `
        <input type="date" name="date_of_birth[]" required>
        <button type="button" class="remove-btn" onclick="removeDateField(this)">Remove</button>
    `;
            container.appendChild(div);
        }

        function removeDateField(button) {
            button.parentElement.remove();
        }
    </script>

</body>

</html>