<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit();
}

include "db.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$data = json_decode(file_get_contents("php://input"), true);
if (!$data || !isset($data['brand_id'])) {
  echo json_encode(['success' => false, 'message' => 'Invalid input']);
  exit;
}

$brandId = intval($data['brand_id']);
$franchiseTypes = $data['franchise_types'] ?? [];
$singleUnit = $data['single_unit'] ?? null;
$masterFranchises = $data['master_franchises'] ?? [];
$preferences = $data['preferences'] ?? [];

$bd_manager_name = isset($data['bd_manager_name']) ? $conn->real_escape_string($data['bd_manager_name']) : null;
$bd_manager_email = isset($data['bd_manager_email']) ? $conn->real_escape_string($data['bd_manager_email']) : null;
$bd_manager_contact = isset($data['bd_manager_contact']) ? $conn->real_escape_string($data['bd_manager_contact']) : null;

try {
  $conn->begin_transaction();

  // 1. Franchise Types
  $conn->query("DELETE FROM brand_franchise_type WHERE brand_id = $brandId");
  foreach ($franchiseTypes as $type) {
    $type = $conn->real_escape_string($type);
    $result = $conn->query("SELECT id FROM franchise_types WHERE name = '$type'");
    if ($row = $result->fetch_assoc()) {
      $typeId = intval($row['id']);
      $conn->query("INSERT INTO brand_franchise_type (brand_id, franchise_type_id) VALUES ($brandId, $typeId)");
    }
  }

  // 2. Single Unit Details
  if (in_array("Single Unit", $franchiseTypes)) {
    $investment = floatval($singleUnit['investment']);
    $roi = $conn->real_escape_string($singleUnit['roi']);
    $payback = $conn->real_escape_string($singleUnit['payback']);
    $area = isset($singleUnit['area']) ? $conn->real_escape_string($singleUnit['area']) : '';

    $exists = $conn->query("SELECT id FROM single_unit_details WHERE brand_id = $brandId")->num_rows > 0;
    if ($exists) {
      $conn->query("UPDATE single_unit_details SET investment_required = $investment, roi = '$roi', payback_period = '$payback', area = '$area' WHERE brand_id = $brandId");
    } else {
      $conn->query("INSERT INTO single_unit_details (brand_id, investment_required, roi, payback_period, area) VALUES ($brandId, $investment, '$roi', '$payback', '$area')");
    }
  } else {
    $conn->query("DELETE FROM single_unit_details WHERE brand_id = $brandId");
  }

  // 3. Master Franchise Details
  if (in_array("Master", $franchiseTypes) || in_array("Master Franchise", $franchiseTypes)) {
    $conn->query("DELETE FROM master_unit_details WHERE brand_id = $brandId");
    foreach ($masterFranchises as $fr) {
      if (!isset($fr['level']) || !isset($fr['investment'])) continue;

      $level = $conn->real_escape_string($fr['level']);
      $investment = floatval($fr['investment']);
      $roi = isset($fr['roi']) ? $conn->real_escape_string($fr['roi']) : '';
      $payback = isset($fr['payback']) ? $conn->real_escape_string($fr['payback']) : '';
      $area = isset($fr['area']) ? $conn->real_escape_string($fr['area']) : '';

      $countryId = 1;
      $stateId = ($level === 'state' || $level === 'city') ? 1 : 'NULL';
      $cityId = ($level === 'city') ? 1 : 'NULL';

      $conn->query("INSERT INTO master_unit_details 
        (brand_id, level, country_id, state_id, city_id, investment_required, roi, payback_period, area) 
        VALUES 
        ($brandId, '$level', $countryId, $stateId, $cityId, $investment, '$roi', '$payback', '$area')");
    }
  } else {
    $conn->query("DELETE FROM master_unit_details WHERE brand_id = $brandId");
  }

  // 4. Preferences Update
  $updates = [];
  foreach ($preferences as $key => $value) {
    $sanitized = $conn->real_escape_string($value);
    $updates[] = "$key = '$sanitized'";
  }

  // 5. BD Manager Info Update
  if ($bd_manager_name !== null) $updates[] = "bd_manager_name = '$bd_manager_name'";
  if ($bd_manager_email !== null) $updates[] = "bd_manager_email = '$bd_manager_email'";
  if ($bd_manager_contact !== null) $updates[] = "bd_manager_contact = '$bd_manager_contact'";

  if (!empty($updates)) {
    $updateSql = "UPDATE brands SET " . implode(", ", $updates) . " WHERE id = $brandId";
    $conn->query($updateSql);
  }

  $conn->commit();
  echo json_encode(['success' => true]);
} catch (Exception $e) {
  $conn->rollback();
  echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
