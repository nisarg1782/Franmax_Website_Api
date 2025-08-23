<?php


$host = "localhost";
$username = "root";
$password = "";
$database = "testcc";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("DB connection failed: " . $e->getMessage());
}
$brand = [
    "name" => "Sankalp Restaurant",
    "description" => "Sankalp is a leading South Indian restaurant chain with a strong national and international presence.",
    "established_year" => 1980,
    "website" => "https://www.sankalpgroup.com",
    "logo" => "sankalp_logo.png",
    "search_key" => "b1c1_fofo_premium_southindian_6000000",
    "modals" => [1, 2],  // FOFO, FOCO
    "franchise_types" => [1, 2], // 1=Single Unit, 2=Master Franchise
    "plan_category_id" => 1,
    "subcategory_id" => 1
];

try {
    $pdo->beginTransaction();

    // Insert brand
    $stmt = $pdo->prepare("INSERT INTO brands (name, description, established_year, website, logo, search_key)
        VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $brand["name"], $brand["description"], $brand["established_year"],
        $brand["website"], $brand["logo"], $brand["search_key"]
    ]);
    $brandId = $pdo->lastInsertId();

    // Modals
    $stmt = $pdo->prepare("INSERT INTO brand_modal_map (brand_id, modal_id) VALUES (?, ?)");
    foreach ($brand["modals"] as $mid) {
        $stmt->execute([$brandId, $mid]);
    }

    // Franchise types
    $stmt = $pdo->prepare("INSERT INTO brand_franchise_type (brand_id, franchise_type_id) VALUES (?, ?)");
    foreach ($brand["franchise_types"] as $fid) {
        $stmt->execute([$brandId, $fid]);

        if ($fid == 1) {
            // Single Unit Details
            $pdo->prepare("INSERT INTO single_unit_details (brand_id, investment_required, roi, payback_period)
                VALUES (?, ?, ?, ?)")
                ->execute([$brandId, 6000000, '18-24%', '2-3 years']);
        }

        if ($fid == 2) {
            // Master Unit Details (Country + State + City)
            $stmt = $pdo->prepare("INSERT INTO master_unit_details (brand_id, level, country_id, state_id, city_id, investment_required, roi, payback_period)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

            // Country level
            $stmt->execute([$brandId, 'country', 1, NULL, NULL, 8000000, '20%', '3-4 years']);

            // State level
            $stmt->execute([$brandId, 'state', 1, 1, NULL, 8500000, '22%', '3-5 years']);

            // City level
            $stmt->execute([$brandId, 'city', 1, 1, 1, 9000000, '25%', '4 years']);
        }
    }

    // Category
    $pdo->prepare("INSERT INTO brand_category_map (brand_id, subcategory_id) VALUES (?, ?)")
        ->execute([$brandId, $brand["subcategory_id"]]);

    // Plan
    $pdo->prepare("INSERT INTO brand_plan_map (brand_id, plan_category_id) VALUES (?, ?)")
        ->execute([$brandId, $brand["plan_category_id"]]);

    $pdo->commit();
    echo json_encode(["success" => true, "brand_id" => $brandId]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(["error" => $e->getMessage()]);
}