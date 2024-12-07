<?php
include('../dbconn.php');
session_start();


// check if the session variable is set
if (!isset($_SESSION['schoolid'])) {
  // redirect the user to the login page
  header("Location: ../index.php");
  exit(); // stop further execution
}

// check if logout button is clicked
if (isset($_POST['logout'])) {
  // unset session variable
  unset($_SESSION['schoolid']);
  // destroy the session
  session_destroy();
  // redirect user to login page
  header("Location: ../index.php");
  exit();
}

$query = "SELECT * FROM systemsettings";
$result = mysqli_query($con, $query);

if ($result) {
    // Fetch the data from the result set
    $row = mysqli_fetch_assoc($result);

    // Assign values to variables
    $logo = $row['logo'];
    $name = $row['name'];
  
}


// // Function to generate a unique activation code
// function generateActivationCode($length = 10) {
//     $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
//     $code = '';
//     $max = strlen($characters) - 1;

//     for ($i = 0; $i < $length; $i++) {
//         $code .= $characters[rand(0, $max)];
//     }

//     return $code;
// }

// Function to generate a random password
function generateRandomPassword($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $password;
}

// // Function to send an email with credentials and activation link using cURL
// function sendActivationEmail($to, $password, $activation_code) {
//   $emailRecipient = $to;
//   $emailSubject = "Account Registration and Activation";
  
//   // Customize the email body as needed
//   $emailBody = "Thank you for registering!\n\nYour credentials are as follows:\nEmail: $to\nPassword: $password\n\nPlease keep this information secure.\n\nActivation Link: $activation_code";

//   // Use cURL to send the email
//   $url = "https://script.google.com/macros/s/AKfycbyRkHpW_F0KNnqYf4i6HFiCN-jHnTnJVJtF9MdWyJZqwmJZ3UMzO3sbkPKCtRMu4TuCuw/exec";
//   $ch = curl_init($url);
//   curl_setopt_array($ch, [
//       CURLOPT_RETURNTRANSFER => true,
//       CURLOPT_FOLLOWLOCATION => true,
//       CURLOPT_POSTFIELDS => http_build_query([
//           "recipient" => $emailRecipient,
//           "subject" => $emailSubject,
//           "body" => $emailBody,
//       ]),
//   ]);
//     // Execute cURL and close the connection
//     $response = curl_exec($ch);
//     curl_close($ch);

//     // Check if cURL request was successful
//     if ($response === false) {
//         echo '<script>alert("Error sending email")</script>';
//     }
// }

// Your database connection and other code...

if (isset($_POST['register'])) {
  // Retrieve and sanitize form data
  $schoolid = trim($_POST['schoolid']);
  $firstname = trim($_POST['firstname']);
  $lastname = trim($_POST['lastname']);
  $email = trim($_POST['email']);
  $department = trim($_POST['department']);
  $subDepartment = isset($_POST['subDepartment']) ? trim($_POST['subDepartment']) : '';
  $program = trim($_POST['program']);
  $major = isset($_POST['major']) ? trim($_POST['major']) : '';
  $section = trim($_POST['section']);
  $yearlvl = trim($_POST['yearlvl']);
  $user_status = trim($_POST['user_status']);
  $defaultImagePath = "../assets/img/user.jpg";
  $studentValue = 'Student';
  $userStatusValue = '7';

  // Generate a random password server-side
  $password = generateRandomPassword();

  // Check if the email is already taken using prepared statements to prevent SQL injection
  $check_email = $con->prepare("SELECT * FROM users WHERE email = ?");
  if ($check_email) {
      $check_email->bind_param("s", $email);
      $check_email->execute();
      $check_email->store_result();
  } else {
      // Handle error - prepare failed
      echo '<script>
          document.addEventListener("DOMContentLoaded", function() {
              Swal.fire({
                  icon: "error",
                  title: "Registration Failed",
                  text: "Could not prepare the email check statement."
              });
          });
      </script>';
      exit();
  }

  if ($check_email->num_rows > 0) {
      echo '<script>alert("The email is already taken")</script>';
  } else {
      // Hash the password securely using password_hash()
      $pass_enc = md5($password);

      // Prepare the INSERT statement without activation fields
      $register = $con->prepare("INSERT INTO users (schoolid, firstname, lastname, email, password, department, subDepartment, user_status, program, major, section, yearlvl, user_img, active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
      if ($register === false) {
          // Handle error - prepare failed
          echo '<script>
              document.addEventListener("DOMContentLoaded", function() {
                  Swal.fire({
                      icon: "error",
                      title: "Registration Failed",
                      text: "Could not prepare the registration statement."
                  });
              });
          </script>';
          exit();
      }

      $active = 1; // User is active since activation is removed

      // Bind parameters to the prepared statement
      $register->bind_param("sssssssssssssi", $schoolid, $firstname, $lastname, $email, $pass_enc, $department, $subDepartment, $userStatusValue, $program, $major, $section, $yearlvl, $defaultImagePath, $active);

      if ($register->execute()) {
          // Fetch the user's image path
          $query_user = $con->prepare("SELECT user_img FROM users WHERE email = ?");
          if ($query_user) {
              $query_user->bind_param("s", $email);
              $query_user->execute();
              $result_user = $query_user->get_result();
              $userData = $result_user->fetch_assoc();
              $imagePath = $userData['user_img'];
              $query_user->close();
          } else {
              // Handle error - prepare failed
              $imagePath = $defaultImagePath; // Fallback to default image
          }

          // Optional: Send credentials email
          // Uncomment the line below if you want to send an email with credentials
          // sendCredentialsEmail($email, $password);

          // Display the email and generated password to the admin with copy functionality
          echo '<script>
              document.addEventListener("DOMContentLoaded", function() {
                  Swal.fire({
                      title: "Registration Successful",
                      html:
                          `<p><strong>Email:</strong> ' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '</p>
                           <p><strong>Password:</strong> <input type="text" id="generatedPassword" value="' . htmlspecialchars($password, ENT_QUOTES, 'UTF-8') . '" readonly style="border: none; background: transparent; width: 100%;"></p>
                           <button id="copyPassword" class="btn btn-primary mt-2">Copy Password</button>
                           <hr>
                           <p><strong>User Image:</strong></p>
                           <img src="' . htmlspecialchars($imagePath, ENT_QUOTES, 'UTF-8') . '" alt="User Image" style="max-width: 100px; max-height: 100px;">`,
                      showConfirmButton: false,
                      didOpen: () => {
                          const copyButton = Swal.getPopup().querySelector("#copyPassword");
                          copyButton.addEventListener("click", () => {
                              const passwordField = Swal.getPopup().querySelector("#generatedPassword");
                              passwordField.select();
                              passwordField.setSelectionRange(0, 99999); // For mobile devices

                              navigator.clipboard.writeText(passwordField.value).then(() => {
                                  Swal.fire({
                                      icon: "success",
                                      title: "Copied!",
                                      text: "The password has been copied to your clipboard.",
                                      timer: 1500,
                                      showConfirmButton: false
                                  });
                              }).catch(() => {
                                  Swal.fire({
                                      icon: "error",
                                      title: "Error",
                                      text: "Failed to copy the password."
                                  });
                              });
                          });
                      }
                  }).then(function() {
                      window.location.href = "admin.php";
                  });
              });
          </script>';
      } else {
          // **Error Handling:** Display error message if registration fails
          echo '<script>
              document.addEventListener("DOMContentLoaded", function() {
                  Swal.fire({
                      icon: "error",
                      title: "Registration Failed",
                      text: "Please try again."
                  });
              });
          </script>';
      }
  }

  $check_email->close();
}









  $query = $con->query("SELECT * FROM users WHERE schoolid = '{$_SESSION['schoolid']}'");
  $userData = $query->fetch_assoc();
  $imagePath = $userData['user_img'];

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if the form was submitted
    if (isset($_POST['upload_button2'])) {
        // Process the uploaded CSV file here
        if ($_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
            $csv_file = $_FILES['csv_file']['tmp_name'];

            // Process the CSV file (example: read the data and perform operations)

            // For demonstration purposes, let's read and display the contents of the CSV file
            $file_contents = file_get_contents($csv_file);
            echo '<pre>' . $file_contents . '</pre>';
            exit;
        } else {
            echo '<script>alert("Error uploading the CSV file")</script>';
        }
    }
}


if ($_SERVER["REQUEST_METHOD"] === "POST") {
  if (isset($_POST['import'])) {
    // Disable the form to prevent multiple submissions
    echo "<script>document.getElementById('importForm').setAttribute('disabled', 'true');</script>";

    if ($_FILES['filename']['error'] == 0) {
      $file = $_FILES['filename']['tmp_name'];

      // Check if the uploaded file is a CSV file
      $ext = pathinfo($_FILES['filename']['name'], PATHINFO_EXTENSION);
      if (strtolower($ext) == 'csv') {
        $handle = fopen($file, "r");

        if ($handle !== false) {
          // Skip the header row
          $header = fgetcsv($handle);

          // Initialize a variable for debugging
          $debugOutput = "";
          $successCount = 0; // Variable to count successful records

          // Prepare a SQL statement
          $stmt = $con->prepare("INSERT INTO `users` (`schoolid`, `firstname`, `lastname`, `user_img`, `user_status`, `department`, `subDepartment`, `program`, `major`, `yearlvl`, `section`, `email`, `password`, `created_at`, `updated_at`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");

          if ($stmt) {
            // Bind variables to the prepared statement
            $stmt->bind_param("sssssssssssss", $schoolid, $firstname, $lastname, $user_img, $user_status, $department, $subDepartment, $program, $major, $yearlvl, $section, $email, $password);

            // Example: Process data from the CSV file
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {

              $defaultImagePath = "../assets/img/user.jpg"; // Define the default image path
              // Extract data from each column
              $schoolid = mysqli_real_escape_string($con, $data[1]);
              $firstname = mysqli_real_escape_string($con, $data[2]);
              $lastname = mysqli_real_escape_string($con, $data[3]);
              $user_img = !empty($data[4]) ? mysqli_real_escape_string($con, $data[4]) : $defaultImagePath; // Use default image path if $data[4] is empty
              $user_status = mysqli_real_escape_string($con, $data[5]);
              $department = mysqli_real_escape_string($con, $data[6]);
              $subDepartment = mysqli_real_escape_string($con, $data[7]);
              $program = mysqli_real_escape_string($con, $data[8]);
              $major = mysqli_real_escape_string($con, $data[9]);
              $yearlvl = mysqli_real_escape_string($con, $data[10]);
              $section = mysqli_real_escape_string($con, $data[11]);
              $email = mysqli_real_escape_string($con, $data[12]);
              $password = md5(mysqli_real_escape_string($con, $data[13])); // Use md5 to hash the password

              // Execute the prepared statement
              if ($stmt->execute()) {
                // Debugging: Record success message
                $debugOutput .= "Record inserted successfully.\n";
                $successCount++;
              } else {
                // Debugging: Record error message
                $debugOutput .= "Error inserting record: " . $stmt->error . "\n";
              }
            }

            $stmt->close();
          } else {
            // Debugging: Record error message
            $debugOutput .= "Error preparing statement: " . $con->error . "\n";
          }

          fclose($handle);

          // Display SweetAlert
          echo '<script>
          document.addEventListener("DOMContentLoaded", function() {
              Swal.fire({
                  position: "top-end",
                  icon: "success",
                  title: "Imported Successfully",
                  showConfirmButton: false,
                  timer: 1500
              }).then(function() {
                  window.location.href = "admin.php";
              });
          });
      </script>';
        } else {
          echo '<script>
          document.addEventListener("DOMContentLoaded", function() {
              Swal.fire({
                  icon: "error",
                  title: "Registration Failed",
                  text: "Please try again."
              });
          });
      </script>';
        }
      } else {
        echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            Swal.fire({
                icon: "error",
                title: "Uploaded file is not CSV",
                text: "Please try again."
            });
        });
    </script>';;
      }
    } else {
      echo '<script>
      document.addEventListener("DOMContentLoaded", function() {
          Swal.fire({
              icon: "error",
              title: "Error Uploading file",
              text: "Please try again."
          });
      });
  </script>';
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>PCZC Evaluation</title>
  <meta content="" name="description">
  <meta content="" name="keywords">

  <!-- Favicons -->
  <link href="<?php echo $logo; ?>" rel="icon">

  <!-- Google Fonts -->
  <link href="https://fonts.gstatic.com" rel="preconnect">
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">

  <!-- bootstraps CSS Files -->
  <link href="../assets/bootstraps/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/bootstraps/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="../assets/bootstraps/boxicons/css/boxicons.min.css" rel="stylesheet">
  <link href="../assets/bootstraps/quill/quill.snow.css" rel="stylesheet">
  <link href="../assets/bootstraps/quill/quill.bubble.css" rel="stylesheet">
  <link href="../assets/bootstraps/remixicon/remixicon.css" rel="stylesheet">
  <link href="../assets/bootstraps/simple-datatables/style.css" rel="stylesheet">

  <!-- Template Main CSS File -->
  <link href="../assets/css/style.css" rel="stylesheet">

  <!-- Template Import CSV File  -->
  <link rel="stylesheet" type="text/css" href="https://getbootstrap.com/dist/css/bootstrap.min.css">
  <script type="text/javascript" src="https://code.jquery.com/jquery-2.1.4.min.js"></script>
  <script type="text/javascript" src="bootstrap-filestyle.js"></script>

  <!-- Sweet Alert  -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="sweetalert2.all.min.js"></script>
</head>

<body>

  <?php include '../include/header.php'; ?>
  <?php include '../include/admin-sidebar.php'; ?>

  <main id="main" class="main">

    <div class="pagetitle">
      <h1>Creation of Account</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="admin.php">Dashboard</a></li>
          <li class="breadcrumb-item">Tables</li>
          <li class="breadcrumb-item active">Registration</li>
        </ol>
      </nav>
    </div><!-- End Page Title -->

    <section class="section dashboard">
    <div class="col-lg-12">
      <div class="card">
        <div class="card-body" >
          
          <?php include '../include/modal.php'; ?>

        <form action="admin_accStudent.php" method="POST" id="manage_user">
          <input type="hidden" name="id" value="<?php echo isset($id) ? $id : '' ?>">
          <input type="hidden" name="user_status" value="<?php echo isset($user_status) ? $user_status : '' ?>">
          <div class="row">
            <div class="col-md-6 border-right">
              <b class="text-muted">Personal Information</b>
              <div class="form-group">
                <label for="" class="control-label">School ID</label>
                <input type="text" name="schoolid" class="form-control form-control-sm" required value="<?php echo isset($studentid) ? $studentid : '' ?>">
              </div>
              <div class="form-group">
                <label for="" class="control-label">First Name</label>
                <input type="text" name="firstname" class="form-control form-control-sm" required value="<?php echo isset($firstname) ? $firstname : '' ?>">
              </div>
              <div class="form-group">
                <label for="" class="control-label">Last Name</label>
                <input type="text" name="lastname" class="form-control form-control-sm" required value="<?php echo isset($lastname) ? $lastname : '' ?>">
              </div>
              <div class="form-group">
                <label class="control-label">Email</label>
                <input type="email" class="form-control form-control-sm" name="email" id="email" required value="<?php echo isset($email) ? $email : '' ?>">
                <small id="#msg"></small>
              </div>
              <div class="form-group">
              <input type="hidden" class="form-control form-control-sm" name="password" id="password" required>
              </div>
              <script>
              function generateRandomPassword(length = 10) {
              const characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
              let password = '';
              for (let i = 0; i < length; i++) {
              password += characters.charAt(Math.floor(Math.random() * characters.length));
                }
              return password;
              }

              function generatedPassword() {
              const passwordField = document.getElementById('password');
              const generatedPassword = generateRandomPassword();
              passwordField.value = generatedPassword;
            }

              // Generate password immediately when the page loads
              generatedPassword();
            </script>
              
              <br>
            </div>


          <div class="col-md-6">
            <b class="text-muted">System Credentials</b>
            <div class="form-group">
              <label for="department" class="control-label">Department</label>
              <select class="form-control form-control-sm" id="department" name="department" required>
                <option value="" disabled selected>Select Department</option>
                <option value="TED">TED</option>
                <option value="BED">BED</option>
              </select>
            </div>
            <div class="form-group" id="subDepartmentGroup" style="display: none;">
              <label for="subDepartment">Sub-Department</label>
              <select class="form-control form-control-sm" id="subDepartment" name="subDepartment" onchange="updateUserLevel()">
                <option value="" disabled selected>Select Sub-Department</option>
              </select>
            </div>
            <div class="form-group" id="programGroup">
            <!-- <b class="text-muted">TED what program</b> -->
              <label for="program">Program</label>
              <select class="form-control form-control-sm" id="program" name="program" required>
                <option value="" disabled selected>Select Program</option>                
              </select>
            </div>
            <div class="form-group" id="majorGroup" style="display: none;">
                <label for="major">Major</label>
                <select class="form-control form-control-sm" id="major" name="major">
                    <option value="" disabled selected>Select Major</option>
                    <option value="Financial Management">Financial Management</option>
                    <option value="Marketing Management">Marketing Management</option>
                </select>
            </div>
            <div class="form-group" id="yearlvlGroup">
            <!-- <b class="text-muted">TED what program</b> -->
              <label for="yearlvl">Year Level</label>
              <select class="form-control form-control-sm" id="yearlvl" name="yearlvl" required>
                <option value="" disabled selected>Select Year Level</option>
              </select>
            </div>
            <div class="form-group" id="sectionGroup" >
              <label for="section">Section</label>
              <select class="form-control form-control-sm" id="section" name="section" required>
                <option value="" disabled selected>Select Section</option>
              </select>
            </div>
          </div>
          <hr>
          <div class="col-lg-12 text-right justify-content-center d-flex">
              <button class="btn btn-primary mr-2" name="register" type="submit" style="margin-right: 10px;">Save</button>
              <button class="btn btn-secondary" type="button" onclick="location.href = '../admin/admin.php'" style="margin-right: 10px;">Cancel</button>
              <button class="btn btn-success" type="button" data-toggle="modal" data-target="#customCSVModal">Import a CSV File</button>
          </div>
        </form>
        </div>
      </div>
    </div>
    </section>
</main>


  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <!-- bootstraps JS Files -->
  <script src="../assets/bootstraps/apexcharts/apexcharts.min.js"></script>
  <script src="../assets/bootstraps/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/bootstraps/chart.js/chart.umd.js"></script>
  <script src="../assets/bootstraps/echarts/echarts.min.js"></script>
  <script src="../assets/bootstraps/quill/quill.min.js"></script>
  <script src="../assets/bootstraps/simple-datatables/simple-datatables.js"></script>
  <script src="../assets/bootstraps/tinymce/tinymce.min.js"></script>
  <script src="../assets/bootstraps/php-email-form/validate.js"></script>
  <script src="../assets/js/main.js"></script>
  
  <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.0.7/dist/umd/popper.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
  <script>
  document.getElementById('manage_user').addEventListener('submit', function(e) {
    var email = document.getElementById('email').value;

    if (email.indexOf('@') === -1 || email.indexOf('.com') === -1) {
      e.preventDefault();
      document.getElementById('emailMsg').innerHTML = 'Email must contain "@" and ".com"';
    } else {
      document.getElementById('emailMsg').innerHTML = '';
    }

  });

</script>


<script>
    // Handle department change event
    document.getElementById('department').addEventListener('change', function () {
        const department = this.value;
        const programGroup = document.getElementById('programGroup');
        const yearlvlGroup = document.getElementById('yearlvlGroup');
        const programSelect = document.getElementById('program');
        const programSelect2 = document.getElementById('yearlvl');
        const majorGroup = document.getElementById('majorGroup');
        const majorSelect = document.getElementById('major');
        const sectionGroup = document.getElementById('sectionGroup');
        const sectionSelect = document.getElementById('section');

        if (department === '') {
            programGroup.style.display = 'none';
            programSelect.innerHTML = '';
            majorGroup.style.display = 'none';
            majorSelect.innerHTML = '';
            sectionGroup.style.display = 'none';
            sectionSelect.innerHTML = '';
        } else {
            programGroup.style.display = 'block';

            // Program options based on the selected department
            let programOptions = [];
            switch (department) {
                case 'TED':
                    programOptions = [
                        { text: 'Select Program', value: '', disabled: true, selected: true }, 
                        { text: 'BLIS Student', value: 'BLIS' },
                        { text: 'BSIT Student', value: 'BSIT' },
                        { text: 'BEED Student', value: 'BEED' },
                        { text: 'BSN Student', value: 'BSN' },
                        { text: 'BSBA Student', value: 'BSBA' },
                        { text: 'IHTM Student', value: 'IHTM' }
                    ];
                    break;
                case 'BED':
                    programOptions = [
                        { text: 'Select Program', value: '', disabled: true, selected: true },
                        { text: 'SHS Student', value: 'SHS Student' },
                        { text: 'JHS Student', value: 'JHS Student' }
                    ];
                    break;
            }

            // Generate the options for the program select input
            const programOptionsHtml = generateOptions(programOptions);
            programSelect.innerHTML = programOptionsHtml;
        }

        // Function to update the "Major" select input
        function updateMajorSelect() {
            const selectedProgram = programSelect.value;
            if (selectedProgram === 'BSBA') {
                majorGroup.style.display = 'block';
            } else {
                majorGroup.style.display = 'none';
            }
        }

        // Add an event listener to update the "Major" select input when the program changes
        programSelect.addEventListener('change', updateMajorSelect);

        // Initially update the "Major" select input based on the department selection
        updateMajorSelect();

        // Handle year level change event
        if (department === '') {
            yearlvlGroup.style.display = 'none';
            programSelect2.innerHTML = '';
        } else {
            yearlvlGroup.style.display = (department === 'TED' || department === 'BED') ? 'block' : 'none';

            if (department === 'TED') {
                // Program options based on the selected department
                let programOptions = [
                    { text: 'Select Year', value: '', disabled: true, selected: true }, 
                    { text: 'First Year', value: 'First Year' },
                    { text: 'Second Year', value: 'Second Year' },
                    { text: 'Third Year', value: 'Third Year' },
                    { text: 'Fourth Year', value: 'Fourth Year' },
                ];

                // Generate the options for the year level select input
                const programOptionsHtml = generateOptions(programOptions);
                programSelect2.innerHTML = programOptionsHtml;
            } else {
                programSelect2.innerHTML = '';
            }
        }
    });

    // Handle program change event
    document.getElementById('program').addEventListener('change', function () {
        const program = this.value;
        const programGroup = document.getElementById('programGroup');
        const yearlvlGroup = document.getElementById('yearlvlGroup');
        const programSelect = document.getElementById('program');
        const programSelect2 = document.getElementById('yearlvl');
        const sectionGroup = document.getElementById('sectionGroup');
        const sectionSelect = document.getElementById('section');

        if (program === '') {
            yearlvlGroup.style.display = 'none';
            programSelect2.innerHTML = '';
            sectionGroup.style.display = 'none';
            sectionSelect.innerHTML = '';
        } else {
            yearlvlGroup.style.display = 'block';

            // Year Level options based on the selected program
            let programOptions = [];
            switch (program) {
                case 'SHS Student':
                    programOptions = [
                        { text: 'Select Option', value: '', disabled: true, selected: true }, 
                        { text: 'Grade 11', value: 'G11' },
                        { text: 'Grade 12', value: 'G12' },
                    ];
                    break;
                case 'JHS Student':
                    programOptions = [
                        { text: 'Select Option', value: '', disabled: true, selected: true }, 
                        { text: 'Grade 7', value: 'G7' },
                        { text: 'Grade 8', value: 'G8' },
                        { text: 'Grade 9', value: 'G9' },
                        { text: 'Grade 10', value: 'G10' },
                    ];
                    break;
                case 'BLIS':
                    programOptions = [
                    { text: 'Select Option', value: '', disabled: true, selected: true }, 
                    { text: 'First Year', value: 'First Year' },
                    { text: 'Second Year', value: 'Second Year' },
                    { text: 'Third Year', value: 'Third Year' },
                    { text: 'Fourth Year', value: 'Fourth Year' }
                    ];
                    break;
                case 'BSIT':
                    programOptions = [
                    { text: 'Select Option', value: '', disabled: true, selected: true }, 
                    { text: 'First Year', value: 'First Year' },
                    { text: 'Second Year', value: 'Second Year' },
                    { text: 'Third Year', value: 'Third Year' },
                    { text: 'Fourth Year', value: 'Fourth Year' }
                    ];
                    break;
                case 'BEED':
                    programOptions = [
                    { text: 'Select Option', value: '', disabled: true, selected: true }, 
                    { text: 'First Year', value: 'First Year' },
                    { text: 'Second Year', value: 'Second Year' },
                    { text: 'Third Year', value: 'Third Year' },
                    { text: 'Fourth Year', value: 'Fourth Year' }
                    ];
                    break;
                case 'BSN':
                    programOptions = [
                    { text: 'Select Option', value: '', disabled: true, selected: true }, 
                    { text: 'First Year', value: 'First Year' },
                    { text: 'Second Year', value: 'Second Year' },
                    { text: 'Third Year', value: 'Third Year' },
                    { text: 'Fourth Year', value: 'Fourth Year' }
                    ];
                    break;
                case 'BSBA':
                    programOptions = [
                    { text: 'Select Option', value: '', disabled: true, selected: true }, 
                    { text: 'First Year', value: 'First Year' },
                    { text: 'Second Year', value: 'Second Year' },
                    { text: 'Third Year', value: 'Third Year' },
                    { text: 'Fourth Year', value: 'Fourth Year' }
                    ];
                    break;
                case 'IHTM':
                    programOptions = [
                    { text: 'Select Option', value: '', disabled: true, selected: true }, 
                    { text: 'First Year', value: 'First Year' },
                    { text: 'Second Year', value: 'Second Year' },
                    { text: 'Third Year', value: 'Third Year' },
                    { text: 'Fourth Year', value: 'Fourth Year' }
                    ];
                    break;
                    }

            // Generate the options for the year level select input
            const programOptionsHtml = generateOptions(programOptions);
            programSelect2.innerHTML = programOptionsHtml;
        }
    });

    document.getElementById('yearlvl').addEventListener('change', function () {
        const sectionGroup = document.getElementById('sectionGroup');
        const sectionSelect = document.getElementById('section');

        // Clear existing options
        sectionSelect.innerHTML = '<option value="" disabled selected>Select Section</option>';

        if (this.value !== 'All') {
            sectionGroup.style.display = 'block';

            // Generate options dynamically based on the selected year level
            const yearLevel = this.value;
            const selectedProgram = document.getElementById('program').value;

            // Call the appropriate function based on the selected program
            const sectionOptions = (selectedProgram === 'JHS Student')
                ? generateJHSOptions(yearLevel)
                : (selectedProgram === 'SHS Student')
                    ? generateSHSOptions(yearLevel)
                    : generateDefaultOptions(yearLevel);

            // Append the generated options to the section dropdown
            sectionSelect.innerHTML += sectionOptions;
        } else {
            sectionGroup.style.display = 'none';
        }
    });

    // Function to generate section options for JHS Student
    function generateJHSOptions(selectedYearLevel) {
        let sectionOptionsHtml = '';

        switch (selectedYearLevel) {
            case 'G7':
                sectionOptionsHtml = generateOptions([
                    { text: 'Love', value: 'Love' },
                    { text: 'Hope', value: 'Hope' },
                    { text: 'Patience', value: 'Patience' },
                    { text: 'Piety', value: 'Piety' }
                ]);
                break;
            case 'G8':
                sectionOptionsHtml = generateOptions([
                    { text: 'Communion', value: 'Communion' },
                    { text: 'Faith', value: 'Faith' },
                    { text: 'Justice', value: 'Justice' },
                    { text: 'Nationalism', value: 'Nationalism' }
                ]);
                break;
            case 'G9':
                sectionOptionsHtml = generateOptions([
                    { text: 'Courage', value: 'Courage' },
                    { text: 'Diligence', value: 'Diligence' },
                    { text: 'Fidelity', value: 'Fidelity' },
                    { text: 'Generosity', value: 'Generosity' }
                ]);
                break;
            case 'G10':
                sectionOptionsHtml = generateOptions([
                    { text: 'Honesty', value: 'Honesty' },
                    { text: 'Humility', value: 'Humility' },
                    { text: 'Peace', value: 'Peace' },
                    { text: 'Wisdom', value: 'Wisdom' }
                ]);
                break;
            // Add cases for other grades in JHS

            default:
                sectionOptionsHtml = generateDefaultOptions(selectedYearLevel);
                break;
        }

        return sectionOptionsHtml;
    }

    // Function to generate section options for SHS Student
    function generateSHSOptions(selectedYearLevel) {
        let sectionOptionsHtml = '';

        switch (selectedYearLevel) {
            case 'G11':
                sectionOptionsHtml = generateOptions([
                    { text: 'OL of Fatima', value: 'OL of Fatima' },
                    { text: 'OL of Grace', value: 'OL of Grace' },
                    { text: 'OL of Pillar', value: 'OL of Pillar' },
                    { text: 'OL of Mt. Carmel', value: 'OL of Mt. Carmel' }
                ]);
                break;
            case 'G12':
                sectionOptionsHtml = generateOptions([
                    { text: 'ST. Augustine', value: 'ST. Augustine' },
                    { text: 'ST. Francis of Assisi', value: 'ST. Francis of Assisi' },
                    { text: 'ST. Paul of Apostle', value: 'ST. Paul of Apostle' },
                    { text: 'ST. Tomas Aquinas', value: 'ST. Tomas Aquinas' },
                    { text: 'ST. Pedro Calungsod', value: 'ST. Pedro Calungsod' }
                ]);
                break;
            // Add cases for other grades in SHS

            default:
                sectionOptionsHtml = generateDefaultOptions(selectedYearLevel);
                break;
        }

        return sectionOptionsHtml;
    }

    // Function to generate default section options
    function generateDefaultOptions(selectedYearLevel) {
        // Add logic here to generate default section options based on the selected year level
        // This is a placeholder, modify it according to your requirements

        const sections = ['A', 'B', 'C', 'D', 'E', 'F', 'No Section'];
        let optionsHtml = '';
        sections.forEach(section => {
            optionsHtml += `<option value="${section}">${section}</option>`;
        });

        return optionsHtml;
    }

    // Function to generate options dynamically
    function generateOptions(options, selectedOption = '') {
        let html = '';
        options.forEach((option) => {
            const selected = (option.value === selectedOption) ? 'selected' : '';
            html += `<option value="${option.value}" ${selected}>${option.text}</option>`;
        });
        return html;
    }
</script>




<script>
    document.getElementById('select_file_button2').addEventListener('click', function() {
        document.getElementById('file_input2').click();
    });

    document.getElementById('upload_button2').addEventListener('click', function() {
        document.querySelector('form').submit();
    });
</script>

</body>

</html>