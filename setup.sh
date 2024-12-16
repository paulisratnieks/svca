#!/bin/sh

cp ./api/.env.example ./api/env
cp ./docker/livekit/livekit.example.yaml ./docker/livekit/livekit.yaml
cp ./docker/livekit/egress.example.yaml ./docker/livekit/egress.yaml

docker compose up livekit redis -d
docker exec -it $(docker ps -a --filter "ancestor=livekit/livekit-server:v1.8.0" --format "{{.ID}}") sh -c './livekit-server generate-keys' > keys.tmp
docker compose down

API_KEY=$(grep -o 'API Key:  [^ ]*' keys.tmp | awk '{print $3}' | tr -d '\r\n')
API_SECRET=$(grep -o 'API Secret:  [^ ]*' keys.tmp | awk '{print $3}'| tr -d '\r\n')
rm keys.tmp

sed -i "s/^LIVEKIT_API_KEY=.*/LIVEKIT_API_KEY=${API_KEY}/" ./api/.env
sed -i "s/^LIVEKIT_API_SECRET=.*/LIVEKIT_API_SECRET=${API_SECRET}/" ./api/.env
sed -i "s/^api_key:.*/api_key: ${API_KEY}/" ./docker/livekit/egress.yaml
sed -i "s/^api_secret:.*/api_secret: ${API_SECRET}/" ./docker/livekit/egress.yaml
sed -i "s/key_placeholder/${API_KEY}/" ./docker/livekit/livekit.yaml
sed -i "s/value_placeholder/${API_SECRET}/" ./docker/livekit/livekit.yaml

docker compose up php mysql -d
docker exec -it $(docker ps -a --filter "ancestor=svca-php" --format "{{.ID}}") sh -c 'php artisan key:generate; php artisan make:super-user'

docker compose up -d
