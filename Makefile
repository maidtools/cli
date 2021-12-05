.PHONY: build
build: update
	@echo "Building..."
	php maid app:build maid --build-version=dev-master

.PHONY: publish
publish: build
	@echo "Publishing..."
	git commit -am 'build: new dev version'
	git push

.PHONY: update
update:
	@echo "Updating..."
	composer update

.PHONY: install
install:
	@echo "Installing..."
	composer global require ghostzero/maid:dev-master
