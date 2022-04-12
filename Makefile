##
## Variables
## -----
##

SYMFONY=bin/console --ansi

##
## Project
## -----
##

.PHONY: install
install: .git/hooks/pre-commit vendor ## Install the project

.PHONY: clean
clean: ## Remove generated files
	@rm -rf vendor var/*

##
## Test
## -----
##

PHPUNIT_DISABLE_CACHE := $(if $(CI),--do-not-cache-result)
.PHONY: test
test: vendor ## Execute the test
	@APP_DEBUG=$(PHPUNIT_APP_DEBUG) vendor/bin/phpunit --colors=always $(PHPUNIT_DISABLE_CACHE)

##
## Quality assurance
## -----------------
##

.PHONY: security
security: ## Check security of dependencies
	@composer update --ansi --dry-run roave/security-advisories

PHPSTAN_LEVEL := $(if $(LEVEL),-l $(LEVEL))
PHPSTAN_NO_PROGRESS := $(if $(CI),--no-progress)
.PHONY: phpstan
phpstan: vendor ## Execute phpstan, you can pass level with: LEVEL=2
	@vendor/bin/phpstan analyse $(PHPSTAN_LEVEL) $(PHPSTAN_NO_PROGRESS) --ansi

.PHONY: composer-validate
composer-validate: ## Validate the composer.json
	@composer validate --no-check-all --strict --ansi

.PHONY: ecs
ecs: vendor ## Check code against coding standard
	@vendor/bin/ecs check --ansi --no-progress-bar

.PHONY: check-platform-reqs
check-platform-reqs: ## Check platform requirements
	@composer check-platform-reqs

##
## rules based on files
## -----
##

composer.lock: composer.json
	@composer update --lock --no-scripts --no-interaction
	@touch -c $@

vendor: composer.lock
	@composer install --ansi
	@touch -c $@

.git/hooks/pre-commit: requirements.txt
ifndef CI
	pip3 install -r requirements.txt
	pre-commit install
endif

##
## CI
## -----
##

ci: install check-platform-reqs security composer-validate ecs phpstan test

##
## Makefile
## -----
##

.DEFAULT_GOAL := help
default: help

.PHONY: help
help:
	@grep -E '^[a-zA-Z0-9_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'
