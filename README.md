Ce projet à été développé avec Symfony, XAMPP et MailHog.

Il est nécessaire d'avoir ces outils pour faire fonctionner le projet.



composer i

créer une copie du fichier .env nommé .env.local et insérer les variables DATABASE_URL et MAILER_DSN avec vos valeurs locales

symfony console doctrine:database:create

symfony console doctrine:migration:migrate

symfony console doctrine:fixture:load