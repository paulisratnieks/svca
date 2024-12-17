#!/bin/sh

cp ./api/.env.example ./api/.env
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

echo -n "Enter the domain the app will be using: "
read domain

docker run -ti --rm -w /certs -v $(pwd)/docker/web/certs:/certs alpine/mkcert -cert-file "${domain}.pem" -key-file "${domain}-key.pem" "${domain}" "livekit.${domain}" "api.${domain}"

cp ./docker/web/default.example.conf ./docker/web/default.conf
sed -i "s/domain_placeholder/${domain}/g" ./docker/web/default.conf

docker compose up -d
