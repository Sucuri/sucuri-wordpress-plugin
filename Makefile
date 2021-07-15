.PHONY: e2e
e2e:
	npx wp-env start
	npx wp-env clean all
	npx wp-env run tests-cli "wp user create sucuri sucuri@sucuri.net --role=author --user_pass=password"
	npx wp-env run tests-cli "wp plugin install akismet --activate"
	npx wp-env run tests-cli "touch .htaccess"
	npx cypress run

.PHONY: unit-test
unit-test:
	./vendor/bin/phpunit
