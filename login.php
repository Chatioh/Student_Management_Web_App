<?php
// Initialize the session
session_start();
 
// Check if the user is already logged in, if yes then redirect him to index page
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: index.php");
    exit;
}
 
// Include config file
require_once "config.php";
 
// Define variables and initialize with empty values
$email = $password = "";
$email_err = $password_err = $login_err = "";
 
// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
 
    // Check if email is empty
    if(empty(trim($_POST["email"]))){
        $email_err = "Please enter email.";
    } else{
        $email = trim($_POST["email"]);
    }
    
    // Check if password is empty
    if(empty(trim($_POST["password"]))){
        $password_err = "Please enter your password.";
    } else{
        $password = trim($_POST["password"]);
    }
    
    // Validate credentials
    if(empty($email_err) && empty($password_err)){
        // Prepare a select statement
        $sql = "SELECT id, email, password FROM users WHERE email = ?";
        
        if($stmt = mysqli_prepare($link, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "s", $param_email);
            
            // Set parameters
            $param_email = $email;
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                // Store result
                mysqli_stmt_store_result($stmt);
                
                // Check if email exists, if yes then verify password
                if(mysqli_stmt_num_rows($stmt) == 1){                    
                    // Bind result variables
                    mysqli_stmt_bind_result($stmt, $id, $email, $hashed_password);
                    if(mysqli_stmt_fetch($stmt)){
                        if(password_verify($password, $hashed_password)){
                            // Password is correct, so start a new session
                            session_start();
                            
                            // Store data in session variables
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["email"] = $email;                            
                            
                            //selecting email and phonenumber from database to create its session variable
                            $sql = "SELECT * FROM users WHERE id = $id";
                            $result = $link->query($sql);
                            $row = $result->fetch_assoc();
                            $_SESSION["role"] = $row['role']; 
                            $_SESSION["full_name"] = $row['full_name']; 
                            $_SESSION["company_name"] = $row['company_name']; 
                            $_SESSION["email"] = $row['email']; 
                            $_SESSION["phone_number"] = $row['phone_number']; 

                            // Redirect user to index page
                            header("location: index.php");
                        } else{
                            // Password is not valid, display a generic error message
                            $login_err = "Invalid email or password.";
                        }
                    }
                } else{
                    // email doesn't exist, display a generic error message
                    $login_err = "Invalid email or password.";
                }
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
    <title>Welcome to SMA!</title>
    <link rel="stylesheet" href="assets/css/register-login.css">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="assets/vendor/bootstrap/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.min.css" rel="stylesheet">
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
                        <h1 class="carousel-title">Welcome to SMA!</h1>
                        <p class="carousel-text">A platform that brings students and the school close together.</p>
                    </div> 
                </div>
            </div>
            
            <!-- Right side with login form -->
            <div class="col-12 col-md-6 login-side">
                <div class="login-form">
                    <div class="text-center">
                        <h2 class="form-title">Login</h2>
                        <p class="form-paragraph">Enter your credentials to login</p>
                    </div>
                    
                    <form method="POST" action="" class="loginform">
                        <div class="col-md-12 mt-3">
                            <label style="font-size: 14px;">Email</label><br>
                            <input value="" type="email" class="form-control py-2 w-100 <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" name="email" placeholder="Email">
                            <span class="invalid-feedback"><?php echo $email_err; ?></span>
                        </div>
                        <div class="col-md-12 mt-3">
                            <label style="font-size: 14px;">Password</label><br>
                            <input value="" type="password" class="form-control py-2 w-100 <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" name="password" placeholder="Enter your password">
                            <span class="invalid-feedback"><?php echo $password_err; ?></span>
                        </div>
                        <br>
                        <button type="submit" class="sign-up-btn">Login</button>
                    </form>
                    
                    <!-- <div class="divider">Or sign up with</div>
                    
                    <div class="social-btns">
                        <button class="social-btn google-btn">
                            <i class="bi bi-google"></i>
                        </button>
                        <button class="social-btn twitter-btn">
                            <i class="bi bi-twitter"></i>
                        </button>
                        <button class="social-btn facebook-btn">
                            <i class="bi bi-facebook"></i>
                        </button>
                    </div> -->
                    <div class="signin-link mb-4">
                        Don't have account? <a href="register.php">Sign Up</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
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