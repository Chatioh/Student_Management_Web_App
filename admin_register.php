<?php
// Include config file
require_once "config.php";
// Initialize the session
session_start();
// Check if the user is already logged in, if yes then redirect him to index page
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: AdminDashboard/dashboard.php");
    exit;
}
 
// Define variables and initialize with empty values
$full_name = $phone_number = $email = $password = $confirm_password = $location = "";
$full_name_err = $phone_number_err  = $email_err = $password_err = $confirm_password_err = $location_err = "";
 
// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
 
    // Validate full_name
    if(empty(trim($_POST["full_name"]))){
        $full_name_err = "Please enter a full name.";
    } elseif(!preg_match('/^[a-zA-Z0-9_ ]+$/', trim($_POST["full_name"]))){
        $full_name_err = "Name can only contain letters, numbers, spaces, and underscores.";
    } else{
        // Prepare a select statement
        $sql = "SELECT id FROM admin WHERE full_name = ?";
        
        if($stmt = mysqli_prepare($link, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "s", $param_full_name);
            
            // Set parameters
            $param_full_name = trim($_POST["full_name"]);
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                /* store result */
                mysqli_stmt_store_result($stmt);
                
                if(mysqli_stmt_num_rows($stmt) == 1){
                    $full_name_err = "This full name is already taken.";
                } else{
                    $full_name = trim($_POST["full_name"]);
                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }

    // Validate phonenumber
    if(empty(trim($_POST["phone_number"]))){
        $phone_number_err = "Please enter your phone number.";
    } elseif (!preg_match('/^\\+?\\d{3,15}$/', trim($_POST["phone_number"]))) {
        $phone_number_err = "Invalid phone number format.";
    } else{
        // Prepare a select statement
        $sql = "SELECT id FROM admin WHERE phone_number = ?";
        
        if($stmt = mysqli_prepare($link, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "s", $param_phone_number);
            
            // Set parameters
            $param_phone_number = trim($_POST["phone_number"]);
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                /* store result */
                mysqli_stmt_store_result($stmt);
                
                if(mysqli_stmt_num_rows($stmt) == 1){
                    $phone_number_err = "This phone number is already taken.";
                } else{
                    $phone_number = trim($_POST["phone_number"]);
                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }

    // Validate email
    if(empty(trim($_POST["email"]))){
        $email_err = "Please enter your email.";
    } elseif (!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)) {
        $email_err = "Invalid email format.";
    } else{
        // Prepare a select statement
        $sql = "SELECT id FROM admin WHERE email = ?";
        
        if($stmt = mysqli_prepare($link, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "s", $param_email);
            
            // Set parameters
            $param_email = trim($_POST["email"]);
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                /* store result */
                mysqli_stmt_store_result($stmt);
                
                if(mysqli_stmt_num_rows($stmt) == 1){
                    $email_err = "This email is already taken.";
                } else{
                    $email = trim($_POST["email"]);
                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }
    

    // Validate password
    if(empty(trim($_POST["password"]))){
        $password_err = "Please enter a password.";     
    } elseif(strlen(trim($_POST["password"])) < 6){
        $password_err = "Password must have atleast 6 characters.";
    } else{
        $password = trim($_POST["password"]);
    }
    
    // Validate confirm password
    if(empty(trim($_POST["confirm_password"]))){
        $confirm_password_err = "Please confirm password.";     
    } else{
        $confirm_password = trim($_POST["confirm_password"]);
        if(empty($password_err) && ($password != $confirm_password)){
            $confirm_password_err = "Password did not match.";
        }
    }
    
    // Validate location
    if(empty(trim($_POST["location"]))){
        $location_err = "Please enter a location.";
    } elseif(!preg_match('/^[a-zA-Z0-9_ ]+$/', trim($_POST["location"]))){
        $location_err = "Location can only contain letters, numbers, spaces, and underscores.";
    } else{
        // Prepare a select statement
        $sql = "SELECT id FROM admin WHERE location = ?";
        
        if($stmt = mysqli_prepare($link, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "s", $param_location);
            
            // Set parameters
            $param_location = trim($_POST["location"]);
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                /* store result */
                mysqli_stmt_store_result($stmt);
                
                $location = trim($_POST["location"]);
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }


    // Check input errors before inserting in database
    if(empty($full_name_err) && empty($phone_number_err) && empty($email_err) &&  empty($password_err) && empty($confirm_password_err) && empty($location_err)){
        
        // Prepare an insert statement
        $sql = "INSERT INTO admin (full_name, phone_number, email, password, location) VALUES (?, ?, ?, ?, ?)";
         
        if($stmt = mysqli_prepare($link, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "sssss", $param_full_name, $param_phone_number, $param_email, $param_password, $param_location);
            
            // Set parameters
            $param_full_name = $full_name;
            $param_phone_number = $phone_number;
            $param_email = $email;
            $param_password = password_hash($password, PASSWORD_DEFAULT); // Creates a password hash
            $param_location = $location;
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                // Redirect to login page
                header("location: login.php");
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }
    
    // Close connection
    mysqli_close($link);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Registration</title>
    <link rel="stylesheet" href="assets/css/register-login.css">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="assets/vendor/bootstrap/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body>
    <div class="main-container">
        <div class="row g-0 h-100">
            <!-- Left side with carousel -->
            <div class="col-md-6 carousel-side">
                <div class="carousel-dim">
                    <br><br><br><br><br>
                    <div class="text-center text-white">
                        <a href="index.php" class="logo"><img class="pb-2" src="assets/images/b2bblue.svg" alt="" width="110px" height="auto"></a>
                        <br><br>
                        <h1 class="carousel-title">Welcome to SMA Admin!</h1>
                        <p class="carousel-text">The dashboard that brings manages students.</p>
                    </div> 
                </div>
            </div>
            
            <!-- Right side with login form -->
            <div class="col-12 col-md-6 login-side">
                <div class="login-form">
                    <div class="text-center">
                        <h2 class="form-title">Admin Registration</h2>
                        <p class="form-paragraph">Fill the form below to register as an admin</p>
                    </div>
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="loginform">
                        <div class="row gx-2">
                            <div class="col-md-6">
                                <input value="<?php echo $full_name; ?>" type="text"  class="form-control py-2 w-100 mt-3 <?php echo (!empty($full_name_err)) ? 'is-invalid' : ''; ?>" name="full_name" placeholder="Full Name">
                                <span class="invalid-feedback"><?php echo $full_name_err; ?></span>
                            </div>
                            <div class="col-md-6">
                                <input value="<?php echo $phone_number; ?>" 
                                    type="tel" 
                                    class="form-control py-2 mt-3 <?php echo (!empty($phone_number_err)) ? 'is-invalid' : ''; ?>" 
                                    name="phone_number" 
                                    placeholder="Phone Number">
                                <span class="invalid-feedback"><?php echo $phone_number_err; ?></span>
                            </div>
                        </div>
                        <div class="row gx-2">
                            <div class="col-md-6">
                                <input value="<?php echo $email; ?>" type="email" class="form-control py-2 w-100 mt-3 <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" name="email" placeholder="Email">
                                <span class="invalid-feedback"><?php echo $email_err; ?></span>
                            </div>
                            <div class="col-md-6">
                                <input value="<?php echo $password; ?>" type="password"  class="form-control py-2 w-100 mt-3 <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" name="password" placeholder="Create a password">
                                <span class="invalid-feedback"><?php echo $password_err; ?></span>
                            </div>
                        </div>
                        <div class="row gx-2">
                            <div class="col-md-6">
                                <input value="<?php echo $confirm_password; ?>" type="password"  class="form-control py-2 w-100 mt-3 <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>" name="confirm_password" placeholder="Confirm your password">
                                <span class="invalid-feedback"><?php echo $confirm_password_err; ?></span>
                            </div>
                            <div class="col-md-6">
                                <input value="<?php echo $location; ?>" type="text"  class="form-control py-2 w-100 mt-3 <?php echo (!empty($location_err)) ? 'is-invalid' : ''; ?>" name="location" placeholder="Enter your location">
                                <span class="invalid-feedback"><?php echo $location_err; ?></span>
                            </div>
                        </div>
                        <button type="submit" class="sign-up-btn mt-4 mb-2">Sign Up</button>
                    </form>
                
                    <div class="signin-link mb-4">
                        Already have account? <a href="admin_login.php">Sign In</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="assets/bootstrap-5.3.3-dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Manual carousel implementation
        document.addEventListener('DOMContentLoaded', function() {
            const slides = document.querySelectorAll('.carousel-slide');
            const indicators = document.querySelectorAll('.carousel-indicator');
            let currentSlide = 0;
            
            function showSlide(n) {
                // Hide all slides
                slides.forEach(slide => {
                    slide.classList.remove('active');
                });
                
                // Remove active class from all indicators
                indicators.forEach(indicator => {
                    indicator.classList.remove('active');
                });
                
                // Show the current slide and activate indicator
                slides[n].classList.add('active');
                indicators[n].classList.add('active');
                
                currentSlide = n;
            }
            
            // Attach click events to indicators
            indicators.forEach((indicator, index) => {
                indicator.addEventListener('click', () => {
                    showSlide(index);
                });
            });
            
            // Auto rotate slides
            setInterval(() => {
                let nextSlide = (currentSlide + 1) % slides.length;
                showSlide(nextSlide);
            }, 5000);
        });
    </script>
</body>
</html>