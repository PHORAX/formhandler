(function( $ ) {

    $.fn.formhandler = function(options) {

        var defaults = {
            pageID: 0,
            contentID: 0,
            randomID: 0,
            lang: 0,
            ajaxSubmit: false,
            submitButtonSelector: "INPUT[type='submit']",
            autoDisableSubmitButton: false,
            validateFields: '',
            validationStatusClasses: {
                base: 'formhandler-validation-status',
                valid: 'form-valid',
                invalid: 'form-invalid'
            },
            submitStart: function(el) { },
            submitComplete: function(el, data) { },
            submitFinished: function(el, data) { },
            validateStart: function(el) { },
            validateComplete: function(el, data) { },
            validateFinished: function(el, data) { }
        };

        var settings = $.extend( {}, defaults, options );
        var formhandlerDiv = $(this);
        if(settings.autoDisableSubmitButton) {
            formhandlerDiv.find(settings.submitButtonSelector).attr("disabled", "disabled");
        }
        if(settings.ajaxSubmit) {
            formhandlerDiv.on('submit', 'form', function (e) {
                settings.submitStart.call(formhandlerDiv);
                formhandlerDiv.find(settings.submitButtonSelector).attr("disabled", "disabled");
                var form = $(this);
                var url = '/index.php?eID=formhandler-ajaxsubmit&id=' + settings.pageID + '&randomID=' + settings.randomID + '&uid=' + settings.contentID + '&L=' + settings.lang;
                var postData = form.serialize() + "&" + formhandlerDiv.find(settings.submitButtonSelector).attr("name") + "=submit";
                formhandlerDiv.find(".loading_ajax-submit").show();
                jQuery.ajax({
                    type: "post",
                    url: url,
                    data: postData,
                    dataType: "json",
                    success: function(data, textStatus) {
                        settings.submitComplete.call(formhandlerDiv, data);
                        if (data.redirect) {
                            window.location.href = data.redirect;
                        } else {
                            formhandlerDiv[0].innerHTML = data.form;
                        }
                        settings.submitFinished.call(formhandlerDiv, data);
                    }
                });
                e.preventDefault();
            });
        }

        formhandlerDiv.on('click', 'a.formhandler_removelink', function (e) {
            var el = $(this);
            var url = el.attr("href");
            var container = el.closest("div[id^='Tx_Formhandler_UploadedFiles_']");
            container.load(url + '#' + container.attr("id"));
            e.preventDefault();
        });

        for (index = 0; index < settings.validateFields.length; ++index) {
            var shortFieldName = settings.validateFields[index];
            var fieldName = shortFieldName;
            if(settings.formValuesPrefix) {
                fieldName = settings.formValuesPrefix + '[' + fieldName + ']';
            }

            formhandlerDiv.on('blur', "input[name^='"+ fieldName + "'],textarea[name^='"+ fieldName + "']", function() {
                var field = $(this);
                var name = field.attr("name");
                var shortName = name.replace(settings.formValuesPrefix, '').replace("[", "").replace("]", "");
                var fieldVal = encodeURIComponent(field.val());
                if(field.attr("type") == "radio" || field.attr("type") == "checkbox") {
                    if (field.attr("checked") == "") {
                        fieldVal = "";
                    }
                }
                var loading = formhandlerDiv.find('#loading_' + shortName);
                var result = formhandlerDiv.find('#result_' + shortName);
                loading.show();
                result.hide();
                var url = '/index.php?eID=formhandler&id=' + settings.pageID + '&field=' + shortName + '&randomID=' + settings.randomID + '&uid=' + settings.contentID + '&L=' + settings.lang;
                var postData = formhandlerDiv.find("form").serialize() + "&" + formhandlerDiv.find(settings.submitButtonSelector).attr("name") + "=submit";
                settings.validateStart.call(formhandlerDiv, shortName);
                jQuery.ajax({
                    type: "post",
                    url: url,
                    data: postData,
                    success: function(data, textStatus) {
                        settings.validateComplete.call(formhandlerDiv, shortName, result);
                        result.html(data);
                        loading.hide();
                        result.show();
                        isFieldValid = false;
                        if (result.find("SPAN.error").length > 0) {
                            result.data("isValid", false);
                        } else {
                            isFieldValid = true;
                            result.data("isValid", true);
                        }
                        settings.validateFinished.call(formhandlerDiv, shortName, result);

                        if(settings.autoDisableSubmitButton) {
                            var valid = true;
                            formhandlerDiv.find(".formhandler-ajax-validation-result").each(function () {
                                if (!$(this).data("isValid")) {
                                    valid = false;
                                }
                            });
                            var button = formhandlerDiv.find("." + settings.validationStatusClasses.base);
                            if (valid) {
                                button.removeAttr("disabled");
                                button.removeClass(settings.validationStatusClasses.invalid).addClass(settings.validationStatusClasses.valid);
                            } else {
                                button.attr("disabled", "disabled");
                                button.removeClass(settings.validationStatusClasses.valid).addClass(settings.validationStatusClasses.invalid);
                            }
                        }
                    }
                });
            });
        }
    };

}( jQuery ));



