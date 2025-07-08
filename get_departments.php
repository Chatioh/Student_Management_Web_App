<?php
require_once "config.php";

header("Content-Type: application/json");

$departments = [];

if (isset($_GET["school_id"]) && !empty($_GET["school_id"])) {
    $school_id = $_GET["school_id"];

    // Fetch levels associated with the selected school
    $sql_levels = "SELECT id FROM levels WHERE school_id = ?";
    if ($stmt_levels = mysqli_prepare($link, $sql_levels)) {
        mysqli_stmt_bind_param($stmt_levels, "i", $school_id);
        if (mysqli_stmt_execute($stmt_levels)) {
            $result_levels = mysqli_stmt_get_result($stmt_levels);
            $level_ids = [];
            while ($row_level = mysqli_fetch_assoc($result_levels)) {
                $level_ids[] = $row_level["id"];
            }
            mysqli_free_result($result_levels);

            if (!empty($level_ids)) {
                $level_ids_str = implode(",", $level_ids);
                // Fetch departments associated with these levels
                $sql_departments = "SELECT id, name FROM departments WHERE level_id IN ($level_ids_str) ORDER BY name";
                if ($result_departments = mysqli_query($link, $sql_departments)) {
                    while ($row_dept = mysqli_fetch_assoc($result_departments)) {
                        $departments[] = $row_dept;
                    }
                    mysqli_free_result($result_departments);
                }
            }
        }
        mysqli_stmt_close($stmt_levels);
    }
}

echo json_encode($departments);

mysqli_close($link);
?>
