<?php
// Include config file
require_once "config.php";

// Initialize the session
session_start();

// Check if the user is already logged in, if yes then redirect him to index page
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: StudentDashboard/home.php");
    exit;
}

// Define variables and initialize with empty values
$full_name = $matricule = $school_id = $department_id = $phone_number = $email = $password = $confirm_password = "";
$full_name_err = $matricule_err = $school_err = $department_err = $phone_number_err  = $email_err = $password_err = $confirm_password_err = "";

// Function to fetch schools
function getSchools($link) {
    $schools = [];
    $sql = "SELECT id, name FROM schools ORDER BY name";
    if($result = mysqli_query($link, $sql)){
        while($row = mysqli_fetch_assoc($result)){
            $schools[] = $row;
        }
        mysqli_free_result($result);
    }
    return $schools;
}

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){

    // Validate matricule
    if(empty(trim($_POST["matricule"]))){
        $matricule_err = "Please enter a matricule.";
    } else{
        $matricule = trim($_POST["matricule"]);
        // Check if matricule exists in students table and is not yet registered (full_name is empty or null)
        $sql = "SELECT id, full_name FROM students WHERE matricule = ?";
        if($stmt = mysqli_prepare($link, $sql)){
            mysqli_stmt_bind_param($stmt, "s", $param_matricule);
            $param_matricule = $matricule;
            if(mysqli_stmt_execute($stmt)){
                mysqli_stmt_store_result($stmt);
                if(mysqli_stmt_num_rows($stmt) == 0){
                    $matricule_err = "Matricule not found. Please contact administration.";
                } else {
                    mysqli_stmt_bind_result($stmt, $id, $existing_full_name);
                    mysqli_stmt_fetch($stmt);
                    if (!empty($existing_full_name)) {
                        $matricule_err = "This matricule is already registered.";
                    }
                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }
            mysqli_stmt_close($stmt);
        }
    }

    // Validate full_name
    if(empty(trim($_POST["full_name"]))){
        $full_name_err = "Please enter your full name.";
    } else{
        $full_name = trim($_POST["full_name"]);
    }

    // Validate email
    if(empty(trim($_POST["email"]))){
        $email_err = "Please enter your email.";
    } elseif (!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)) {
        $email_err = "Invalid email format.";
    } else{
        $email = trim($_POST["email"]);
        // Check if email is already taken by another student
        $sql = "SELECT id FROM students WHERE email = ? AND matricule != ?";
        if($stmt = mysqli_prepare($link, $sql)){
            mysqli_stmt_bind_param($stmt, "ss", $param_email, $param_matricule_check);
            $param_email = $email;
            $param_matricule_check = $matricule; // Exclude current matricule from check
            if(mysqli_stmt_execute($stmt)){
                mysqli_stmt_store_result($stmt);
                if(mysqli_stmt_num_rows($stmt) >= 1){
                    $email_err = "This email is already taken.";
                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }
            mysqli_stmt_close($stmt);
        }
    }

    // Validate phone_number
    if(empty(trim($_POST["phone_number"]))){
        $phone_number_err = "Please enter your phone number.";
    } elseif (!preg_match("/^\+?\d{3,15}$/", trim($_POST["phone_number"]))) {
        $phone_number_err = "Invalid phone number format.";
    } else{
        $phone_number = trim($_POST["phone_number"]);
    }

    // Validate school
    if(empty($_POST["school"]) || $_POST["school"] == ""){
        $school_err = "Please select a school.";
    } else {
        $school_id = $_POST["school"];
    }

    // Validate department
    if(empty($_POST["department"]) || $_POST["department"] == ""){
        $department_err = "Please select a department.";
    } else {
        $department_id = $_POST["department"];
    }

    // Validate password
    if(empty(trim($_POST["password"]))){
        $password_err = "Please enter a password.";     
    } elseif(strlen(trim($_POST["password"])) < 6){
        $password_err = "Password must have at least 6 characters.";
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

    // Check input errors before updating in database
    if(empty($full_name_err) && empty($matricule_err) && empty($school_err) && empty($phone_number_err) && empty($email_err) && empty($password_err) && empty($confirm_password_err) && empty($department_err)){
        
        // Update the existing student record with full details
        $sql = "UPDATE students SET full_name = ?, email = ?, phone_number = ?, password = ?, school_id = ?, department_name = ? WHERE matricule = ?";
         
        if($stmt = mysqli_prepare($link, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "sssssss", $param_full_name, $param_email, $param_phone_number, $param_password, $param_school_id, $param_department_id, $param_matricule);
            
            // Set parameters
            $param_full_name = $full_name;
            $param_email = $email;
            $param_phone_number = $phone_number;
            $param_password = password_hash($password, PASSWORD_DEFAULT); // Creates a password hash
            error_log("Hashed Password: " . ($param_password === false ? "HASHING FAILED" : $param_password));
            $param_school_id = $school_id;
            $param_department_id = $department_id;
            $param_matricule = $matricule;
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                // Get the student_id of the newly registered student
                $student_id_for_enrollment = null;
                $sql_get_student_id = "SELECT id FROM students WHERE matricule = ?";
                if($stmt_get_id = mysqli_prepare($link, $sql_get_student_id)){
                    mysqli_stmt_bind_param($stmt_get_id, "s", $param_matricule);
                    mysqli_stmt_execute($stmt_get_id);
                    mysqli_stmt_bind_result($stmt_get_id, $student_id_for_enrollment);
                    mysqli_stmt_fetch($stmt_get_id);
                    mysqli_stmt_close($stmt_get_id);
                }

                if ($student_id_for_enrollment) {
                    // Enroll student in all courses for their department
                    $sql_courses = "SELECT id FROM courses WHERE department_id = ?";
                    if($stmt_courses = mysqli_prepare($link, $sql_courses)){
                        mysqli_stmt_bind_param($stmt_courses, "i", $param_department_id);
                        if(mysqli_stmt_execute($stmt_courses)){
                            $result_courses = mysqli_stmt_get_result($stmt_courses);
                            while($row_course = mysqli_fetch_assoc($result_courses)){
                                $course_id = $row_course["id"];
                                $sql_enroll = "INSERT INTO enrollments (student_id, course_id) VALUES (?, ?)";
                                if($stmt_enroll = mysqli_prepare($link, $sql_enroll)){
                                    mysqli_stmt_bind_param($stmt_enroll, "ii", $student_id_for_enrollment, $course_id);
                                    mysqli_stmt_execute($stmt_enroll);
                                    mysqli_stmt_close($stmt_enroll);
                                }
                            }
                        }
                        mysqli_stmt_close($stmt_courses);
                    }
                }

                // Redirect to login page with success message
                $_SESSION["registration_success"] = "You have been successfully Admitted! Welcome to SMA, your student journey begins now.";
                header("location: login.php");
                exit();
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }
    
}

// Fetch schools for dropdown
$schools = getSchools($link);

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
                                <input value="<?php echo $matricule; ?>" type="text"  class="form-control py-2 w-100 mt-3 <?php echo (!empty($matricule_err)) ? (
                                    !empty($matricule_err) ? 'is-invalid' : ''
                                ) : ''; ?>" name="matricule" placeholder="Matricule">
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
                                <select class="form-select py-2 w-100 mt-3 <?php echo (!empty($school_err)) ? 'is-invalid' : ''; ?>" name="school" id="school-select" required>
                                    <option value="" selected disabled>Select school</option>
                                    <?php foreach ($schools as $school_option): ?>
                                        <option value="<?php echo $school_option['id']; ?>" <?php echo ($school_option['id'] == $school_id) ? 'selected' : ''; ?>><?php echo $school_option['name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <span class="invalid-feedback"><?php echo $school_err; ?></span>
                            </div>
                            <div class="col-md-6">
                                <select class="form-select py-2 w-100 mt-3 <?php echo (!empty($department_err)) ? 'is-invalid' : ''; ?>" name="department" id="department-select" required>
                                    <option value="" selected disabled>Select department</option>
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
    document.addEventListener("DOMContentLoaded", function() {
        const schoolSelect = document.getElementById("school-select");
        const departmentSelect = document.getElementById("department-select");

        // Function to fetch departments based on school_id
        function fetchDepartments(schoolId) {
            departmentSelect.innerHTML = 
                `<option value="" selected disabled>Loading departments...</option>`;
            departmentSelect.disabled = true;

            fetch(`get_departments.php?school_id=${schoolId}`)
                .then(response => response.json())
                .then(data => {
                    departmentSelect.innerHTML = 
                        `<option value="" selected disabled>Select department</option>`;
                    if (data.length > 0) {
                        data.forEach(department => {
                            const option = document.createElement("option");
                            option.value = department.id;
                            option.textContent = department.name;
                            departmentSelect.appendChild(option);
                        });
                        departmentSelect.disabled = false;
                    } else {
                        departmentSelect.innerHTML = 
                            `<option value="" selected disabled>No departments found</option>`;
                    }
                })
                .catch(error => {
                    console.error("Error fetching departments:", error);
                    departmentSelect.innerHTML = 
                        `<option value="" selected disabled>Error loading departments</option>`;
                });
        }

        // Event listener for school select change
        schoolSelect.addEventListener("change", function() {
            const selectedSchoolId = this.value;
            if (selectedSchoolId) {
                fetchDepartments(selectedSchoolId);
            } else {
                departmentSelect.innerHTML = 
                    `<option value="" selected disabled>Select school first</option>`;
                departmentSelect.disabled = true;
            }
        });

        // If a school was previously selected (e.g., on form submission error), re-fetch departments
        const initialSchoolId = schoolSelect.value;
        if (initialSchoolId) {
            fetchDepartments(initialSchoolId);
        }
    });
    </script>
</body>
</html>



<?php
mysqli_close($link);
?>
