help:
	@echo "Usage: make <command> ";\
	echo "Commands: ";\
	echo "    help:                Shows this help";\
	echo "    install:             Recreate db containers";\
	echo "    uninstall:           Destroy containers ";\
	echo "    phpunit:             Run unit tests ";\

install: container install-postgres restart-web-container composer-update

container:
	docker-compose up -d
	@sleep 10

uninstall: clean-container

reinstall: uninstall install

clean-container:
	docker-compose stop || true

clean-container-all:
	docker-compose down || true

composer-update:
	@docker exec -ti starreddocker_web_1 bash -c \
	    'cd app & composer update'

install-postgres:
	@docker exec -ti starreddocker_web_1 bash -c \
	    'apt-get update && apt-get install php5-pgsql'

restart-web-container:
	docker restart  starreddocker_web_1

phpunit:
	@docker exec -ti starreddocker_web_1 bash -c \
		'vendor/bin/phpunit -c tests'
