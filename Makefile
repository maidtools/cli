.PHONY: build
build: update
	@echo "Building..."
	php maid app:build maid --build-version=dev-master

.PHONY: build-release
build-release: update
	@echo "Building..."
	php maid app:build maid --build-version="$(MAID_BUILD_VERSION)"

.PHONY: publish
publish: build
	@echo "Publishing..."
	git commit -am 'build: new dev version'
	git push

.PHONY: release
release: build-release
	@echo "Publishing..."
	git commit -am 'build: release version'
	gh release create "$(MAID_BUILD_VERSION)" --title "Release $(MAID_BUILD_VERSION)" --notes "Release $(MAID_BUILD_VERSION)"

.PHONY: update
update:
	@echo "Updating..."
	composer update

.PHONY: install
install:
	@echo "Installing..."
	composer global require ghostzero/maid:dev-master
