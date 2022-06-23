DOCKER_DIR= $(PWD)/docker
PHP_DIR= ${DOCKER_DIR}/php
APACHE_DIR= ${DOCKER_DIR}/apache
DOCKER_COMPOSE_FILE=-f ${DOCKER_DIR}/docker-compose.yml

ifneq ("$(wildcard ${DOCKER_DIR}/.env)","")
    include ${DOCKER_DIR}/.env
endif

help:
	@echo "[init]  - Initialize docker files"
	@echo "[up] - Up containers"
	@echo "[down] - Down containers"
	@echo "[restart] - Restart containers"

init:
	cp ${DOCKER_DIR}/.env.example ${DOCKER_DIR}/.env
	cp ${DOCKER_DIR}/docker-compose.yml.example ${DOCKER_DIR}/docker-compose.yml
	cp ${PHP_DIR}/Dockerfile.example ${PHP_DIR}/Dockerfile
	cp ${PHP_DIR}/php.ini.example ${PHP_DIR}/php.ini
	cp ${APACHE_DIR}/sites-enabled/default.conf.example ${APACHE_DIR}/sites-enabled/default.conf

up:
	@docker-compose ${DOCKER_COMPOSE_FILE} up -d

down:
	@docker-compose ${DOCKER_COMPOSE_FILE} down --remove-orphans

build:
	@docker-compose ${DOCKER_COMPOSE_FILE} build

restart: down up