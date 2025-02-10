.PHONY: dev prod test logs help

# Default target
.DEFAULT_GOAL := help

help: ## Display this help message
	@echo "Usage: make [target]"
	@echo ""
	@echo "Targets:"
	@awk '/^[a-zA-Z_-]+:.*?## .*$$/ {printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

dev: ## Start development environment
	CI_ENVIRONMENT=development APP_ENV=dev docker compose up -d

prod: ## Start production environment
	CI_ENVIRONMENT=production APP_ENV=prod docker compose up -d

test: ## Run PHPUnit tests
	docker exec php-fpm vendor/bin/phpunit

logs: ## View coaster information logs
	docker logs -f console-monitor

stop: ## Stop all containers
	docker compose down

restart: stop ## Restart containers
	docker compose up -d