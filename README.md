<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>

<h1>🌟 Cinéphoria</h1>

<h2>📝 Description</h2>
<p>
    Cinéphoria est un projet réalisé durant mon année de Bachelor au sein de l'école Studi. C'est une application web qui permet de réserver des séances de cinéma.
</p>

<h2>🚀 Prérequis</h2>
<p>Assurez-vous d'avoir les éléments suivants installés :</p>
<ul>
    <li>🐳 Docker</li>
    <li>🧰 Composer</li>
    <li>🐘 PHP</li>
    <li>📦 Node.js et npm</li>
    <li>🌐 Symfony CLI</li>
    <li>🔐 OpenSSL</li> <!-- Ajout d'OpenSSL -->
</ul>

<h2>⚙️ Instructions de configuration</h2>
<p>Suivez ces étapes pour configurer et exécuter le projet.</p>

<h3>🖥️ Installation des dépendances</h3>

<h4>Pour Linux</h4>
<ol>
    <li>Installer Symfony CLI :
        <pre><code>curl -sS https://get.symfony.com/cli/installer | bash
export PATH="$HOME/.symfony/bin:$PATH"
        </code></pre>
    </li>
    <li>Installer Node.js et npm : Suivez les instructions sur le site officiel de Node.js pour installer la version appropriée pour votre système.</li>
</ol>

<h4>Pour Windows</h4>
<ol>
    <li>Installer Symfony CLI : Téléchargez et exécutez l'installateur depuis <a href="https://symfony.com/download">Symfony CLI</a>.</li>
    <li>Installer Node.js et npm : Téléchargez et installez depuis le <a href="https://nodejs.org">site officiel de Node.js</a>.</li>
</ol>

<h3>🖥️ Symfony</h3>
<ol>
    <li>Démarrer le conteneur de la base de données Docker :
        <pre><code>docker-compose up -d database</code></pre>
    </li>
    <li>Installer les dépendances Composer :
        <pre><code>composer install</code></pre>
    </li>
    <li>Installer les dépendances npm :
        <pre><code>npm install</code></pre>
    </li>
    <li>Créer et exécuter les migrations de la base de données :
        <pre><code>php bin/console make:migration
php bin/console doctrine:migrations:migrate
        </code></pre>
    </li>
    <li>Créer le fichier `.env.local` à la racine du projet et y ajouter les variables d'environnement nécessaires, notamment celles pour la connexion à MySQL et les clés JWT.</li>
    <li>Ajouter l'URL pour la connexion MySQL dans le fichier `.env.local` :
        <pre><code>DATABASE_URL="mysql://cinephoria:cinephoriapassword@127.0.0.1:3306/cinephoriaproject"
        </code></pre>
    </li>
    <li>Générer les clés JWT (publique et privée) avec OpenSSL :
        <pre><code>mkdir -p config/jwt
openssl genpkey -algorithm RSA -out config/jwt/private.pem -aes256
openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout
        </code></pre>
    </li>
    <li>Modifier le fichier `.env.local` pour inclure les variables suivantes :
        <pre><code>JWT_PASSPHRASE=VOTREPASSPHRASE
JWT_PRIVATE_KEY_PATH=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY_PATH=%kernel.project_dir%/config/jwt/public.pem
JWT_EXPIRATION_TIME=3600
        </code></pre>
    </li>
    <li>Démarrer le serveur Symfony : Ouvrez une nouvelle fenêtre/onglet de terminal et exécutez :
        <pre><code>npm run build
npm run watch
symfony serve</code></pre>
    </li>
</ol>

<h2>🛠️ Dépannage</h2>
<p>Assurez-vous que les ports utilisés par Docker et le serveur Symfony ne sont pas bloqués par des pare-feux ou d'autres services.</p>

<hr>

<p>Avec ces instructions, vous devriez être en mesure de configurer et d'exécuter correctement l'application Cinephoria. Bonne chance !</p>

</body>
</html>
