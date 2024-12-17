#!/bin/sh

cp ./api/.env.example ./api/.env
cp ./web/.env.example ./web/.env
cp ./docker/livekit/livekit.example.yaml ./docker/livekit/livekit.yaml
cp ./docker/livekit/egress.example.yaml ./docker/livekit/egress.yaml

docker compose up livekit redis -d
docker exec -it $(docker ps -a --filter "ancestor=livekit/livekit-server:v1.8.0" --format "{{.ID}}") sh -c './livekit-server generate-keys' > keys.tmp
docker compose down

api_key=$(grep -o 'API Key:  [^ ]*' keys.tmp | awk '{print $3}' | tr -d '\r\n')
api_secret=$(grep -o 'API Secret:  [^ ]*' keys.tmp | awk '{print $3}'| tr -d '\r\n')
rm keys.tmp

echo -n "Enter the domain the app will be using: "
read domain

sed -i "s/^LIVEKIT_API_KEY=.*/LIVEKIT_API_KEY=${api_key}/" ./api/.env
sed -i "s/^LIVEKIT_API_SECRET=.*/LIVEKIT_API_SECRET=${api_secret}/" ./api/.env
sed -i "s/^LIVEKIT_URL=.*/LIVEKIT_URL=wss:\/\/livekit.${domain}/" ./api/.env
sed -i "s/^APP_URL=.*/APP_URL=https:\/\/${domain}/" ./api/.env
sed -i "s/^SESSION_DOMAIN=.*/SESSION_DOMAIN=.${domain}/" ./api/.env
sed -i "s/^SANCTUM_STATEFUL_DOMAINS=.*/SANCTUM_STATEFUL_DOMAINS=${domain}/" ./api/.env

sed -i "s/^VITE_API_URL=.*/VITE_API_URL=https:\/\/api.${domain}/" ./web/.env
sed -i "s/^VITE_LIVEKIT_API_URL=.*/VITE_LIVEKIT_API_URL=wss:\/\/livekit.${domain}/" ./web/.env

sed -i "s/^api_key:.*/api_key: ${api_key}/" ./docker/livekit/egress.yaml
sed -i "s/^api_secret:.*/api_secret: ${api_secret}/" ./docker/livekit/egress.yaml

sed -i "s/key_placeholder/${api_key}/" ./docker/livekit/livekit.yaml
sed -i "s/value_placeholder/${api_secret}/" ./docker/livekit/livekit.yaml

docker compose --env-file ./api/.env up php mysql -d

container_id = ''
while [ -z "$container_id" ]; do
  container_id=$(sudo docker ps -a -f ancestor=svca-php -f health=healthy --format "{{.ID}}")
  if [ -z "$container_id" ]; then
    echo "Waiting for mysql container to fully initialize"
    sleep 2
  fi
done

docker exec -it "$container_id" sh -c 'php artisan key:generate; php artisan migrate --no-interaction; php artisan make:super-user'

cp ./docker/web/default.example.conf ./docker/web/default.conf
sed -i "s/domain_placeholder/${domain}/g" ./docker/web/default.conf

docker compose up -d
