<?php
include("../dbconn.php");

if (isset($_POST['register'])) {
    // Retrieve form data
    $schoolid = $_POST['schoolid'];
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $conpass = $_POST['conpass'];
    $user_status = $_POST['user_status'];
    $department = $_POST['department'];
    $subDepartment = $_POST['subDepartment'];
    $defaultImagePath = "../assets/img/user.jpg";

    // Since we're bypassing email verification, no need for activation code and expiry
    // $activation_code = generateActivationCode(); // Removed
    // $activation_expiry_date = date('Y-m-d H:i:s', strtotime('+24 hours')); // Removed

    // Encrypt the password securely
    // Note: It's recommended to use password_hash() instead of md5 for better security
    $pass_enc = password_hash($password, PASSWORD_BCRYPT);

    // Check if the data already exists
    $query = $con->prepare("SELECT * FROM users WHERE schoolid = ? AND user_status = ?");
    $query->bind_param("si", $schoolid, $user_status);
    $query->execute();
    $query->store_result();

    if ($query->num_rows > 0) {
        // Data already exists
        echo '<script>alert("Data already exists");</script>';
    } else {
        if ($password === $conpass) {
            // Prepare the INSERT statement without activation fields
            $register = $con->prepare("INSERT INTO users (schoolid, firstname, lastname, email, password, department, subDepartment, user_status, user_img, active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            // Set active to 1 since the user is automatically verified
            $active = 1; 

            // Bind parameters (ensure the types match your database schema)
            $register->bind_param("sssssssisi", $schoolid, $firstname, $lastname, $email, $pass_enc, $department, $subDepartment, $user_status, $defaultImagePath, $active);

            // Execute the query
            if ($register->execute()) {
                // Optionally, you can send a welcome email without activation link
                /*
                $emailRecipient = $email;
                $emailSubject = "Welcome to Pilar College";
                $emailBody = "Hi $firstname, welcome to Pilar College! Your account has been successfully created.";

                // Use cURL to send the email
                $url = "https://script.google.com/macros/s/AKfycbyRkHpW_F0KNnqYf4i6HFiCN-jHnTnJVJtF9MdWyJZqwmJZ3UMzO3sbkPKCtRMu4TuCuw/exec";
                $ch = curl_init($url);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_POSTFIELDS => http_build_query([
                        "recipient" => $emailRecipient,
                        "subject" => $emailSubject,
                        "body" => $emailBody,
                    ]),
                ]);
                $result = curl_exec($ch);

                // Check if the email was sent successfully
                if ($result !== false) {
                    echo '<script>alert("Registration Successful. A welcome email has been sent.");</script>';
                } else {
                    echo '<script>alert("Registration Successful, but failed to send welcome email.");</script>';
                }
                */

                // If you choose not to send any email, simply notify the user
                echo '<script>alert("Registration Successful. You are now logged in.");</script>';
            } else {
                echo '<script>alert("Registration Failed");</script>';
            }
        } else {
            echo '<script>alert("Password Mismatch");</script>';
        }
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.0/css/line.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../reg.css">
    <link href="../assets/img/pilar.png" rel="icon">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <title>Pilar College</title>
</head>
<body>
    <div class="container">
        <div class="row d-flex justify-content-center">
            <div class="col-md-4">
                <div class="card px-5 py-5" id="form1">
                    <div class="form-data" v-if="!submitted">
                        <form id="regForm" action="registration.php" method="POST">

                        <input type="hidden" id="user_status" name="user_status" value="1">
                        <input type="hidden" id="department" name="department" >
                        <input type="hidden" id="subDepartment" name="subDepartment" >


                            <div class="forms-inputs mb-4"> <span>School ID</span> 
                                <input type="text" id="schoolid" name="schoolid" required>
                            </div>
                            <div class="forms-inputs mb-4"> <span>First Name</span> 
                                <input type="text" id="firstname" name="firstname" required>
                            </div>
                            <div class="forms-inputs mb-4"> <span>Last Name</span> 
                                <input type="text" id="lastname" name="lastname" required>
                            </div>
                            <div class="forms-inputs mb-4"> <span>Email</span> 
                                <input type="text" id="email" name="email" required>
                            </div>
                            <div class="forms-inputs mb-4"><span>Password</span></label>
                                <input type="password" name="password" id="password" required >
                                <small id="passwordMsg"></small>
                            </div>
                            <div class="forms-inputs mb-4"> <span>Confirm Password</span> 
                                <input type="password" id="conpass" name="conpass" required>
                            </div>
                        
                            <div class="mb-3"> <button type="submit" name="register" class="btn btn-dark w-100">Register</button> </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
  document.getElementById('manage_user').addEventListener('submit', function(e) {
    var email = document.getElementById('email').value;
    var password = document.getElementById('password').value;
    var conpass = document.getElementById('conpass').value;

    if (email.indexOf('@') === -1 || email.indexOf('.com') === -1) {
      e.preventDefault();
      document.getElementById('emailMsg').innerHTML = 'Email must contain "@" and ".com"';
    } else {
      document.getElementById('emailMsg').innerHTML = '';
    }

    if (password.length < 8 || password.length > 9) {
      e.preventDefault();
      document.getElementById('passwordMsg').innerHTML = 'Password must be 8 to 9 characters long';
    } else {
      document.getElementById('passwordMsg').innerHTML = '';
    }

    if (password !== conpass) {
      e.preventDefault();
      alert('Password and Confirm Password do not match');
    }
  });

  document.getElementById('password').addEventListener('input', function() {
    var password = this.value;
    var passwordMsg = document.getElementById('passwordMsg');

    if (password.length < 8 || password.length > 9) {
      passwordMsg.innerHTML = 'Password must be 8 to 9 characters long';
    } else {
      passwordMsg.innerHTML = '';
    }
  });
</script>
</body>
</html>
