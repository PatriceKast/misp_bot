.DEFAULT_GOAL := help

.PHONY: help
help:
	@echo "Welcome to the MISP Bot. Please use \`make <target>\` where <target> is one of"
	@echo " "
	@echo "  Next commands are only for dev environment with nextcloud-docker-dev!"
	@echo "  They should run from the host you are developing on(with activated venv) and not in the container with Nextcloud!"
	@echo "  "
	@echo "  build-push        build image and upload to ghcr.io"
	@echo "  "
	@echo "  run               install MISPBot for Nextcloud Last"
	@echo "  run28             install MISPBot for Nextcloud 28"
	@echo "  "
	@echo "  For development of this example use PyCharm run configurations. Development is always set for last Nextcloud."
	@echo "  First run 'MISPBot' and then 'make registerXX', after that you can use/debug/develop it and easy test."
	@echo "  "
	@echo "  register          perform registration of running 'MISPBot' into the 'manual_install' deploy daemon."
	@echo "  register28        perform registration of running 'MISPBot' into the 'manual_install' deploy daemon."

.PHONY: build-push
build-push:
	docker login ghcr.io
	docker buildx build --push --platform linux/arm64/v8,linux/amd64 --tag patcas/misp_bot:latest .

.PHONY: run
run:
	docker exec master-nextcloud-1 sudo -u www-data php occ app_api:app:unregister misp_bot --silent --force || true
	docker exec master-nextcloud-1 sudo -u www-data php occ app_api:app:register misp_bot --force-scopes \
		--info-xml https://raw.githubusercontent.com/PatriceKast/NextCloud-Bot-MISP-IoC-Importer/refs/heads/main/appinfo/info.xml

.PHONY: run28
run28:
	docker exec master-stable28-1 sudo -u www-data php occ app_api:app:unregister misp_bot --silent --force || true
	docker exec master-stable28-1 sudo -u www-data php occ app_api:app:register misp_bot --force-scopes \
		--info-xml https://raw.githubusercontent.com/PatriceKast/NextCloud-Bot-MISP-IoC-Importer/refs/heads/main/appinfo/info.xml

.PHONY: register
register:
	docker exec master-nextcloud-1 sudo -u www-data php occ app_api:app:unregister misp_bot --silent --force || true
	docker exec master-nextcloud-1 sudo -u www-data php occ app_api:app:register misp_bot manual_install --json-info \
  "{\"id\":\"misp_bot\",\"name\":\"MISPBot\",\"daemon_config_name\":\"manual_install\",\"version\":\"1.0.0\",\"secret\":\"12345\",\"port\":9032,\"scopes\":[\"TALK\", \"misp_bot\"]}" \
  --force-scopes --wait-finish

.PHONY: register28
register28:
	docker exec master-stable28-1 sudo -u www-data php occ app_api:app:unregister misp_bot --force || true
	docker exec master-stable28-1 sudo -u www-data php occ app_api:app:register misp_bot manual_install --json-info \
  "{\"id\":\"misp_bot\",\"name\":\"MISPBot\",\"daemon_config_name\":\"manual_install\",\"version\":\"1.0.0\",\"secret\":\"12345\",\"port\":9032,\"scopes\":[\"TALK\", \"misp_bot\"]}" \
  --force-scopes --wait-finish
