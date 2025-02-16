How To Install
==============

1. Create a deployment daemon according to the [instructions](https://cloud-py-api.github.io/app_api/CreationOfDeployDaemon.html#create-deploy-daemon) of the AppPI
2. php occ app_api:app:register misp_bot --force-scopes \
--info-xml https://raw.githubusercontent.com/PatriceKast/NextCloud-Bot-MISP-IoC-Importer/appinfo/info.xml

    to deploy and install this ExApp.
