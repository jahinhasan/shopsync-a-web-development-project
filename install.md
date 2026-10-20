Amar project ta dekhe nilam — native (Docker chara) chalate nicher step gulo follow koro. Docker o ache kintu oi compose file ta host DB chara kaj kore na, tai new PC te native setup tai simplest.

1. System packages (Arch)

sudo pacman -S --needed php composer nodejs npm mariadb unzip git
Erbapor /etc/php/php.ini khule ei line ta uncomment koro (semicolon baad):


extension=pdo_mysql
Check: php -v 8.3+ hote hobe ar node -v 20+.

2. MariaDB setup + backup restore

sudo mariadb-install-db --user=mysql --basedir=/usr --datadir=/var/lib/mysql
sudo systemctl enable --now mariadb
sudo mariadb

CREATE DATABASE smart_shoping CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'shopsync'@'localhost' IDENTIFIED BY 'tomar_password';
GRANT ALL ON smart_shoping.* TO 'shopsync'@'localhost';
FLUSH PRIVILEGES;
EXIT;
Ekhon tomar backup file ta restore koro:


mariadb -u shopsync -p smart_shoping < /path/to/backup.sql
Important: project folder copy korle .env o copy hoye geche (git clone korle hoy na). .env e DB_DATABASE=smart_shoping, DB_USERNAME, DB_PASSWORD already ache — upore je user/password banaila tar sathe mile dekho. Na mile hole .env e edit koro. .env na thakle: cp .env.example .env && php artisan key:generate. .env thakle key:generate lagbe na, key already ache.

3. Project setup + run

cd ~/shopsync
composer install
npm install
php artisan migrate --graceful   # backup e sessions/cache tables thakle no-op, na thakle baniye dibe
npm run build
php artisan storage:link
php artisan config:clear
php artisan serve
Erbapor browser e http://localhost:8000 — tomar backup er shob data (users, products, orders) pawa jabe, demo seed korar dorkar nei.

4. Real-time chat (optional)
Jodi chat ta WebSocket soho chao, .env e BROADCAST_CONNECTION=reverb + REVERB_* set koro (README te exact values ache), npm run build abar chalao, tarpor second terminal e:


php artisan reverb:start
Kono step e error asle output ta paste koro, dekhe boli.