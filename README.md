# Northwind Web App

The existing `index.php` interface is kept in place. Its CRUD API reads and writes the Northwind tables supplied by `dbNorthwind.sql`.

## Railway setup

1. Add a Railway MySQL service to the project and make its variables available to the web service (`MYSQLHOST`, `MYSQLPORT`, `MYSQLUSER`, `MYSQLPASSWORD`, and `MYSQLDATABASE`). A MySQL `DATABASE_URL` or `MYSQL_URL` is also supported.
2. Deploy this repository with the included `Dockerfile` and `railway.json`.
3. On the first request, the app imports `dbNorthwind.sql` into the configured database if `tb_products` is not present. The supplied dump includes the stock column used by the page.
4. Check `/health.php` for database connectivity and imported row counts.

Do not commit database credentials. Set them as Railway service variables.

## Local setup

Create the MySQL database, import `dbNorthwind.sql`, then set the variables shown in `.env.example` in your local PHP environment. PHP PDO MySQL is required.
