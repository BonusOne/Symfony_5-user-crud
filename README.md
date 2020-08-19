# Symfony 5 - user CRUD

Symfony 5 - user CRUD !<br /> 
With Web and API panel

PHP >= 7.4.6

## How to run
1. Clone from Repo
2. ```.env``` change swift mailer and Database user/pass
3. ```composer install```
4. ```php bin/console doctrine:migrations:migrate```
5. ```php bin/console doctrine:fixtures:load``` 
<br />to load user Fixtures => login: test | pass: qwerty

vHost
```
<VirtualHost *:80>
   	DocumentRoot ".../symfony5-crud/public"
   	ServerName symfony5-crud.loc
   	ServerAlias www.symfony5-crud.loc
   	ServerAdmin webmaster@symfony5-crud.loc
   	ErrorLog "logs/symfony5-crud.loc-error.log"
   	CustomLog "logs/symfony5-crud.loc-access.log" common
   	SetEnv APPLICATION_ENV development
   	<Directory ".../symfony5-crud/public">
   		Order allow,deny
   		Allow from all
   	</Directory>
   </VirtualHost>
```

Apache need .htaccess
```
RewriteEngine on
 
 # if a directory or a file exists, use it directly
 RewriteCond %{REQUEST_FILENAME} !-f
 RewriteCond %{REQUEST_FILENAME} !-d
 
 # otherwise forward it to index.php
 RewriteRule . index.php
```

to check route: ```php bin/console debug:route```


### API login:
1. POST `username` and `password` to `/api/login`
2. If success then give response eg.:
```json
{
    "Success": true,
    "data": {
        "Success": true,
        "targetPath": "/api/dashboard",
        "user": {
            "id": 1,
            "username": "test",
            "first_name": "Test",
            "last_name": "Testowy",
            "email": "testowy@pawelliwocha.com",
            "type": 0,
            "position": "Super Admin",
            "avatar": "noset.png",
            "roles": [
                "ROLE_SUPER_ADMIN"
            ]
        },
        "token":  "57d6ce373d7ea4d70823ad2bebe6fd651fba6c412a53ed38424694ece2858be4"
    }
}
```
if username, password or something is wrong, then give response eg.:
```json
{
    "Success": false,
    "data": "Username could not be found"
}
```
Next if You post for something, you must add to POST
```
"token" => "57d6ce373d7ea4d70823ad2bebe6fd651fba6c412a53ed38424694ece2858be4"
```


# E-mail REPORT errors

If you want get e-mail report errors, add in `.env` config MAILER and in `config/packages/dev` and `/prod` in monolog.yaml write your email address in `swif`