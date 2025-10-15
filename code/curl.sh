#!/usr/bin/env bash
set -e

BASE="http://localhost:3000/api.php"

# 1) login → token
AUTH_RESPONSE=$(curl -s -X POST "$BASE?resource=auth" \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"Carlo"}')
echo "AUTH RAW: $AUTH_RESPONSE" >&2
TOKEN=$(printf '%s' "$AUTH_RESPONSE" | php -r '$data = json_decode(stream_get_contents(STDIN), true); if (!is_array($data) || !isset($data["token"])) {fwrite(STDERR, "Auth failed: ".json_encode($data).PHP_EOL); exit(1);} echo $data["token"];')
echo "TOKEN=$TOKEN"

# 2) list cars (empty)
curl -s "$BASE?resource=cars" | jq .

# 3) create car (secured)
CREATE_RESPONSE=$(curl -s -X POST "$BASE?resource=cars" \
  -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"make":"Toyota","model":"Supra","year":2021,"price":55000}')
echo "$CREATE_RESPONSE" | jq .
CAR_ID=$(printf '%s' "$CREATE_RESPONSE" | php -r '$data = json_decode(stream_get_contents(STDIN), true); if (!is_array($data) || !isset($data["id"])) {fwrite(STDERR, "Create car failed: ".json_encode($data).PHP_EOL); exit(1);} echo (int)$data["id"];')
echo "CAR_ID=$CAR_ID"

# 4) list cars
curl -s "$BASE?resource=cars" | jq .

# 5) get by id
curl -s "$BASE?resource=cars&id=$CAR_ID" | jq .

# 6) update car (secured)
curl -s -X PUT "$BASE?resource=cars&id=$CAR_ID" \
  -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"price":54000}' | jq .

# 7) record sale (secured)
curl -s -X POST "$BASE?resource=sales" \
  -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d "{\"car_id\":$CAR_ID,\"customer_name\":\"Jane Doe\",\"sale_price\":53500}" | jq .

# 8) list sales
curl -s "$BASE?resource=sales" | jq .

# 9) delete car (secured)
curl -s -X DELETE "$BASE?resource=cars&id=$CAR_ID" \
  -H "Authorization: Bearer $TOKEN" | jq .

# 10) confirm deletion
curl -s -i "$BASE?resource=cars&id=$CAR_ID"
