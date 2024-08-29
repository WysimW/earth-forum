# Mon Projet

# Rebuild the Docker image
`docker-compose build`

# Recreate the containers
`docker-compose up -d`

# Restart docker
`docker restart $(docker ps -q)`

# Go on a container
`docker exec -it CONTAINER_NAME bash`

`docker exec -it react_frontend bash`

docker-compose down
docker-compose up --build
