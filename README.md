# Laravel OpenID Connect Server

![](https://img.shields.io/badge/license-AGPL--3.0-green)

This is an OpenID Connect Server written in PHP, built on top of the following repos:
1.  [arietimmerman/openid-connect-server](https://github.com/arietimmerman/openid-connect-server) 
2.  [Laravel Passport](https://github.com/laravel/passport)
3.  [arietimmerman/laravel-openid-connect-server](https://github.com/arietimmerman/laravel-openid-connect-server)

This library is **work in progress**.

## Example

```
docker-compose build
docker-compose up -d
```

Now find your `openid-configuration` at `http://localhost:18124/.well-known/openid-configuration`.
