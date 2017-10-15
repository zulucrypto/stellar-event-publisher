FROM php:7.1

RUN echo "Installing additional requirements..." \
    && docker-php-ext-install -j$(nproc) \
        pcntl \
    && echo "[Done installing additional requirements]"

RUN echo "Creating directories..." \
    && mkdir /project \
    && mkdir /project/var \
    && mkdir /project/var/data \
    && mkdir /project/var/config \
    && echo "[Done creating directories]"

#
# Copy basic directory structure
COPY app/ /project/app/
COPY bin/ /project/bin/
COPY src/ /project/src/
COPY vendor/ /project/vendor/
COPY web/ /project/web/

#
# Add in docker configuration files
COPY docker/parameters.yml /project/app/config/parameters.yml

#
# Create initial database schema
RUN echo "Initializing project..." \
    && /project/bin/console doctrine:schema:create \
    && echo "[Done initializing project]"

# Configure the amount of data logged
# Valid values: QUIET, NORMAL, VERBOSE, DEBUG
ENV SEP_LOG_VERBOSITY="NORMAL"

# Specify a config file location
# This can be a config file, a URL to a config file, or a directory of config files
ENV SEP_CONFIG_FILE=""

WORKDIR /project
ENTRYPOINT [ "/project/bin/console" ]
CMD [ "sep:supervisor" ]