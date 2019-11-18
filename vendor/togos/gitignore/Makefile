default: run-unit-tests

.PHONY: run-unit-tests

composer.lock: | composer.json
	composer install

vendor: composer.lock
	composer install
	touch "$@"

run-unit-tests: vendor
	vendor/bin/phpsimplertest --bootstrap vendor/autoload.php src/test/ --colorful-output
