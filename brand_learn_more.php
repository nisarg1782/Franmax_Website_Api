<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
date_default_timezone_set('Asia/Kolkata'); // ensure consistent timezone

include "db.php";

$brand_id = isset($_GET['brand_id']) ? intval($_GET['brand_id']) : 0;
$user_id  = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

if ($brand_id <= 0 || $user_id <= 0) {
    echo json_encode(['status'=>'error','message'=>'Invalid brand_id or user_id.']);
    exit;
}

// Step 1: Check investor plan and cooldown
$check_sql = "
    SELECT id, leads_counter, last_update_at 
    FROM investor_plan_map 
    WHERE register_id = ? 
      AND plan_category_id IN (1,3,4) 
      AND leads_counter >= 1
      AND plan_end_date >= CURDATE()
    LIMIT 1
";

$stmt = $conn->prepare($check_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['status'=>'error','message'=>'No valid plan or limit exceeded.']);
    exit;
}

$plan = $result->fetch_assoc();
$plan_id = (int)$plan['id'];
$current_limit = (int)$plan['leads_counter'];
$last_updated = $plan['last_update_at'];

// --- Cooldown check using DateTime ---
if (!empty($last_updated) && $last_updated != '0000-00-00 00:00:00') {
    $last = new DateTime($last_updated);
    $now = new DateTime();

    $diffSeconds = $now->getTimestamp() - $last->getTimestamp();

    if ($diffSeconds < 86400) { // less than 24 hours
        $remaining = 86400 - $diffSeconds;
        $hours_remaining = floor($remaining / 3600);
        $minutes_remaining = floor(($remaining % 3600) / 60);

        echo json_encode([
            'status' => 'error',
            'message' => 'You can request again after '.$hours_remaining.' hrs '.$minutes_remaining.' mins.'
        ]);
        exit;
    }
}

// Step 2: Fetch brand manager details
$brand_sql = "
    SELECT bd_manager_name, bd_manager_contact, bd_manager_email 
    FROM brands 
    WHERE register_id = ?
    LIMIT 1
";
$stmt2 = $conn->prepare($brand_sql);
$stmt2->bind_param("i", $brand_id);
$stmt2->execute();
$brand_res = $stmt2->get_result();

if ($brand_res->num_rows === 0) {
    echo json_encode(['status'=>'error','message'=>'Brand not found.']);
    exit;
}

$brand_data = $brand_res->fetch_assoc();

// Step 3: Update investor plan (subtract 1 and update timestamp)
// IMPORTANT: use plan_id in WHERE instead of user_id
$new_limit = max(0, $current_limit - 1);
$update_sql = "UPDATE investor_plan_map 
               SET leads_counter = ?, last_update_at = NOW() 
               WHERE id = ?";
$stmt3 = $conn->prepare($update_sql);
$stmt3->bind_param("ii", $new_limit, $plan_id);
$stmt3->execute();

// Step 4: Return success
echo json_encode([
    'status' => 'success',
    'message' => 'Details fetched successfully.',
    'bde_manager' => [
        'name'    => $brand_data['bd_manager_name'],
        'contact' => $brand_data['bd_manager_contact'],
        'email'   => $brand_data['bd_manager_email']
    ],
    'remaining_limit' => $new_limit
]);
exit;
?>
