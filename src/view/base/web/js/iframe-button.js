// noinspection JSUnresolvedFunction,JSCheckFunctionSignatures,JSUnusedGlobalSymbols,JSUnresolvedVariable

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

define([
    'jquery',
    'Magento_Ui/js/modal/modal'
], function ($, modal) {
    'use strict';

    $.widget('infrangible.iframeButton', {
        options: {
            buttonId: '',
            src: '',
            srcDoc: '',
            title: ''
        },
        _create: function () {
            var self = this;
            $('#' + this.options.buttonId).click(function (event) {
                event.preventDefault();
                self._openPopup(self._getData());
            });
        },
        _openPopup: function (data) {
            var modalContent;
            if (data.srcDoc) {
                modalContent = $('<iframe srcdoc=\'' + data.srcDoc + '\'></iframe>');
            } else if (data.src) {
                modalContent = $('<iframe src=\'' + data.src + '\'></iframe>');
            }
            if (modalContent) {
                var modalOptions = {
                    type: 'popup',
                    responsive: true,
                    innerScroll: false,
                    modalClass: 'iframe-button-modal',
                    buttons: []
                };
                if (data.title) {
                    modalOptions['title'] = data.title;
                }
                modal(modalOptions, modalContent);
                modalContent.modal('openModal');
            }
        },
        _getData: function () {
            var data = {};
            if (this.options.srcDoc && this.options.srcDoc != '') {
                data['srcDoc'] = this.options.srcDoc;
            }
            if (this.options.src && this.options.src != '') {
                data['src'] = this.options.src;
            }
            if (this.options.title && this.options.title != '') {
                data['title'] = this.options.title;
            }
            return data;
        }
    });

    return $.infrangible.iframeButton;
});
