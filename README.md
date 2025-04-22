# Mon Projet

# Rebuild the Docker image
`docker-compose build`

# Recreate the containers
`docker-compose up -d`

# Restart docker
`docker restart $(docker ps -q)`

# Go on a container
docker exec -it CONTAINER_NAME bash

docker exec -it symfony bash

docker-compose down
docker-compose up --build


php bin/console make:entity

php bin/console make:migration
cphp bin/console doctrine:database:create
php bin/console doctrine:schema:update --force
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load

php bin/console cache:clear

php bin/console doctrine:fixtures:load --group=main-fixtures
php bin/console doctrine:fixtures:load --group=main-fixtures

php bin/console doctrine:fixtures:load --group=RandomPostsFixtures
 php bin/console doctrine:fixtures:load --append --group=RandomPostsFixtures
php bin/console doctrine:fixtures:load --group=messaging --append

 fake change v4