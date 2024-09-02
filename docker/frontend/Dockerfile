# Utiliser une image de base Node.js
FROM node:18

# Définir le répertoire de travail
WORKDIR /app

ENV NODE_OPTIONS=--openssl-legacy-provider


# Exposer le port que le serveur web servira
EXPOSE 3000

# Lancer un shell dans le conteneur pour que vous puissiez interagir avec
CMD ["sh", "-c", "while :; do sleep 1; done", "npm start"]
