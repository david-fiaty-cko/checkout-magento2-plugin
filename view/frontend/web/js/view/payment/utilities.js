define([
        'jquery',
        'CheckoutCom_Magento2/js/view/payment/config-loader',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/checkout-data',
        'mage/url',
        'Magento_Checkout/js/action/redirect-on-success',
        'Magento_Checkout/js/model/full-screen-loader',
        'mage/cookies'
    ],
    function ($, Config, Quote, CheckoutData, Url, RedirectOnSuccessAction, FullScreenLoader) {
        'use strict';

        const KEY_CONFIG = 'checkoutcom_configuration';
        const KEY_DATA = 'checkoutcom_data';

        return {

            /**
             * Gets a field value.
             *
             * @param      {string}  methodId The method id
             * @param      {string}  field    The field
             * @return     {mixed}            The value
             */
            getValue: function(methodId, field) {
                var val = null;
                if (Config.hasOwnProperty(methodId) && Config[methodId].hasOwnProperty(field)) {
                    val = Config[methodId][field]
                }
                else if (Config.hasOwnProperty(KEY_CONFIG) && Config[KEY_CONFIG].hasOwnProperty(field)) {
                    val = Config[KEY_CONFIG][field];
                }

                return val;
            },

            getStoreName: function() {
                return Config[KEY_CONFIG].store.name;
            },

            getQuoteValue: function() {
                return Config[KEY_DATA].quote.value;
            },

            getQuoteCurrency: function() {
                return Config[KEY_DATA].quote.currency;
            },

            userHasCards: function() {
                return Config[KEY_DATA].user.hasCards;
            },

            /**
             * Builds the controller URL.
             *
             * @param      {string}  path  The path
             * @return     {string}
             */
            getUrl: function(path) {
                return Url.build('checkout_com/' + path);
            },

            /**
             * Customer name.
             *
             * @param      {bool} return in object format.
             * @return     {mixed}  The billing address.
             */
            getCustomerName: function(obj = false) {
                var billingAddress = Quote.billingAddress(),
                    name = {
                        first_name: billingAddress.firstname,
                        last_name: billingAddress.lastname
                    };

                if (!obj) {
                    name = name.first_name + ' ' + name.last_name
                }

                return name;
            },

            /**
             * Billing address.
             *
             * @return     {object}  The billing address.
             */
            getBillingAddress: function() {
                return Quote.billingAddress();
            },

            /**
             * @returns {string}
             */
            getEmail: function () {
                return window.checkoutConfig.customerData.email || Quote.guestEmail || CheckoutData.getValidatedEmailValue();
            },

            /**
             * @returns {void}
             */
            setEmail: function() {
                $.cookie(
                    this.getValue('email_cookie_name'),
                    this.getEmail()
                );
            },

            /**
             * @returns {object}
             */
            getPhone: function () {
                var billingAddress = Quote.billingAddress();

                return {
                    number: billingAddress.telephone
                };
            },

            /**
             * Methods
             */

            /**
             * Show a message
             */
            showMessage: function (type, message) {
                this.clearMessages();
                var messageContainer = $('.message');
                messageContainer.addClass('message-' + type + ' ' + type);
                messageContainer.append('<div>' + message + '</div>');
                messageContainer.show();
            },

            /**
             * Clear all messages
             */
            clearMessages: function () {
                var messageContainer = $('.message');
                messageContainer.hide();
                messageContainer.empty();
            },

            isUrl: function (str) {
                var pattern = /^(?:\w+:)?\/\/([^\s\.]+\.\S{2}|localhost[\:?\d]*)\S*$/;
                return pattern.test(str);
            },

            allowPlaceOrder: function (buttonId, yesNo) {
                $('#' + buttonId).prop('disabled', !yesNo);
            },

            /**
             * HTTP handlers
             */

            /**
             * Place a new order.
             * @returns {void}
             */
            placeOrder: function (payload) {
                var self = this;

                // Start the loader
                FullScreenLoader.startLoader();

                // Send the request
                $.ajax({
                    type: 'POST',
                    url: self.getUrl('payment/placeorder'),
                    data: payload,
                    showLoader: true,
                    success: function (data) {
                        if (!data.success) {
                            FullScreenLoader.stopLoader();
                            self.showMessage('error', data.message);
                        }
                        else if (data.success && data.url) {
                            // Handle 3DS redirection
                            window.location.href = data.url
                        }
                        else {
                            // Normal redirection
                            RedirectOnSuccessAction.execute();
                        }
                    },
                    error: function (request, status, error) {
                        self.showMessage('error', error);
                        FullScreenLoader.stopLoader();
                    }
                });
            }
        };
    }
);
