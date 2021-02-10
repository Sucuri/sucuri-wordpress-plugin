.PHONY: e2e

e2e:
	npx wp-env start
	npx wp-env clean all
	npx wp-env run cli "wp user create sucuri sucuri@sucuri.net --role=author --user_pass=password"
	npx cypress run