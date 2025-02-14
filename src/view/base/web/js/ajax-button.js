// noinspection JSUnresolvedFunction,JSCheckFunctionSignatures,JSUnusedGlobalSymbols,JSUnresolvedVariable

/* jshint esversion: 6 */
/* global console */
/* global define */
/* global require */

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

define([
    'jquery',
    'Magento_Ui/js/modal/alert',
    'mage/translate',
    'jquery/ui'
], function ($, alert, $__) {
    'use strict';

    $.widget('infrangible.ajaxButton', {
        options: {
            formKey: '',
            buttonId: '',
            ajaxUrl: '',
            dataHtmlIds: ''
        },
        _create: function () {
            var self = this;
            $('#' + this.options.buttonId).click(function (event) {
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
                success: function (result) {
                    if ('success' in result) {
                        if (result.success) {
                            if (result.message) {
                                alert({
                                    title: $__('Success'),
                                    content: result.message
                                });
                            }
                        } else {
                            if (result.message) {
                                alert({
                                    title: $__('Error'),
                                    content: result.message
                                });
                            } else {
                                alert({
                                    title: $__('Error')
                                });
                            }
                        }
                    } else if (result.error) {
                        if (result.message) {
                            alert({
                                title: result.error ? $__('Error') : $__('Success'),
                                content: result.message
                            });
                        } else {
                            alert({
                                title: $__('Error')
                            });
                        }
                    }
                },
                error: function (request, error, message) {
                    alert({
                        title: $__('Error'),
                        content: error + ': ' + message
                    });
                }
            });
        },
        _getData: function () {
            var data = {
                form_key: this.options.formKey
            };
            if (this.options.dataHtmlIds) {
                var dataHtmlIds = this.options.dataHtmlIds.split(',');
                dataHtmlIds.forEach(function (dataHtmlId) {
                    var dataHtmlElement = $('#' + dataHtmlId);
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

    return $.infrangible.ajaxButton;
});
