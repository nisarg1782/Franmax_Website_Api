

<?php
// older version of get_brand_profile.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

include "db.php";

// $brandId = intval($_GET['brand_id']);
$brandId=4; // For testing, hardcoded brand ID

$response = [];

// 1. Basic Brand Info
$brand = mysqli_fetch_assoc(mysqli_query($conn, "
  SELECT * FROM brands WHERE id = $brandId
"));
$response = $brand;

// 2. Category
$q = "SELECT 
        mc.name AS master_category, 
        c.name AS category, 
        s.name AS subcategory 
      FROM brand_category_map bcm
      JOIN subcategories s ON bcm.subcategory_id = s.id
      JOIN categories c ON s.category_id = c.id
      JOIN master_categories mc ON c.master_category_id = mc.id
      WHERE bcm.brand_id = $brandId LIMIT 1";
$cat = mysqli_fetch_assoc(mysqli_query($conn, $q));
$response['category'] = $cat['category'] ?? '';
$response['subcategory'] = $cat['subcategory'] ?? '';
$response['master_category'] = $cat['master_category'] ?? '';

// 3. Modals
$modals = [];
$mod_res = mysqli_query($conn, "SELECT m.name FROM brand_modal_map bmm JOIN modals m ON bmm.modal_id = m.id WHERE brand_id = $brandId");
while ($row = mysqli_fetch_assoc($mod_res)) $modals[] = $row['name'];
$response['modals'] = $modals;

// 4. Franchise Types
$ftypes = [];
$ft_res = mysqli_query($conn, "SELECT f.name FROM brand_franchise_type bft JOIN franchise_types f ON bft.franchise_type_id = f.id WHERE brand_id = $brandId");
while ($row = mysqli_fetch_assoc($ft_res)) $ftypes[] = $row['name'];
$response['franchise_types'] = $ftypes;

// 5. Plan Categories
$plans = [];
$plan_res = mysqli_query($conn, "SELECT pc.name FROM brand_plan_map bpm JOIN plan_categories pc ON bpm.plan_category_id = pc.id WHERE brand_id = $brandId");
while ($row = mysqli_fetch_assoc($plan_res)) $plans[] = $row['name'];
$response['plan_categories'] = $plans;

// 6. Single Unit
$single_unit = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM single_unit_details WHERE brand_id = $brandId"));
$response['single_unit'] = $single_unit;

// 7. Master Units
$master_units = [];
$mres = mysqli_query($conn, "SELECT * FROM master_unit_details WHERE brand_id = $brandId");
while ($row = mysqli_fetch_assoc($mres)) $master_units[] = $row;
$response['master_units'] = $master_units;

echo json_encode($response);
?>
