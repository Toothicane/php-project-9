PORT ?= 8000

start:
	PHP_CLI_SERVER_WORKERS=5 php -S 0.0.0.0:$(PORT) -t public

setup:
	composer install
	npx @tailwindcss/cli -i ./app.css -o ./public/styles.css

lint:
	composer exec --verbose phpcs -- --standard=PSR12 public