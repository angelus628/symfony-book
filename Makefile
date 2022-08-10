SHELL := /bin/sh

tests:
	symfony console doctrine:database:drop --force --env=test || true
	symfony console doctrine:database:create --env=test
	symfony console doctrine:schema:create -n --env=test
	symfony console doctrine:fixtures:load -n --env=test
	symfony php bin/phpunit $@
.PHONY: tests

consume:
	symfony run -d --watch=config,src,templates,vendor symfony console messenger:consume async
.PHONY: consume

yarn-watch:
	symfony run -d yarn dev --watch
.PHONY: yarn-watch
