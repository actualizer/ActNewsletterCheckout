import template from './act-newsletter-checkout-info.html.twig';
import './act-newsletter-checkout-info.scss';

const { Component } = Shopware;

const HOW_TO_ITEM_COUNT = 5;

Component.register('act-newsletter-checkout-info', {
    template,

    computed: {
        howToItems() {
            return Array.from(
                { length: HOW_TO_ITEM_COUNT },
                (_, index) => `act-newsletter-checkout.settings.info.howTo.item${index + 1}`,
            );
        },
    },
});
