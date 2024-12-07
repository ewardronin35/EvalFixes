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
// Function to generate a unique activation code
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

if (isset($_POST['register'])) {
  // Retrieve form data
  $schoolid = $_POST['schoolid'];
  $firstname = $_POST['firstname'];
  $lastname = $_POST['lastname'];
  $email = $_POST['email'];
  $password = $_POST['password'];
  $subDepartment = $_POST['subDepartment'];
  $program = isset($_POST['program']) ? $_POST['program'] : '';
  $user_status = $_POST['user_status'];
  $department = $_POST['department'];
// Define the default image file path
$major = isset($_POST['major']) ? trim($_POST['major']) : '';
$section = isset($_POST['section']) ? trim($_POST['section']) : '';
$yearlvl = isset($_POST['yearlvl']) ? trim($_POST['yearlvl']) : '';
  $defaultImagePath = "../assets/img/user.jpg";

  $pass_enc = md5($password);

  // Check if the email is already taken
  $check_email = $con->query("SELECT * FROM users WHERE email = '$email'");
  // Check if the schoolid is already taken
  $check_schoolid = $con->query("SELECT * FROM users WHERE schoolid = '$schoolid'");

  if ($check_email->num_rows > 0 && $check_schoolid->num_rows > 0) {
      echo '<script>alert("The email and schoolid are already taken")</script>';
  } elseif ($check_email->num_rows > 0) {
      echo '<script>alert("The email is already taken")</script>';
  } elseif ($check_schoolid->num_rows > 0) {
      echo '<script>alert("The schoolid is already taken")</script>';
  } else {
    if (!empty($subDepartment)) {

      // // Store the user data in the database
      // $activation_code = generateActivationCode();
      // $activation_expiry_date = date('Y-m-d H:i:s', strtotime('+24 hours'));

      $register = $con->prepare("INSERT INTO users (schoolid, firstname, lastname, email, password, department, subDepartment, user_status, program, major, section, yearlvl, user_img, active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
      
      $active = 1; // User is not yet active
      $register->bind_param("sssssssisssssi", $schoolid, $firstname, $lastname, $email, $pass_enc, $department, $subDepartment, $user_status, $program, $major, $section, $yearlvl, $defaultImagePath, $active);

      if ($register->execute()) {
        // Generate the activation link
          // $activation_link = "http://localhost/pilareval2023/admin/Activate.php?code=$activation_code";

          // Send email with credentials and activation link using cURL
          // sendActivationEmail($email, $password, $activation_link);

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
          if (empty($department)) {
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
  }
}
}


$query = $con->query("SELECT * FROM users WHERE schoolid = '{$_SESSION['schoolid']}'");
$userData = $query->fetch_assoc();
$imagePath = $userData['user_img'];


?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>PCZC Evaluation</title>
  <meta content="" name="description">
  <meta content="" name="keywords">

  <!-- Favicons -->
  <link href="<?php echo $logo; ?>" rel="icon">
  <link href="../assets/img/apple-touch-icon.png" rel="apple-touch-icon">

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
        <form action="admin_accEmp.php" method="POST" id="manage_user">
          <input type="hidden" name="id" value="<?php echo isset($id) ? $id : '' ?>">
          <div class="row">
            <div class="col-md-6 border-right">
              <b class="text-muted">Personal Information</b>
              <!-- <button class="test" id="test">Test</button> -->
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
                <small id="emailMsg"></small>
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
            </div>

            <div class="col-md-6" >
              <b class="text-muted">System Credentials</b>
              <div class="form-group" >
                  <label for="user_status" class="control-label">User Type</label>
                  <select name="user_status" id="user_status" class="form-control form-control-sm" required>
                      <option value="" disabled selected>Select User Type</option>
                      <option value="6">Staff</option>
                      <option value="5">Guidance</option>
                      <option value="4">IQAT</option>
                  </select>
              </div>
              <div class="form-group" >
                <label for="department" class="control-label">Department</label>
                <select class="form-control form-control-sm" id="department" name="department" required>
                  <option value="" disabled selected>Select Department</option>
                  <option value="TED">TED</option>
                  <option value="BED">BED</option>
                  <option value="NTP">NTP</option>
                </select>
              </div>

              <div class="form-group" id="subDepartmentGroup">
                <label for="subDepartment">Sub-Department</label>
                <select class="form-control form-control-sm" id="subDepartment" name="subDepartment" onchange="updateUserLevel()" >
                  <option value="" disabled selected>Select Sub-Department</option>
                </select>
              </div>
              <div class="form-group" id="programGroup" style="display: none;">
              <!-- <b class="text-muted">TED what program</b> -->
                <label for="program">Program</label>
                <select class="form-control form-control-sm" id="program" name="program" onchange="updateprogram()" >
                  <option value="" disabled selected>Select Program</option>
                </select>
              </div>
            </div>

          </div>

          <hr>
          <div class="col-lg-12 text-right justify-content-center d-flex">
              <button class="btn btn-primary mr-2" name="register" type="submit" style="margin-right: 10px;">Save</button>
              <button class="btn btn-secondary" type="button" onclick="location.href = '../admin/admin.php'" style="margin-right: 10px;">Cancel</button>
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

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

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
    function generateOptions(options, selectedOption = '') {
        let html = '';
        options.forEach((option) => {
            const selected = (option.value === selectedOption) ? 'selected' : '';
            html += `<option value="${option.value}" ${selected}>${option.text}</option>`;
        });
        return html;
    }

    // Handle department change event
    document.getElementById('department').addEventListener('change', function() {
        const department = this.value;
        const subDepartmentGroup = document.getElementById('subDepartmentGroup');
        const subDepartmentSelect = document.getElementById('subDepartment');
        const programGroup = document.getElementById('programGroup');
        const programSelect = document.getElementById('program');

        if (department === '') {
            subDepartmentGroup.style.display = 'none';
            subDepartmentSelect.innerHTML = '';
            programGroup.style.display = 'none';
            programSelect.innerHTML = '';
        } else {
            subDepartmentGroup.style.display = 'block';

            // Sub-department options based on the selected department
            let subDepartmentOptions = [];
            switch (department) {
                case 'TED':
                    subDepartmentOptions = [
                        { text: 'Select Option', value: '', disabled: true, selected: true }, 
                        { text: 'Program Head', value: 'Program Head' },
                        { text: 'Teacher', value: 'Teacher' },
                        { text: 'College Dean', value: 'College Dean' },
                        { text: 'Prefect of Discipline', value: 'Prefect of Discipline' },
                        { text: 'Student Activity Coordinator', value: 'Student Activity Coordinator' },
                        { text: 'TED Librarian', value: 'TED Librarian' },
                    ];
                    break;
                case 'BED':
                    subDepartmentOptions = [
                        { text: 'Select Option', value: '', disabled: true, selected: true },
                        { text: 'SHS Teacher', value: 'SHS Teacher' },
                        { text: 'JHS Teacher', value: 'JHS Teacher' },
                        { text: 'Grade School Teacher', value: 'Grade School Teacher' },
                        { text: 'BED Librarian', value: 'BED Librarian' },
                        { text: 'BED Coordinator', value: 'BED Coordinator' },
                    ];
                    break;
                case 'NTP':
                    subDepartmentOptions = [
                        { text: 'Select Option', value: '', disabled: true, selected: true }, 
                        { text: 'Registrar', value: 'Registrar' },
                        { text: 'Finance', value: 'Finance' },
                        { text: 'Librarian', value: 'Librarian' },
                        { text: 'Secretary of President', value: 'Secretary of President' },
                        { text: 'Secretary of TED', value: 'Secretary of TED' },
                        { text: 'Secretary of BED', value: 'Secretary of BED' },
                        { text: 'Procurement', value: 'Procurement' },
                    ];
                    break;
            }
            // Generate the options for the sub-department select input
            const subDepartmentOptionsHtml = generateOptions(subDepartmentOptions);
            subDepartmentSelect.innerHTML = subDepartmentOptionsHtml;
            programGroup.style.display = 'none'; // Hide the program dropdown initially
            programSelect.innerHTML = ''; // Clear the program dropdown options
        }
    });

    // Handle sub-department change event
    document.getElementById('subDepartment').addEventListener('change', function() {
        const subDepartment = this.value;
        const programGroup = document.getElementById('programGroup');
        const programSelect = document.getElementById('program');

        if (subDepartment === '') {
            programGroup.style.display = 'none';
            programSelect.innerHTML = '';
        } else {
            programGroup.style.display = (subDepartment === 'Program Head' || subDepartment === 'Teacher') ? 'block' : 'none';

            if (subDepartment === 'Program Head' || subDepartment === 'Teacher') {
                // Program options based on the selected sub-department
                let programOptions = [
                    { text: 'Select Option', value: '', disabled: true, selected: true }, 
                    { text: 'BLIS', value: 'BLIS' },
                    { text: 'BSIT', value: 'BSIT' },
                    { text: 'BEED', value: 'BEED' },
                    { text: 'BSN', value: 'BSN' },
                    { text: 'BSBA', value: 'BSBA' },
                    { text: 'IHTM', value: 'IHTM' },
                ];

                // Generate the options for the program select input
                const programOptionsHtml = generateOptions(programOptions);
                programSelect.innerHTML = programOptionsHtml;
            } else {
                programSelect.innerHTML = '';
            }
        }
    });

    function toggleSections() {
    const userTypeSelect = document.getElementById('user_status');
    const departmentSelect = document.getElementById('department');
    const subDepartmentSelect = document.getElementById('subDepartment');
    const programSelect = document.getElementById('program');
    const departmentGroup = departmentSelect.closest('.form-group');
    const subDepartmentGroup = document.getElementById('subDepartmentGroup');
    const programGroup = document.getElementById('programGroup');

    // Enable all fields by default
    departmentSelect.disabled = false;
    subDepartmentSelect.disabled = false;
    programSelect.disabled = false;

      if (userTypeSelect.value === '4') { // IQAT
          departmentGroup.style.display = 'block';
          subDepartmentGroup.style.display = 'block';
          programGroup.style.display = 'none';

          // Set department to 'NTP' and subDepartment to 'IQAT'
          departmentSelect.value = 'NTP';
          subDepartmentSelect.innerHTML = '<option value="IQAT" selected>IQAT</option>';

      } else if (userTypeSelect.value === '5') { // Guidance
          departmentGroup.style.display = 'block';
          subDepartmentGroup.style.display = 'block';
          programGroup.style.display = 'none';

          // Set department to 'NTP' and subDepartment to 'Guidance'
          departmentSelect.value = 'NTP';
          subDepartmentSelect.innerHTML = '<option value="Guidance" selected>Guidance</option>';

      } else if (userTypeSelect.value === '6') { // Staff
          departmentGroup.style.display = 'block';
          departmentSelect.value = '';

          subDepartmentGroup.style.display = 'none';
          programGroup.style.display = 'none';
      } else { // Admin and other user types
          departmentGroup.style.display = 'none';
          subDepartmentGroup.style.display = 'none';
          programGroup.style.display = 'none';
      }
  }

    // Attach the toggleSections function to the user type select change event
    document.getElementById('user_status').addEventListener('change', toggleSections);

    // Call the toggleSections function on page load to set the initial state
    toggleSections();
</script>

<script>
    document.getElementById('select_file_button').addEventListener('click', function() {
        document.getElementById('file_input').click();
    });

    document.getElementById('upload_button').addEventListener('click', function() {
        // Add code here to handle the CSV file upload and processing if needed
        // For example, you can submit the form using JavaScript:
        document.querySelector('form').submit();
    });
</script>
</body>

</html>