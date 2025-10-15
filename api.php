<?php
require "database.php";

$resource = $_GET['resource'] ?? '';
$id = isset($_GET['id']) ? intval($_GET['id']) : null;
$method = $_SERVER['REQUEST_METHOD'];

if ($resource === '') {
  respond(200, [
    "message" => "Dealership API ready",
    "endpoints" => [
      "POST ?resource=auth",
      "GET|POST|PUT|DELETE ?resource=cars",
      "GET|POST ?resource=sales"
    ]
  ]);
}

switch ($resource) {
  case 'auth':
    if ($method !== 'POST') respond(405, ["error" => "POST only"]);
    $b = json_body();
    $u = $b['username'] ?? '';
    $p = $b['password'] ?? '';
    if ($u === '' || $p === '') respond(400, ["error" => "username & password required"]);
    $stmt = $pdo->prepare("SELECT id, password_hash FROM staff WHERE username = ?");
    $stmt->execute([$u]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row || !password_verify($p, $row['password_hash'])) {
      respond(403, ["error" => "Bad credentials"]);
    }
    $token = bin2hex(random_bytes(32));
    $up = $pdo->prepare("UPDATE staff SET token=? WHERE id=?");
    $up->execute([$token, $row['id']]);
    respond(200, ["token" => $token]);
    break;

  case 'cars':
    if ($method === 'GET') {
      if ($id) {
        $stmt = $pdo->prepare("SELECT * FROM cars WHERE id=?");
        $stmt->execute([$id]);
        $car = $stmt->fetch(PDO::FETCH_ASSOC);
        $car ? respond(200, $car) : respond(404, ["error" => "Not found"]);
      } else {
        $res = $pdo->query("SELECT * FROM cars ORDER BY id DESC");
        $out = $res->fetchAll(PDO::FETCH_ASSOC);
        respond(200, $out);
      }
    } elseif ($method === 'POST') {
      require_token($pdo);
      $b = json_body();
      foreach (['make','model','year','price'] as $f) if (!isset($b[$f])) respond(400, ["error"=>"Missing $f"]);
      $stmt = $pdo->prepare("INSERT INTO cars(make,model,year,price) VALUES(?,?,?,?)");
      $stmt->execute([$b['make'], $b['model'], $b['year'], $b['price']]);
      respond(201, ["id" => (int)$pdo->lastInsertId()]);
    } elseif ($method === 'PUT' && $id) {
      require_token($pdo);
      $b = json_body();
      $fields = []; $params = [];
      foreach (['make','model','year','price','status'] as $k) {
        if (array_key_exists($k, $b)) { $fields[] = "$k=?"; $params[] = $b[$k]; }
      }
      if (!$fields) respond(400, ["error"=>"No fields to update"]);
      $params[] = $id;
      $sql = "UPDATE cars SET ".implode(",", $fields)." WHERE id=?";
      $stmt = $pdo->prepare($sql);
      $stmt->execute($params);
      respond(200, ["updated" => $stmt->rowCount() > 0]);
    } elseif ($method === 'DELETE' && $id) {
      require_token($pdo);
      $stmt = $pdo->prepare("DELETE FROM cars WHERE id=?");
      $stmt->execute([$id]);
      respond(200, ["deleted" => $stmt->rowCount() > 0]);
    } else {
      respond(405, ["error" => "Method not allowed"]);
    }
    break;

  case 'sales':
    if ($method === 'GET') {
      $res = $pdo->query("SELECT * FROM sales ORDER BY id DESC");
      $out = $res->fetchAll(PDO::FETCH_ASSOC);
      respond(200, $out);
    } elseif ($method === 'POST') {
      require_token($pdo);
      $b = json_body();
      foreach (['car_id','customer_name','sale_price'] as $f) if (!isset($b[$f])) respond(400, ["error"=>"Missing $f"]);
      // verify car exists
      $check = $pdo->prepare("SELECT id FROM cars WHERE id=?");
      $check->execute([$b['car_id']]);
      if (!$check->fetch(PDO::FETCH_ASSOC)) respond(404, ["error"=>"car_id not found"]);
      // record sale
      $stmt = $pdo->prepare("INSERT INTO sales(car_id,customer_name,sale_price) VALUES(?,?,?)");
      $stmt->execute([$b['car_id'], $b['customer_name'], $b['sale_price']]);
      // mark car sold
      $upd = $pdo->prepare("UPDATE cars SET status='sold' WHERE id=?");
      $upd->execute([$b['car_id']]);
      respond(201, ["id" => (int)$pdo->lastInsertId()]);
    } else {
      respond(405, ["error" => "Method not allowed"]);
    }
    break;

  default:
    respond(404, ["error" => "Unknown resource"]);
}
