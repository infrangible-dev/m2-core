// noinspection JSUnresolvedFunction,JSCheckFunctionSignatures,JSUnusedGlobalSymbols,JSUnresolvedVariable

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

define([
    'jquery',
    'Magento_Ui/js/modal/alert',
    'mage/translate',
    'jquery/ui'
], function ($, alert, $__) {
    'use strict';

    // $.widget() returns the constructor, which is $.infrangible.ajaxButton
    return $.widget('infrangible.ajaxButton', {
        options: {
            formKey: '',
            buttonId: '',
            ajaxUrl: '',
            dataHtmlIds: ''
        },
        _create: function () {
            const self = this;

            $('#' + this.options.buttonId).on('click', function (event) {
                event.preventDefault();
                self._ajaxRequest(self._getData());
            });
        },
        _ajaxRequest: function (data) {
            $.ajax({
                url: this.options.ajaxUrl,
                dataType: 'json',
                data: data,
                showLoader: true,
                success: this._showResult.bind(this),
                error: function (request, error, message) {
                    alert({
                        title: $__('Error'),
                        content: error + ': ' + message
                    });
                }
            });
        },

        /**
         * Shows the message of the result: on success only if there is one, on errors always.
         *
         * @param {{success: (boolean|undefined), error: (boolean|undefined), message: (string|undefined)}} result
         */
        _showResult: function (result) {
            let title;

            if ('success' in result) {
                if (result.success && !result.message) {
                    return;
                }

                title = result.success ? $__('Success') : $__('Error');
            } else if (result.error) {
                title = $__('Error');
            } else {
                return;
            }

            alert(result.message ? {title: title, content: result.message} : {title: title});
        },
        _getData: function () {
            const data = {
                form_key: this.options.formKey
            };

            if (this.options.dataHtmlIds) {
                const dataHtmlIds = this.options.dataHtmlIds.split(',');

                dataHtmlIds.forEach(function (dataHtmlId) {
                    const dataHtmlElement = $('#' + dataHtmlId);

                    if (dataHtmlElement.length > 0) {
                        if (dataHtmlElement.attr('type') === 'password') {
                            dataHtmlElement.prop('type', 'text');
                            data[dataHtmlId] = dataHtmlElement.val();
                            dataHtmlElement.prop('type', 'password');
                        } else {
                            data[dataHtmlId] = dataHtmlElement.val();
                        }
                    }
                });
            }
            return data;
        }
    });
});
