.PHONY: build
build: update
	@echo "Building develop into a single file..."
	php maid app:build maid --build-version=dev-master --ansi

.PHONY: build-release
build-release: update
	@echo "Building production into a single file..."
	php maid app:build maid --build-version="$(MAID_BUILD_VERSION)" --ansi

.PHONY: publish
publish: build
	@echo "Publishing to GitHub via dev-master..."
	git commit -am 'build: new developer version'
	git push

.PHONY: release
release: build-release
	@echo "Publishing to GitHub via new version..."
	git commit -am 'build: release version'
	gh release create "$(MAID_BUILD_VERSION)" --title "Release $(MAID_BUILD_VERSION)" --notes "Release $(MAID_BUILD_VERSION)"

.PHONY: update
update:
	@echo "Updating local composer packages..."
	composer update --ansi

.PHONY: install
install:
	@echo "Installing develop globally..."
	composer global require ghostzero/maid:dev-master --ansi
