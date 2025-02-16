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