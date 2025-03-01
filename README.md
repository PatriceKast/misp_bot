## Warning: Development Version! Not production ready!

# NextCloud Talk Bot for IPv4 Address Submission to MISP

## Overview

This project provides a NextCloud Talk bot that processes messages containing IPv4 addresses, verifies if they belong to public IP ranges, and submits them to a [MISP](https://www.misp-project.org/) (Malware Information Sharing Platform) instance. This can be useful for monitoring and sharing potential threats in a collaborative threat intelligence network.

## Features

- Listens for IPv4 addresses in NextCloud Talk messages
- Validates whether the IPs are public (non-reserved) IPv4 addresses
- Submits detected public IPs to a configured MISP instance
- Provides logging for tracking submissions
- Secure API integration with MISP
- Configurable settings for bot behavior and API endpoints

## Development
The following section shall provide support during development of the bot.

### Installation (Manual)
Clone this repository to the following data directory of NextCloud:
```
nextcloud/custom_apps/
```

Then in case of a dockerized nextcloud, use the following command to install it:
```
docker exec -it --user 33 nextcloud-docker-app-1 php occ app:enable misp_bot
```

To list all installed Talk Bots, use the following command:
```
docker exec -it --user 33 nextcloud-docker-app-1 php occ talk:bot:list
```

### Revert Installation
To revert performed DB migrations of the Bot for testing, use the following SQL queries:
```
DELETE FROM oc_migrations WHERE app LIKE 'misp_bot';
DELETE FROM oc_talk_bots_server WHERE name LIKE 'MISP IoC Importer Bot';
```
To disable the installed bot, use:
```
docker exec -it --user 33 nextcloud-docker-app-1 php occ app:enable misp_bot
```