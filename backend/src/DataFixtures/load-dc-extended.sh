#!/bin/bash

# Charger uniquement les fixtures DC Extended
# Maintenant que getDependencies() retourne un tableau vide, cette fixture sera chargée seule
php bin/console doctrine:fixtures:load --group=dc-extended-fixtures --append

echo "DC Extended Fixtures chargées avec succès sans dépendances!" 