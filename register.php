<?php
// Include config file
require_once "config.php";
// Initialize the session
session_start();
// Check if the user is already logged in, if yes then redirect him to index page
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: index.php");
    exit;
}
 
// Define variables and initialize with empty values
$full_name = $matricule = $school = $country_code = $phone_number = $email = $password = $confirm_password = $location = "";
$full_name_err = $matricule_err = $school_err = $country_code_err = $phone_number_err  = $email_err = $password_err = $confirm_password_err = $location_err = "";
 
// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
 
    // Validate matricule
    if(empty(trim($_POST["matricule"]))){
        $matricule_err = "Please enter a Company name.";
    } elseif(!preg_match('/^[a-zA-Z0-9_ ]+$/', trim($_POST["matricule"]))){
        $matricule_err = "Matricule can only contain letters, numbers, spaces, and underscores.";
    } else{
        // Prepare a select statement
        $sql = "SELECT id FROM students WHERE matricule = ?";
        
        if($stmt = mysqli_prepare($link, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "s", $param_matricule);
            
            // Set parameters
            $param_matricule = trim($_POST["matricule"]);
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                /* store result */
                mysqli_stmt_store_result($stmt);
                
                if(mysqli_stmt_num_rows($stmt) == 1){
                    $matricule_err = "This Company name is already taken.";
                } else{
                    $matricule = trim($_POST["matricule"]);
                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }

    // Validate username
    if(empty(trim($_POST["full_name"]))){
        $full_name_err = "Please enter a full name.";
    } elseif(!preg_match('/^[a-zA-Z0-9_ ]+$/', trim($_POST["full_name"]))){
        $full_name_err = "Name can only contain letters, numbers, spaces, and underscores.";
    } else{
        // Prepare a select statement
        $sql = "SELECT id FROM students WHERE full_name = ?";
        
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
                    $full_name_err = "This Username is already taken.";
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

    // Validate email
    if(empty(trim($_POST["email"]))){
        $email_err = "Please enter your email.";
    } elseif (!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)) {
        $email_err = "Invalid email format.";
    } else{
        // Prepare a select statement
        $sql = "SELECT id FROM students WHERE email = ?";
        
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

    // Validate phonenumber
    if(empty(trim($_POST["phone_number"]))){
        $phone_number_err = "Please enter your phone number.";
    } elseif (!preg_match('/^\+?\d{3,15}$/', trim($_POST["phone_number"]))) {
        $phone_number_err = "Invalid phone number format.";
    } else{
        // Prepare a select statement
        $sql = "SELECT id FROM students WHERE phone_number = ?";
        
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

    // Validate school
    if(empty($_POST["school"]) || $_POST["school"] == "Select school"){
        $school_err = "Please select a school.";
    } else {
        // Prepare a select statement
        $sql = "SELECT id FROM users WHERE school = ?";
        
        if($stmt = mysqli_prepare($link, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "s", $param_school);
            
            // Set parameters
            $param_school = trim($_POST["school"]);
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                /* store result */
                mysqli_stmt_store_result($stmt);
                $school = trim($_POST["school"]);
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }

    // Validate department
    if(empty($_POST["department"]) || $_POST["department"] == "Select department"){
        $department_err = "Please select a department.";
    } else {
        // Prepare a select statement
        $sql = "SELECT id FROM students WHERE department_id = ?";
        
        if($stmt = mysqli_prepare($link, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "s", $param_department);
            
            // Set parameters
            $param_department = trim($_POST["department"]);
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                /* store result */
                mysqli_stmt_store_result($stmt);
                $department = trim($_POST["department"]);
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
        $location_err = "Please enter a Company name.";
    } elseif(!preg_match('/^[a-zA-Z0-9_ ]+$/', trim($_POST["department"]))){
        $department_err = "department can only contain letters, numbers, spaces, and underscores.";
    } else{
        // Prepare a select statement
        $sql = "SELECT id FROM users WHERE department = ?";
        
        if($stmt = mysqli_prepare($link, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "s", $param_department);
            
            // Set parameters
            $param_department = trim($_POST["department"]);
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                /* store result */
                mysqli_stmt_store_result($stmt);
                
                $department = trim($_POST["department"]);
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }


    // Check input errors before inserting in database
    if(empty($full_name_err) && empty($matricule_err) && empty($school_err) && empty($country_code_err) && empty($phone_number_err) && empty($email_err) &&  empty($password_err) && empty($confirm_password_err) && empty($department_err)){
        
        // Prepare an insert statement
        $sql = "INSERT INTO users (full_name, matricule, school, country_code, phone_number, email, password, department) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
         
        if($stmt = mysqli_prepare($link, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "ssssssss", $param_full_name, $param_matricule, $param_school, $param_country_code, $param_phone_number, $param_email, $param_password, $param_department);
            
            // Set parameters
            $param_full_name = $full_name;
            $param_matricule = $matricule;
            $param_school = $school;
            $param_country_code = $country_code;
            $param_phone_number = $phone_number;
            $param_email = $email;
            $param_password = password_hash($password, PASSWORD_DEFAULT); // Creates a password hash
            $param_department = $department;
            
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
            <!-- Left side-->
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
                        <h2 class="form-title">Get Started</h2>
                        <p class="form-paragraph">Fill the form below to register</p>
                    </div>
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="loginform">
                        <div class="row gx-2">
                            <div class="col-md-6">
                                <input value="<?php echo $matricule; ?>" type="text"  class="form-control py-2 w-100 mt-3 <?php echo (!empty($matricule_err)) ? 'is-invalid' : ''; ?>" name="matricule" placeholder="Matricule">
                                <span class="invalid-feedback"><?php echo $matricule_err; ?></span>
                            </div>
                            <div class="col-md-6">
                                <input value="<?php echo $full_name; ?>" type="text"  class="form-control py-2 w-100 mt-3 <?php echo (!empty($full_name_err)) ? 'is-invalid' : ''; ?>" name="full_name" placeholder="Full Name">
                                <span class="invalid-feedback"><?php echo $full_name_err; ?></span>
                            </div>
                        </div>
                        <div class="row gx-2">
                            <div class="col-md-6">
                                <input value="<?php echo $email; ?>" type="email" class="form-control py-2 w-100 mt-3 <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" name="email" placeholder="Email">
                                <span class="invalid-feedback"><?php echo $email_err; ?></span>
                            </div>
                            <div class="col-md-6">
                                <input value="<?php echo $phone_number; ?>" type="text"  class="form-control py-2 w-100 mt-3 <?php echo (!empty($phone_number_err)) ? 'is-invalid' : ''; ?>" name="phone_number" placeholder="Phone Number">
                                <span class="invalid-feedback"><?php echo $phone_number_err; ?></span>
                            </div>
                        </div>
                        <div class="row gx-2">
                            <div class="col-md-6">
                                <select class="form-select py-2 w-100 mt-3 <?php echo (!empty($school_err)) ? 'is-invalid' : ''; ?>" name="school" id="supplier-school" onchange="updateDepartment('supplier')" required>
                                    <option selected disabled>Select school</option>
                                    <option value="Centre">Centre</option>
                                    <option value="East">East</option>
                                    <option value="Adamawa">Adamawa</option>
                                    <option value="North">North</option>
                                    <option value="South">South</option>
                                    <option value="Southwest">Southwest</option>
                                    <option value="Northwest">Northwest</option>
                                    <option value="Littoral">Littoral</option>
                                    <option value="West">West</option>
                                    <option value="Farnorth">Farnorth</option>
                                </select>
                                <span class="invalid-feedback"><?php echo $school_err; ?></span>
                            </div>
                            <div class="col-md-6">
                                <select class="form-select py-2 w-100 mt-3 <?php echo (!empty($department_err)) ? 'is-invalid' : ''; ?>" name="department" id="supplier-department" required>
                                    <option selected disabled>Select school first</option>
                                </select>
                                <span class="invalid-feedback"><?php echo $department_err; ?></span>
                            </div>
                        </div>
                        <div class="row gx-2">
                            <div class="col-md-6">
                                <input value="<?php echo $password; ?>" type="password"  class="form-control py-2 w-100 mt-3 <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" name="password" placeholder="Create a password">
                                <span class="invalid-feedback"><?php echo $password_err; ?></span>
                            </div>
                            <div class="col-md-6">
                                <input value="<?php echo $confirm_password; ?>" type="password"  class="form-control py-2 w-100 mt-3 <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>" name="confirm_password" placeholder="Confirm your password">
                                <span class="invalid-feedback"><?php echo $confirm_password_err; ?></span>
                            </div>
                        </div>
                        
                        <button type="submit" class="mt-4 sign-up-btn">Sign Up</button>
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
                        Already have account? <a href="login.php">Sign In</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
    const departmentBySchool = {
    "Centre": ["Bafia", "Ntui", "Mfou", "Ngoumou", "Monatélé", "Yaoundé", "Eseka", "Akonolinga", "Mbalmayo"],
    "East": ["Yokadouma", "Abong-Mbang", "Batouri", "Bertoua"],
    "Adamawa": ["Tibati", "Tignère", "Meiganga", "Ngaoundéré", "Banyo"],
    "North": ["Garoua", "Poli", "Guider", "Tcholliré"],
    "South": ["Sangmélima", "Ebolowa", "Kribi", "Ambam"],
    "Southwest": ["Limbe", "Buea", "Bangem", "Menji", "Mamfe", "Kumba", "Mundemba"],
    "Northwest": ["Fundong", "Kumbo", "Nkambe", "Wum", "Bamenda", "Mbengwi", "Ndop"],
    "Littoral": ["Douala", "Yabassi", "Édéa", "Nkongsamba"],
    "West": ["Mbouda", "Baham", "Bafang", "Dschang", "Bandjoun", "Bafoussam", "Foumban"],
    "Farnorth": ["Maroua", "Kousséri", "Yagoua", "Kaélé", "Mora", "Mokolo"]
};

function updateDepartment(formType) {
    const schoolSelect = document.getElementById(`${formType}-school`);
    const departmentSelect = document.getElementById(`${formType}-department`);

    // Clear current options
    departmentSelect.innerHTML = '';

    const selectedSchool = schoolSelect.value;

    if (selectedSchool) {
        departmentSelect.disabled = false;

        const defaultOption = document.createElement('option');
        defaultOption.disabled = true;
        defaultOption.selected = true;
        defaultOption.textContent = 'Select department';
        departmentSelect.appendChild(defaultOption);

        const departments = departmentBySchool[selectedSchool] || [];
        departments.forEach(department => {
            const option = document.createElement('option');
            option.value = department.toLowerCase().replace(/\s+/g, '-');
            option.textContent = department;
            departmentSelect.appendChild(option);
        });
    } else {
        departmentSelect.disabled = true;

        const option = document.createElement('option');
        option.disabled = true;
        option.selected = true;
        option.textContent = 'Select school first';
        departmentSelect.appendChild(option);
    }
}
  </script>
</body>
</html>