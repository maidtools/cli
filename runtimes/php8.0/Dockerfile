FROM ubuntu:20.04

LABEL maintainer="René Preuß"

ARG WWWGROUP=33
ARG CICD_GIT_COMMIT

WORKDIR /var/www/html

RUN echo $CICD_GIT_COMMIT > /var/www/html/git_hash

ENV DEBIAN_FRONTEND noninteractive
ENV TZ=UTC

RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

RUN apt-get update \
    && apt-get install -y software-properties-common gosu curl ca-certificates zip unzip git supervisor sqlite3 \
    && add-apt-repository ppa:ondrej/php \
    && apt-get install -y php8.0-cli \
        php8.0-curl php8.0-mbstring php8.0-xml php8.0-zip php8.0-bcmath php8.0-intl php8.0-readline \
        php8.0-mysql php8.0-sqlite3 \
        php8.0-redis php8.0-swoole \
    && php -r "readfile('http://getcomposer.org/installer');" | php -- --install-dir=/usr/bin/ --filename=composer \
    && curl -sL https://deb.nodesource.com/setup_15.x | bash - \
    && apt-get install -y nodejs \
    && apt-get -y autoremove \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

RUN setcap "cap_net_bind_service=+ep" /usr/bin/php8.0

RUN groupadd --force -g $WWWGROUP maid
RUN useradd -ms /bin/bash --no-user-group -g $WWWGROUP -u 1337 maid

COPY ./runtimes/php8.0/start-container /usr/local/bin/start-container
COPY ./runtimes/php8.0/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY ./runtimes/php8.0/php.ini /etc/php/8.0/cli/conf.d/99-maid.ini
RUN chmod +x /usr/local/bin/start-container

EXPOSE 8000

ENTRYPOINT ["start-container"]
