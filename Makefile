ROOT_DIR := $(shell dirname $(realpath $(lastword $(MAKEFILE_LIST))))

default: php_check

php_check:
	@(echo "-> Start check php")
	php -d memory_limit=256m ./vendor/bin/phpstan analyse -l 4 src examples
	php -d memory_limit=256m ./vendor/bin/phplint
	@(echo "-> Start check code standart")
	php -d memory_limit=256m ./vendor/bin/phpcs --standard=PSR2 src examples

php_fix:
	@(echo "-> Start fix php")
	php -d memory_limit=256m ./vendor/bin/php-cs-fixer fix
	php -d memory_limit=256m ./vendor/bin/phpcbf --standard=PSR2 src examples


.PHONY: php_check php_fix
