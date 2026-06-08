#
# Copyright Magmodules.eu. All rights reserved.
# See COPYING.txt for license details.
#

set -e
set -x

# Check if $HYVA_SSH_PRIVATE_KEY is set
if [ -z "$HYVA_SSH_PRIVATE_KEY" ]; then
    echo "Variable \$HYVA_SSH_PRIVATE_KEY is not set"
    exit 1
fi

eval `ssh-agent -s`
mkdir -p ~/.ssh/ && touch ~/.ssh/known_hosts
echo "$HYVA_SSH_PRIVATE_KEY" | ssh-add -
ssh-keyscan -t rsa gitlab.hyva.io >> ~/.ssh/known_hosts

composer config repositories.hyva-themes/magento2-theme-module git git@gitlab.hyva.io:hyva-themes/magento2-theme-module.git
composer config repositories.hyva-themes/magento2-reset-theme git git@gitlab.hyva.io:hyva-themes/magento2-reset-theme.git
composer config repositories.hyva-themes/magento2-email-module git git@gitlab.hyva.io:hyva-themes/magento2-email-module.git
composer config repositories.hyva-themes/magento2-default-theme git git@gitlab.hyva.io:hyva-themes/magento2-default-theme.git
composer config repositories.hyva-themes/magento2-default-theme-csp git git@gitlab.hyva.io:hyva-themes/magento2-default-theme-csp.git
composer config repositories.hyva-themes/magento2-compat-module-fallback git git@gitlab.hyva.io:hyva-themes/magento2-compat-module-fallback.git
composer config repositories.hyva-themes/magento2-order-cancellation-webapi git git@gitlab.hyva.io:hyva-themes/magento2-order-cancellation-webapi.git
composer config repositories.hyva-themes/hyva-checkout git git@gitlab.hyva.io:hyva-checkout/checkout.git

jq '.replace |= (. // {} | .["hyva-themes/magento2-mollie-theme-bundle"] = "*")' composer.json > composer.tmp && mv composer.tmp composer.json

composer require hyva-themes/magento2-default-theme-csp hyva-themes/magento2-hyva-checkout

bin/magento setup:upgrade --keep-generated

# Activate the Hyvä Default CSP theme. The numeric theme_id shifts between Hyvä
# releases, so resolve it by code instead of hardcoding a magic number.
csp_theme_id=$(magerun2 --no-ansi db:query "SELECT theme_id FROM theme WHERE area = 'frontend' AND code = 'Hyva/default-csp' LIMIT 1" 2>/dev/null | grep -xE '[0-9]+' | head -n1)
magerun2 config:store:set design/theme/theme_id "$csp_theme_id" --scope=default --scope-id=0
magerun2 config:store:set design/theme/theme_id "$csp_theme_id" --scope=stores --scope-id=1

# Enable CSP
magerun2 config:store:set system/default/csp/policies/storefront/scripts/inline 0
magerun2 config:store:set system/default/csp/policies/storefront/scripts/eval 0
magerun2 config:store:set system/default/csp/mode/storefront/report_only 0

bin/magento config:set general/region/display_all 0
bin/magento config:set hyva_themes_checkout/general/checkout default

bin/magento hyva:config:generate

npm --prefix vendor/hyva-themes/magento2-default-theme-csp/web/tailwind/ ci
npm --prefix vendor/hyva-themes/magento2-default-theme-csp/web/tailwind/ run build-prod
