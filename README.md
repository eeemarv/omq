# omd voting web app

* [#installation](Installation)
* [#usage](Usage)

## Installation

### Dokku

See the [Dokku installation guide](http://dokku.viewdocs.io/dokku/getting-started/installation).

* Install Dokku on a VPS with a fresh Ubuntu 16.04,

* Create app, install postgres, redis and letsencrypt plugins and bind them to to the app (see Dokku guide).

[https://github.com/dokku/dokku-postgres](Postgres plugin)

[https://github.com/dokku/dokku-redis](Redis plugin)

[https://github.com/dokku/dokku-letsencrypt](Letsencrypt plugin)

### Postgres

Login postgres cli:

```shell
dokku postgres:connect dbname
```

Create schema xdb
```sql
create schema xdb;
```
Create events table in xdb schema. See [service/xdb.php] .

### AWS S3
Create a IAM user on AWS with access only to S3. Then create a bucket in your region for images to be uploaded.

Set env variable S3_IMG to the url of the bucket:
```shell
dokku config:set appname S3_IMG=https://s3.eu-central-1.amazonaws.com/my-bucket
```

### Email

Set these environment variables for the SMTP mailserver (e.i. Amazon Simple Email Service)

```shell
dokku config:set appname SMTP_PORT=587
```

* SMTP_HOST
* SMTP_PORT
* SMTP_PASSWORD
* SMTP_USERNAME

#### From mail addresses

```shell
dokku config:set appname MAIL_NOREPLY_ADDRESS=noreply@my-domain.com
```

* MAIL_NOREPLY_ADDRESS

This address should be set up for DKIM in the mailserver.

## Usage

... info will come soon


