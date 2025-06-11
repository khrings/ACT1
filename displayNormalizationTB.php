<?php
include "connnector.php";

function splitData($data)
{
    return preg_split('/\s*,\s*/', trim($data));
}

function validateData($row)
{
    return [
        'candidate_id' => htmlspecialchars(trim($row['candidate_id'] ?? '')),
        'first_name' => htmlspecialchars(trim($row['first_name'] ?? '')),
        'last_name' => htmlspecialchars(trim($row['last_name'] ?? '')),
        'date_of_birth' => htmlspecialchars(trim($row['date_of_birth'] ?? '')),
        'gender' => htmlspecialchars(trim($row['gender'] ?? '')), // Provide a default value if 'gender' is not set
        'email' => htmlspecialchars(trim($row['email'] ?? '')),
        'phone_number' => htmlspecialchars(trim($row['phone_number'] ?? '')),
        'phone_id' => htmlspecialchars(trim($row['phone_id'] ?? ''))
    ];
}

$rawDataQuery = "SELECT * FROM candidates LEFT JOIN phone_numbers ON candidates.candidate_id = phone_numbers.candidate_id";
$rawDataStmt = $conn->prepare($rawDataQuery);
$rawDataStmt->execute();
$rawData = $rawDataStmt->fetchAll(PDO::FETCH_ASSOC);

$nf1Data = [];
$rowCount = 0;
foreach ($rawData as $row) {
    $columns = array_keys($row);
    $splitRows = [[]];
    foreach ($columns as $col) {
        $values = splitData($row[$col] ?? '');
        $newRows = [];
        foreach ($splitRows as $existingRow) {
            foreach ($values as $val) {
                $newRows[] = array_merge($existingRow, [$col => $val]);
                $rowCount++;
            }
        }
        $splitRows = $newRows;
    }
    foreach ($splitRows as $splitRow) {
        $nf1Data[] = validateData(array_merge($row, $splitRow));
    }
}
// Fetch data for 2NF
$nf2Query = "SELECT candidates.candidate_id, candidates.first_name, candidates.last_name, candidates.date_of_birth, candidates.gender, candidates.email, phone_numbers.phone_number 
             FROM candidates 
             INNER JOIN phone_numbers ON candidates.candidate_id = phone_numbers.candidate_id";
$nf2Stmt = $conn->prepare($nf2Query);
$nf2Stmt->execute();
$nf2Data = array_map('validateData', $nf2Stmt->fetchAll(PDO::FETCH_ASSOC));

// Initialize arrays for 2NF tables
$nf2Table1 = []; // Row ID, Candidate ID, First Name, Last Name
$nf2Table2 = []; // Row ID, Last Name
$nf2Table3 = []; // Row ID, Date of Birth
$nf2Table4 = []; // Gender ID, Gender
$nf2Table5 = []; // Row ID, Email, Phone Number
$nf2Table6 = []; // Row ID, Phone Number

// Initialize row counters for each table
$rowCounter1 = 1;
$rowCounter2 = 1;
$rowCounter3 = 1;
$rowCounter4 = 1;
$rowCounter5 = 1;
$rowCounter6 = 1;

// Initialize a unique list of genders
$uniqueGenders = [];
$genderIdCounter = 1;

foreach ($nf2Data as $row) {
    // Split values by commas
    $firstNames = splitData($row['first_name']);
    $lastNames = splitData($row['last_name']);
    $datesOfBirth = splitData($row['date_of_birth']);
    $genders = splitData($row['gender']);
    $emails = splitData($row['email']);
    $phoneNumbers = splitData($row['phone_number']);

    // Create a new row for each split value
    foreach ($firstNames as $firstName) {
        foreach ($lastNames as $lastName) { // Include last names in the loop
            $nf2Table1[] = [
                'row_id' => $rowCounter1++,
                'candidate_id' => $row['candidate_id'],
                'first_name' => $firstName,
                'last_name' => $lastName // Add last name to the table
            ];
        }
    }

    foreach ($lastNames as $lastName) {
        $nf2Table2[] = [
            'row_id' => $rowCounter2++,
            'candidate_id' => $row['candidate_id'],
            'last_name' => $lastName
        ];
    }

    foreach ($datesOfBirth as $dateOfBirth) {
        $nf2Table3[] = [
            'row_id' => $rowCounter3++,
            'candidate_id' => $row['candidate_id'],
            'date_of_birth' => $dateOfBirth
        ];
    }

    // Handle genders uniquely
    foreach ($genders as $gender) {
        if (!isset($uniqueGenders[$gender])) {
            $uniqueGenders[$gender] = $genderIdCounter++;
            $nf2Table4[] = [
                'gender_id' => $uniqueGenders[$gender],
                'gender' => $gender
            ];
        }
    }

    foreach ($emails as $email) {
        foreach ($phoneNumbers as $phoneNumber) { // Include phone numbers in the loop
            if (!empty($row['candidate_id'])) { // Ensure candidate_id is not null
                $nf2Table5[] = [
                    'row_id' => $rowCounter5++,
                    'candidate_id' => $row['candidate_id'],
                    'gender_id' => $uniqueGenders[$row['gender']] ?? null, // Add gender_id to the table
                    'email' => $email,
                    'phone_number' => $phoneNumber
                ];
            }
        }
    }

    foreach ($phoneNumbers as $phoneNumber) {
        $nf2Table6[] = [
            'row_id' => $rowCounter6++,
            'candidate_id' => $row['candidate_id'],
            'phone_number' => $phoneNumber
        ];
    }
}

$nf3Query = "SELECT candidates.candidate_id, candidates.first_name, candidates.last_name, candidates.date_of_birth, candidates.gender, phone_numbers.phone_id, phone_numbers.phone_number 
             FROM candidates 
             INNER JOIN phone_numbers ON candidates.candidate_id = phone_numbers.candidate_id";
$nf3Stmt = $conn->prepare($nf3Query);
$nf3Stmt->execute();
$nf3Data = array_map('validateData', $nf3Stmt->fetchAll(PDO::FETCH_ASSOC));

$nf3Table1 = []; // Candidate ID, Phone ID, First Name, Last Name, Gender ID
$nf3Table2 = []; // Candidate ID, Last Name
$nf3Table3 = []; // Candidate ID, Date of Birth
$nf3Table4 = []; // Gender ID, Gender
$nf3Table5 = []; // Candidate ID, Phone Number

$rowCounter1 = 1;
$rowCounter2 = 1;
$rowCounter3 = 1;
$rowCounter4 = 1;
$rowCounter5 = 1;

// Initialize a unique list of genders
$uniqueGenders = [];
$genderIdCounter = 1;

foreach ($nf3Data as $row) {
    // Split values by commas
    $firstNames = splitData($row['first_name']);
    $lastNames = splitData($row['last_name']);
    $datesOfBirth = splitData($row['date_of_birth']);
    $genders = splitData($row['gender']);
    $phoneNumbers = splitData($row['phone_number']);

    // Handle genders uniquely
    foreach ($genders as $gender) {
        if (!isset($uniqueGenders[$gender])) {
            $uniqueGenders[$gender] = $genderIdCounter++;
            $nf3Table4[] = [
                'gender_id' => $uniqueGenders[$gender],
                'gender' => $gender
            ];
        }
    }

    // Create a new row for each split value
    foreach ($firstNames as $firstName) {
        foreach ($lastNames as $lastName) { // Include last names in the loop
            $nf3Table1[] = [
                'row_id' => $rowCounter1++,
                'candidate_id' => $row['candidate_id'],
                'phone_id' => $row['phone_id'],
                'first_name' => $firstName,
                'last_name' => $lastName, // Add last name to the table
                'gender_id' => $uniqueGenders[$row['gender']] ?? null
            ];
        }
    }

    foreach ($lastNames as $lastName) {
        $nf3Table2[] = [
            'row_id' => $rowCounter2++,
            'candidate_id' => $row['candidate_id'],
            'last_name' => $lastName
        ];
    }

    foreach ($datesOfBirth as $dateOfBirth) {
        $nf3Table3[] = [
            'row_id' => $rowCounter3++,
            'candidate_id' => $row['candidate_id'],
            'date_of_birth' => $dateOfBirth
        ];
    }

    foreach ($phoneNumbers as $phoneNumber) {
        $nf3Table5[] = [
            'row_id' => $rowCounter5++,
            'candidate_id' => $row['candidate_id'],
            'phone_number' => $phoneNumber
        ];
    }
}

$nf3CandidatesTable = [];
$nf3PhonesTable = [];
$nf3GendersTable = [];
$nf3CandidateGenderTable = [];
$nf3DateOfBirthTable = [];

// Initialize unique lists for phones and genders
$uniquePhones = [];
$uniqueGenders = [];
$genderIdCounter = 1;
$phoneIdCounter = 1;

foreach ($nf3Data as $row) {
    // Populate Candidates Table
    $nf3CandidatesTable[$row['candidate_id']] = [
        'candidate_id' => $row['candidate_id'],
        'first_name' => $row['first_name'],
        'last_name' => $row['last_name']
    ];

    // Populate Phones Table
    if (!isset($uniquePhones[$row['phone_number']])) {
        $uniquePhones[$row['phone_number']] = $phoneIdCounter++;
        $nf3PhonesTable[] = [
            'phone_id' => $uniquePhones[$row['phone_number']],
            'candidate_id' => $row['candidate_id'],
            'phone_number' => $row['phone_number']
        ];
    }

    // Populate Genders Table
    if (!isset($uniqueGenders[$row['gender']])) {
        $uniqueGenders[$row['gender']] = $genderIdCounter++;
        $nf3GendersTable[] = [
            'gender_id' => $uniqueGenders[$row['gender']],
            'gender' => $row['gender']
        ];
    }

    // Populate Candidate_Gender Table
    $nf3CandidateGenderTable[] = [
        'candidate_id' => $row['candidate_id'],
        'gender_id' => $uniqueGenders[$row['gender']]
    ];

    // Populate Date of Birth Table
    $nf3DateOfBirthTable[$row['candidate_id']] = [
        'candidate_id' => $row['candidate_id'],
        'date_of_birth' => $row['date_of_birth']
    ];
}

// Ensure unique values for Candidates and Date of Birth tables
$nf3CandidatesTable = array_values($nf3CandidatesTable);
$nf3DateOfBirthTable = array_values($nf3DateOfBirthTable);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Normalization Tables</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">css2?family=Dosis:wght@300;500;700&display=swap');

        table {
            margin-left: 20%;
            width: 65%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        h2, h3 {
            text-align: center;
        }

        th,
        td {
            border: 1px solid white;
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #ff9800;
            color: rgb(49, 30, 1);
        }

        body {
            font-family: 'Dosis', sans-serif;
            background-color: #121212;
            color: white;
            padding: 20px;
            font-size: 12px;
        }

        button {
            width: 10%;
            padding: 10px;
            margin: 10px 5px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            background: rgb(255, 242, 0);
            color: black;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }

        button:hover {
            background: #e68900;
        }

        .table-container {
            display: flex;
            justify-content: space-between;
        }

        .hidden {
            display: none;
        }
    </style>
    <script>
        function showTable(tableId) {
            // Hide all tables
            document.getElementById('rawDataTable').classList.add('hidden');
            document.getElementById('nf1Table').classList.add('hidden');
            document.getElementById('nf2Table').classList.add('hidden');
            document.getElementById('nf3Table').classList.add('hidden');

            // Show the selected table
            document.getElementById(tableId).classList.remove('hidden');
        }
    </script>
</head>

<body>
    <button class="nav-button" onclick="window.location.href='userform.php'">
        <i class="fas fa-envelope"></i> Submit another form
    </button>
    <div style="text-align: center; margin-bottom: 20px;">
        <button onclick="showTable('rawDataTable')">Raw Data</button>
        <button onclick="showTable('nf1Table')">1NF</button>
        <button onclick="showTable('nf2Table')">2NF</button>
        <button onclick="showTable('nf3Table')">3NF</button>
    </div>

    <div id="rawDataTable">
        <h2>Raw Data (Before Normalization)</h2>
        <table>
            <tr>
                <th>Candidate_ID</th>
                <th>First Name</th>
                <th>Last Name</th>
                <th>DOB</th>
                <th>Gender</th>
                <th>Email</th>
                <th>Phone Number</th>
            </tr>
            <?php foreach ($rawData as $row) { ?>
                <tr>
                    <td><?= $row['candidate_id'] ?></td>
                    <td><?= $row['first_name'] ?></td>
                    <td><?= $row['last_name'] ?></td>
                    <td><?= $row['date_of_birth'] ?></td>
                    <td><?= $row['gender'] ?></td>
                    <td><?= $row['email'] ?></td>
                    <td><?= $row['phone_number'] ?></td>
                </tr>
            <?php } ?>
        </table>
    </div>

    <div id="nf1Table" class="hidden">
        <h2>First Normal Form (1NF)</h2>
        <table>
            <tr>
                <th>Candidate_ID</th>
                <th>First Name</th>
                <th>Last Name</th>
                <th>DOB</th>
                <th>Gender</th>
                <th>Email</th>
                <th>Phone Number</th>
            </tr>
            <?php foreach ($nf1Data as $row) { ?>
                <tr>
                    <td><?= $row['candidate_id'] ?></td>
                    <td><?= $row['first_name'] ?></td>
                    <td><?= $row['last_name'] ?></td>
                    <td><?= $row['date_of_birth'] ?></td>
                    <td><?= $row['gender'] ?></td>
                    <td><?= $row['email'] ?></td>
                    <td><?= $row['phone_number'] ?></td>
                </tr>
            <?php } ?>
        </table>
    </div>

    <div id="nf2Table" class="hidden">
        <h2>Second Normal Form (2NF)</h2>
        <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 20px;">
            <!-- Table 1: Candidate ID, First Name, Last Name -->
            <div style="flex: 1 1 30%;">
                <table style="width: 50%;">
                    <tr>
                        <th>Candidate ID</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                    </tr>
                    <?php foreach ($nf2Table1 as $row) { ?>
                        <tr>
                            <td><?= $row['candidate_id'] ?></td>
                            <td><?= $row['first_name'] ?></td>
                            <td><?= $row['last_name'] ?></td>
                        </tr>
                    <?php } ?>
                </table>
            </div>
    
            <!-- Table 3: Candidate ID, Date of Birth -->
            <div style="flex: 1 1 30%;">
                <table style="width:50%;">
                    <tr>
                        <th>Candidate ID</th>
                        <th>Date of Birth</th>
                    </tr>
                    <?php foreach ($nf2Table3 as $row) { ?>
                        <tr>
                            <td><?= $row['candidate_id'] ?></td>
                            <td><?= $row['date_of_birth'] ?></td>
                        </tr>
                    <?php } ?>
                </table>
            </div>
            <!-- Table 4: Gender ID, Gender -->
            <div style="flex: 1 1 30%;">
                <table style="width: 50%;">
                    <tr>
                        <th>Gender ID</th>
                        <th>Gender</th>
                    </tr>
                    <?php foreach ($nf2Table4 as $row) { ?>
                        <tr>
                            <td><?= $row['gender_id'] ?></td>
                            <td><?= $row['gender'] ?></td>
                        </tr>
                    <?php } ?>
                </table>
            </div>
            <!-- Table 5: Candidate ID, Email, Phone Number -->
            <div style="flex: 1 1 30%;">
                <table style="width: 50%;">
                    <tr>
                        <th>Candidate ID</th>
                        <th>Email</th>
                        <th>Phone Number</th>
                    </tr>
                    <?php foreach ($nf2Table5 as $row) { ?>
                        <tr>
                            <td><?= $row['candidate_id'] ?></td>
                            <td><?= $row['email'] ?></td>
                            <td><?= $row['phone_number'] ?></td>
                        </tr>
                    <?php } ?>
                </table>
            </div>
        </div>
    </div>

    <div id="nf3Table" class="hidden">
        <h2>Third Normal Form (3NF)</h2>
        <h3 style="text-align: center;">Candidates Table (3NF)</h3>
        <div style="display: flex; justify-content: center; margin-bottom: 20px; align-items: center;">
            <table style="width: 50%; text-align: center; margin: 0 auto;">
                <tr>
                    <th>Candidate ID</th>
                    <th>First Name</th>
                    <th>Last Name</th>
                </tr>
                <?php foreach ($nf3CandidatesTable as $row) { ?>
                    <tr>
                        <td><?= $row['candidate_id'] ?></td>
                        <td><?= $row['first_name'] ?></td>
                        <td><?= $row['last_name'] ?></td>
                    </tr>
                <?php } ?>
            </table>
        </div>
        <h3 style="text-align: center;">Phones Table (3NF)</h3>
        <div style="display: flex; justify-content: center; margin-bottom: 20px; align-items: center;">
            <table style="width: 50%; text-align: center; margin: 0 auto;">
                <tr>
                    <th>Phone ID</th>
                    <th>Candidate ID</th>
                    <th>Phone Number</th>
                </tr>
                <?php foreach ($nf3PhonesTable as $row) { ?>
                    <tr>
                        <td><?= $row['phone_id'] ?></td>
                        <td><?= $row['candidate_id'] ?></td>
                        <td><?= $row['phone_number'] ?></td>
                    </tr>
                <?php } ?>
            </table>
        </div>
        <h3 style="text-align: center;">Genders Table (3NF)</h3>
        <div style="display: flex; justify-content: center; margin-bottom: 20px; align-items: center;">
            <table style="width: 50%; text-align: center; margin: 0 auto;">
                <tr>
                    <th>Gender ID</th>
                    <th>Gender</th>
                </tr>
                <?php 
                $uniqueGenders = []; // Track unique genders
                foreach ($nf3GendersTable as $row) { 
                    $genders = splitData($row['gender']); // Split comma-separated values
                    foreach ($genders as $gender) {
                        if (!in_array($gender, $uniqueGenders)) { // Ensure no duplicate gender values
                            $uniqueGenders[] = $gender;

                            // Assign specific gender_id based on gender value
                            $genderId = 0; // Default ID
                            if (strtolower($gender) === 'male') {
                                $genderId = 1;
                            } elseif (strtolower($gender) === 'female') {
                                $genderId = 2;
                            } elseif (strtolower($gender) === 'lgbtq') {
                                $genderId = 3;
                            } ?>
                            <tr>
                                <td><?= htmlspecialchars($genderId) ?></td>
                                <td><?= htmlspecialchars($gender) ?></td>
                            </tr>
                        <?php } ?>
                    <?php } ?>
                <?php } ?>
            </table>
        </div>

        <h3 style="text-align: center;">Date of Birth Table (3NF)</h3>
        <div style="display: flex; justify-content: center; margin-bottom: 20px; align-items: center;">
            <table style="width: 50%; text-align: center; margin: 0 auto;">
                <tr>
                    <th>Candidate ID</th>
                    <th>Date of Birth</th>
                </tr>
                <?php foreach ($nf3DateOfBirthTable as $row) { ?>
                    <tr>
                        <td><?= $row['candidate_id'] ?></td>
                        <td><?= $row['date_of_birth'] ?></td>
                    </tr>
                <?php } ?>
            </table>
        </div>
    </div>
</body>

</html>