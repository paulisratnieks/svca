# SVCA
SVCA is a self-hostable video conferencing system.
## Prerequisites
* Docker installed
* Minimum number of CPU cores on server: 4 (the recordings service "Egress" will not work with less)
## Setup
To set up the application you need to clone the repository. Afterward, run the setup bash script with:
```bash
./setup.sh
```
You will be prompted for the domain you will use and new super user credentials, also the system may prompt for sudo password.

You will have to create DNS records with type A for the following domains:
* {domain}
* api.{domain}
* livekit.{domain}

where {domain} is the domain that was entered during the setup script execution.

You also need to acquire SSL certificates for all domains. You should name the private key ```privkey.pem``` and name the certificate chain ```fullchain.pem``` and place both files in the ```docker/web/certs``` directory. If you want to use other names for the certificate files you can update the paths in the nginx configuration manually in ```docker/web/default.conf```.
## Start the app
You can run the app from the project root with:
```bash
docker compose up -d
```
## Stop the app
You can stop the app from the project root with:
```bash
docker compose down
```
