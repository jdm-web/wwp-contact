#!/usr/bin/env bash

vendor/bin/phpunit web/app/plugins/wwp-contact/tests/phpunit/tests --bootstrap web/app/plugins/wwp-contact/tests/phpunit/bootstrap.php --log-junit tests/reports/plugins/wwp-contact.xml --colors=auto
