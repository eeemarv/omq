# omd voting web app

## Installation

### Dokku

See the [Dokku installation guide](http://dokku.viewdocs.io/dokku/getting-started/installation).

* Install Dokku on a VPS with a fresh Ubuntu 16.04,

* Create app, install postgres and redis plugins and bind them to to the app (see Dokku guide).

Postgres plugin  [https://github.com/dokku/dokku-postgres]

Redis plugin [https://github.com/dokku/dokku-redis]

### Postgres

Create extention uuid-ossp (for generating random uuids)

```shell
dokku postgres:connect dbname
```

```sql
create extension if not exists "uuid-ossp";
```

Create schema xdb
```sql
create schema xdb;
```
Create events table in xdb schema. See [./service/xdb.php]

### AWS S3
Create a IAM user on AWS with access only to S3. Then create a bucket in your region for images to be uploaded.

Set env variable S3_IMG to the url of the bucket:
```shell
dokku config:set appname S3_IMG=
```

### Email

Set env  SMTP mailserver (e.i. Amazon Simple Email Service)
* SMTP_HOST
* SMTP_PORT
* SMTP_PASSWORD
* SMTP_USERNAME

(`dokku config:set appname SMTP_PORT=xxxx`)

#### From mail addresses

* MAIL_NOREPLY_ADDRESS

This address should be set up for DKIM in the mailserver.





