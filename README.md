# EBANX Payment Gateway for Magento 1.x

This plugin enables you to integrate your Magento 1.x store with the EBANX payment gateway.

## Getting Started with Docker

**To install this plugin, you need to install Docker on your machine.**

Firstlly, run the command **once**:
```
chmod +x $(pwd)/scripts/start.sh && $(pwd)/scripts/start.sh
```

This will install and run the project with Docker.

After that, you can use the command to start:
```
docker-compose up
```

To access the project, go to http://localhost.

The admin can be acessed by http://localhost/admin using the credentials `ebanx` usernamae and `ebanx123` password.

#### Issues

If you have getting trouble, maybe you can disable your built-in apache if you are using mac.

```
sudo launchctl unload -w /System/Library/LaunchDaemons/org.apache.httpd.plist
```

Try that ;)